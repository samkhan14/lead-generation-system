<?php

namespace App\Http\Controllers\Admin;

use App\Domains\AI\Enums\AiEmployeeStatus;
use App\Domains\AI\Models\AiEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAiEmployeeRequest;
use App\Http\Requests\Admin\UpdateAiEmployeeRequest;
use App\Http\Resources\Admin\AiEmployeeResource;
use App\Support\AiAdminOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiEmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ai.employees.view')->only(['index', 'show']);
        $this->middleware('permission:ai.employees.create')->only(['store']);
        $this->middleware('permission:ai.employees.update')->only(['update']);
        $this->middleware('permission:ai.employees.delete')->only(['destroy']);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'role' => $request->query('role'),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $query = AiEmployee::query()
            ->with(['provider:id,name,slug', 'model:id,name,slug']);

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder->where('name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('department', 'like', '%'.$filters['q'].'%');
            });
        }

        if (filled($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        $employees = $query
            ->orderBy('name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Admin/AI/Employees/Index', [
            'employees' => AiEmployeeResource::collection($employees),
            'filters' => array_map(
                fn ($value) => $value === '' ? null : $value,
                $filters,
            ),
            'statusOptions' => AiAdminOptions::enumOptions(AiEmployeeStatus::class),
            'roleOptions' => AiAdminOptions::enumOptions(\App\Domains\AI\Enums\AiEmployeeRole::class),
            'providerOptions' => AiAdminOptions::providerOptions(),
            'modelOptions' => AiAdminOptions::modelOptions(),
            'knowledgeSourceOptions' => AiAdminOptions::knowledgeSources(),
        ]);
    }

    public function show(AiEmployee $aiEmployee): Response
    {
        $aiEmployee->load([
            'provider:id,name,slug',
            'model:id,name,slug',
            'fallbackProvider:id,name,slug',
            'fallbackModel:id,name,slug',
        ]);

        return Inertia::render('Admin/AI/Employees/Show', [
            'employee' => AiEmployeeResource::make($aiEmployee)->resolve(),
            'statusOptions' => AiAdminOptions::enumOptions(AiEmployeeStatus::class),
            'roleOptions' => AiAdminOptions::enumOptions(\App\Domains\AI\Enums\AiEmployeeRole::class),
            'providerOptions' => AiAdminOptions::providerOptions(),
            'modelOptions' => AiAdminOptions::modelOptions(),
            'knowledgeSourceOptions' => AiAdminOptions::knowledgeSources(),
        ]);
    }

    public function store(StoreAiEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $employee = AiEmployee::query()->create($data);

        return redirect()
            ->route('admin.ai.employees.show', $employee)
            ->with('success', 'AI employee created successfully.');
    }

    public function update(UpdateAiEmployeeRequest $request, AiEmployee $aiEmployee): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $aiEmployee->update($data);

        return redirect()
            ->route('admin.ai.employees.show', $aiEmployee)
            ->with('success', 'AI employee updated successfully.');
    }

    public function destroy(AiEmployee $aiEmployee): RedirectResponse
    {
        $aiEmployee->delete();

        return redirect()
            ->route('admin.ai.employees.index')
            ->with('success', 'AI employee deleted successfully.');
    }
}
