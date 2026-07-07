<?php

namespace App\Domains\Crm\Services;

use App\Domains\Crm\Enums\LeadActivityType;
use App\Domains\Crm\Enums\TaskStatus;
use App\Domains\Crm\Models\Task;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Task
    {
        $task = Task::query()->create([
            'lead_id' => $data['lead_id'] ?? null,
            'deal_id' => $data['deal_id'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? $user->id,
            'created_by' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'status' => TaskStatus::Pending,
            'priority' => $data['priority'] ?? 'medium',
        ]);

        if ($task->lead_id) {
            $lead = Lead::query()->find($task->lead_id);

            if ($lead) {
                app(LeadActivityService::class)->log(
                    lead: $lead,
                    type: LeadActivityType::TaskCreated,
                    subject: 'Task created',
                    body: "Task \"{$task->title}\" created.",
                    user: $user,
                    metadata: ['task_id' => $task->id],
                );
            }
        }

        return $task->fresh(['lead', 'deal', 'assignee']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh(['lead', 'deal', 'assignee']);
    }

    public function complete(Task $task): Task
    {
        $task->update([
            'status' => TaskStatus::Completed,
            'completed_at' => now(),
        ]);

        return $task->fresh(['lead', 'deal', 'assignee']);
    }

    /**
     * @return Collection<int, Task>
     */
    public function dueForUser(User $user, int $limit = 10): Collection
    {
        return Task::query()
            ->with(['lead:id,first_name,last_name,company', 'assignee:id,name'])
            ->where('assigned_to', $user->id)
            ->whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress])
            ->where(function ($query): void {
                $query
                    ->whereNull('due_at')
                    ->orWhere('due_at', '<=', now()->addDays(7));
            })
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function formatForDashboard(Collection $tasks): array
    {
        return $tasks->map(fn (Task $task) => [
            'id' => $task->id,
            'uuid' => $task->uuid,
            'title' => $task->title,
            'status' => $task->status->value,
            'priority' => $task->priority,
            'due_at' => $task->due_at?->toIso8601String(),
            'is_overdue' => $task->due_at !== null && $task->due_at->isPast() && $task->isOpen(),
            'lead' => $task->lead ? [
                'id' => $task->lead->id,
                'name' => $task->lead->company ?: trim("{$task->lead->first_name} {$task->lead->last_name}"),
            ] : null,
        ])->values()->all();
    }
}
