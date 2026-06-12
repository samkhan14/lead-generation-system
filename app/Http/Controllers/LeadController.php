<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use App\Services\LeadScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function __construct(
        private LeadScoringService $scoringService,
    ) {
        $this->middleware('permission:leads.view')->only(['index', 'show']);
        $this->middleware('permission:leads.create')->only(['create', 'store']);
        $this->middleware('permission:leads.delete')->only('destroy');
    }

    public function index(Request $request): Response
    {
        $temperature = $request->query('temperature');

        $leads = Lead::query()
            ->with(['latestScore', 'assignedTo'])
            ->when(
                in_array($temperature, ['hot', 'warm'], true),
                fn ($query) => $query->withTemperature($temperature),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Lead $lead) => $this->formatLeadListItem($lead));

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
            'filters' => [
                'temperature' => in_array($temperature, ['hot', 'warm'], true) ? $temperature : null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Leads/Create');
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $lead = Lead::query()->create([
            ...$request->safe()->only([
                'first_name',
                'last_name',
                'email',
                'phone',
                'website',
                'company',
                'job_title',
                'source',
                'notes',
            ]),
            'created_by' => $request->user()->id,
            'assigned_to' => $request->user()->id,
        ]);

        $this->scoringService->score($lead);

        return redirect()->route('leads.show', $lead);
    }

    public function show(Lead $lead): Response
    {
        $lead->load(['scores' => fn ($query) => $query->latest('calculated_at'), 'assignedTo', 'createdBy']);

        return Inertia::render('Leads/Show', [
            'lead' => [
                'id' => $lead->id,
                'uuid' => $lead->uuid,
                'full_name' => $lead->full_name,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'website' => $lead->website,
                'company' => $lead->company,
                'job_title' => $lead->job_title,
                'source' => $lead->source,
                'status' => $lead->status,
                'notes' => $lead->notes,
                'assigned_to' => $lead->assignedTo?->name,
                'created_by' => $lead->createdBy?->name,
                'created_at' => $lead->created_at?->toIso8601String(),
                'latest_score' => $lead->latestScore ? [
                    'score' => $lead->latestScore->score,
                    'score_grade' => $lead->latestScore->score_grade,
                    'temperature' => $lead->latestScore->temperature,
                    'factors' => $lead->latestScore->factors,
                ] : null,
                'scores' => $lead->scores->map(fn ($score) => [
                    'id' => $score->id,
                    'score' => $score->score,
                    'score_grade' => $score->score_grade,
                    'temperature' => $score->temperature,
                    'factors' => $score->factors,
                    'calculated_at' => $score->calculated_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('leads.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formatLeadListItem(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'uuid' => $lead->uuid,
            'full_name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'website' => $lead->website,
            'company' => $lead->company,
            'source' => $lead->source,
            'assigned_to' => $lead->assignedTo?->name,
            'latest_score' => $lead->latestScore ? [
                'score' => $lead->latestScore->score,
                'score_grade' => $lead->latestScore->score_grade,
                'temperature' => $lead->latestScore->temperature,
                'calculated_at' => $lead->latestScore->calculated_at?->toIso8601String(),
            ] : null,
            'created_at' => $lead->created_at?->toIso8601String(),
        ];
    }
}
