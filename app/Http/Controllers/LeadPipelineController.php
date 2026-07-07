<?php

namespace App\Http\Controllers;

use App\Domains\Crm\Services\LeadPipelineService;
use App\Domains\Crm\Services\LeadTimelineService;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadPipelineController extends Controller
{
    public function __construct(
        private LeadPipelineService $pipeline,
        private LeadTimelineService $timeline,
    ) {
        $this->middleware('permission:leads.view')->only('index');
    }

    public function index(Request $request): Response
    {
        $assignedFilter = $request->query('assigned_to');

        if (! in_array($assignedFilter, ['me', 'unassigned', null], true)) {
            $assignedFilter = null;
        }

        $leads = $this->timeline->pipelineLeads($assignedFilter);

        $stages = $this->pipeline->stageOptions();
        $grouped = [];

        foreach ($stages as $stage) {
            $grouped[$stage['value']] = [];
        }

        foreach ($leads as $lead) {
            $status = $lead->status ?: $this->pipeline->defaultStage();

            if (! array_key_exists($status, $grouped)) {
                $status = $this->pipeline->defaultStage();
            }

            $grouped[$status][] = [
                'id' => $lead->id,
                'uuid' => $lead->uuid,
                'full_name' => $lead->company ?: $lead->full_name,
                'company' => $lead->company,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'status' => $status,
                'assigned_to' => $lead->assignedTo?->name,
                'latest_score' => $lead->latestScore ? [
                    'score' => $lead->latestScore->score,
                    'temperature' => $lead->latestScore->temperature,
                ] : null,
                'updated_at' => $lead->updated_at?->toIso8601String(),
            ];
        }

        return Inertia::render('Leads/Pipeline', [
            'stages' => $stages,
            'columns' => $grouped,
            'filters' => [
                'assigned_to' => $assignedFilter,
            ],
            'lostReasons' => collect(config('lead_pipeline.lost_reasons', []))
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
        ]);
    }
}
