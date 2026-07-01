<?php

namespace App\Http\Controllers\Admin;

use App\Domains\AI\Enums\AiLogStatus;
use App\Domains\AI\Enums\AiRequestType;
use App\Domains\AI\Models\AiLog;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AiLogResource;
use App\Support\AiAdminOptions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ai.logs.view')->only(['index', 'show']);
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'request_type' => $request->query('request_type'),
            'ai_employee_id' => $request->query('ai_employee_id'),
            'per_page' => (int) $request->query('per_page', 20),
        ];

        $query = AiLog::query()
            ->with([
                'employee:id,name',
                'provider:id,name,slug',
                'model:id,name,slug',
                'lead:id,full_name',
            ]);

        if ($filters['q'] !== '') {
            $query->where(function ($builder) use ($filters) {
                $builder->where('uuid', 'like', '%'.$filters['q'].'%')
                    ->orWhere('error_message', 'like', '%'.$filters['q'].'%');
            });
        }

        if (filled($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['request_type'])) {
            $query->where('request_type', $filters['request_type']);
        }

        if (filled($filters['ai_employee_id'])) {
            $query->where('ai_employee_id', $filters['ai_employee_id']);
        }

        $logs = $query
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Admin/AI/Logs/Index', [
            'logs' => AiLogResource::collection($logs),
            'filters' => array_map(
                fn ($value) => $value === '' ? null : $value,
                $filters,
            ),
            'statusOptions' => AiAdminOptions::enumOptions(AiLogStatus::class),
            'requestTypeOptions' => AiAdminOptions::enumOptions(AiRequestType::class),
            'employeeOptions' => \App\Domains\AI\Models\AiEmployee::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($employee) => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function show(AiLog $aiLog): Response
    {
        $aiLog->load([
            'employee:id,name',
            'provider:id,name,slug',
            'model:id,name,slug',
            'lead:id,full_name',
        ]);

        return Inertia::render('Admin/AI/Logs/Show', [
            'log' => AiLogResource::make($aiLog)->resolve(),
        ]);
    }
}
