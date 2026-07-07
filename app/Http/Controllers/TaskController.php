<?php

namespace App\Http\Controllers;

use App\Domains\Crm\Enums\TaskStatus;
use App\Domains\Crm\Models\Task;
use App\Domains\Crm\Services\TaskService;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct(private TaskService $tasks)
    {
        $this->middleware('permission:crm.tasks.manage')->except(['index']);
        $this->middleware('permission:crm.tasks.view')->only('index');
    }

    public function index(Request $request): Response
    {
        $status = $request->query('status', 'open');

        $query = Task::query()
            ->with(['lead:id,first_name,last_name,company', 'assignee:id,name'])
            ->when($status === 'open', fn ($q) => $q->whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress]))
            ->when($status === 'completed', fn ($q) => $q->where('status', TaskStatus::Completed))
            ->when($request->query('assigned_to') === 'me', fn ($q) => $q->where('assigned_to', $request->user()->id))
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Tasks/Index', [
            'tasks' => $query->through(fn (Task $task) => $this->formatTask($task)),
            'filters' => [
                'status' => $status,
                'assigned_to' => $request->query('assigned_to'),
            ],
        ]);
    }

    public function store(StoreTaskRequest $request, ?Lead $lead = null): RedirectResponse
    {
        $data = $request->validated();

        if ($lead !== null) {
            $data['lead_id'] = $lead->id;
        }

        $this->tasks->create($data, $request->user());

        return redirect()->back()->with('success', 'Task created.');
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->tasks->update($task, $request->validated());

        return redirect()->back()->with('success', 'Task updated.');
    }

    public function complete(Task $task): RedirectResponse
    {
        $this->tasks->complete($task);

        return redirect()->back()->with('success', 'Task completed.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()->back()->with('success', 'Task removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'uuid' => $task->uuid,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
            'priority' => $task->priority,
            'due_at' => $task->due_at?->toIso8601String(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'is_overdue' => $task->due_at !== null && $task->due_at->isPast() && $task->isOpen(),
            'lead' => $task->lead ? [
                'id' => $task->lead->id,
                'name' => $task->lead->company ?: trim("{$task->lead->first_name} {$task->lead->last_name}"),
            ] : null,
            'assignee' => $task->assignee ? [
                'id' => $task->assignee->id,
                'name' => $task->assignee->name,
            ] : null,
        ];
    }
}
