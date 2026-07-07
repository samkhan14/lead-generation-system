<?php

namespace App\Http\Controllers;

use App\Domains\Crm\Services\DealService;
use App\Domains\Crm\Services\LeadPipelineService;
use App\Domains\Crm\Services\LeadTimelineService;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Jobs\VerifyLeadJob;
use App\Models\Lead;
use App\Models\LeadScore;
use App\Models\User;
use App\Services\LeadIngestionService;
use App\Services\LeadPitchService;
use App\Services\LeadWorkforcePanelService;
use App\Services\ServiceCatalogService;
use App\Support\LeadQueryFilters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function __construct(
        private LeadIngestionService $ingestionService,
        private LeadPitchService $pitchService,
        private LeadWorkforcePanelService $workforcePanel,
        private LeadPipelineService $pipeline,
        private LeadTimelineService $timeline,
        private DealService $dealService,
        private ServiceCatalogService $catalogService,
    ) {
        $this->middleware('permission:leads.view')->only(['index', 'show', 'reverify']);
        $this->middleware('permission:leads.create')->only(['create', 'store']);
        $this->middleware('permission:leads.update')->only(['edit', 'update']);
        $this->middleware('permission:leads.delete')->only('destroy');
    }

    public function index(Request $request): Response
    {
        $filters = [
            'temperature' => $request->query('temperature'),
            'q' => trim((string) $request->query('q', '')),
            'country' => trim((string) $request->query('country', '')),
            'city' => trim((string) $request->query('city', '')),
            'area' => trim((string) $request->query('area', '')),
            'keyword' => trim((string) $request->query('keyword', '')),
            'source' => $request->query('source'),
            'pitch_type' => $request->query('pitch_type'),
            'has_website' => $request->query('has_website'),
            'subreddit' => trim((string) $request->query('subreddit', '')),
            'lead_kind' => $request->query('lead_kind'),
            'posted_within' => $request->query('posted_within'),
            'status' => $request->query('status'),
            'assigned_to' => $request->query('assigned_to'),
            'sort' => $request->query('sort', 'created_desc'),
            'per_page' => (int) $request->query('per_page', 15),
        ];

        $perPage = in_array($filters['per_page'], [10, 15, 25, 50], true) ? $filters['per_page'] : 15;
        $filters['per_page'] = $perPage;

        if (! in_array($filters['temperature'], ['hot', 'warm', 'cold'], true)) {
            $filters['temperature'] = null;
        }

        if (! in_array($filters['has_website'], ['yes', 'no'], true)) {
            $filters['has_website'] = null;
        }

        $leads = Lead::query()
            ->with(['latestScore', 'assignedTo'])
            ->tap(fn ($query) => LeadQueryFilters::apply($query, $filters))
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Lead $lead) => $this->formatLeadListItem($lead));

        $dbCountries = LeadQueryFilters::distinctMetadataValues('scrape_country');

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
            'filters' => array_map(
                fn ($value) => $value === '' ? null : $value,
                $filters,
            ),
            'filterOptions' => [
                'countries' => collect(config('countries.list', []))
                    ->merge($dbCountries)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
                'cities' => LeadQueryFilters::distinctMetadataValues('scrape_city'),
                'areas' => LeadQueryFilters::distinctMetadataValues('scrape_area'),
                'keywords' => LeadQueryFilters::distinctMetadataValues('scrape_keyword'),
                'sources' => [
                    ['value' => 'google_maps', 'label' => 'Google Maps'],
                    ['value' => 'yelp', 'label' => 'Yelp'],
                    ['value' => 'hotfrog', 'label' => 'Hotfrog'],
                    ['value' => 'yellow_pages', 'label' => 'Yellow Pages'],
                    ['value' => 'manta', 'label' => 'Manta'],
                    ['value' => 'foursquare', 'label' => 'Foursquare'],
                    ['value' => 'the_manifest', 'label' => 'The Manifest'],
                    ['value' => 'goodfirms', 'label' => 'GoodFirms'],
                    ['value' => 'designrush', 'label' => 'DesignRush'],
                    ['value' => 'upcity', 'label' => 'UpCity'],
                    ['value' => 'openstreetmap', 'label' => 'OpenStreetMap'],
                    ['value' => 'bing_places', 'label' => 'Bing Places'],
                    ['value' => 'reddit', 'label' => 'Reddit'],
                    ['value' => 'manual', 'label' => 'Manual'],
                    ['value' => 'api', 'label' => 'API'],
                    ['value' => 'import', 'label' => 'Import'],
                    ['value' => 'scraper', 'label' => 'Scraper'],
                ],
                'subreddits' => LeadQueryFilters::distinctMetadataValues('subreddit'),
                'lead_kinds' => collect(config('reddit.lead_kinds', []))
                    ->keys()
                    ->map(fn (string $kind) => [
                        'value' => $kind,
                        'label' => ucwords(str_replace('_', ' ', $kind)),
                    ])
                    ->values()
                    ->all(),
                'posted_within' => [
                    ['value' => 1, 'label' => 'Posted today'],
                    ['value' => 7, 'label' => 'Posted this week'],
                    ['value' => 30, 'label' => 'Posted this month'],
                ],
                'pitch_types' => LeadPitchService::pitchTypeOptions(),
                'pipeline_stages' => $this->pipeline->stageOptions(),
                'sorts' => [
                    ['value' => 'created_desc', 'label' => 'Newest first'],
                    ['value' => 'created_asc', 'label' => 'Oldest first'],
                    ['value' => 'score_desc', 'label' => 'Score: high to low'],
                    ['value' => 'score_asc', 'label' => 'Score: low to high'],
                    ['value' => 'rating_desc', 'label' => 'Rating: high to low'],
                    ['value' => 'rating_asc', 'label' => 'Rating: low to high'],
                    ['value' => 'company_asc', 'label' => 'Company: A–Z'],
                    ['value' => 'company_desc', 'label' => 'Company: Z–A'],
                ],
            ],
            'voiceCallOptions' => $this->workforcePanel->voiceCallOptions($request->user()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Leads/Create');
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $result = $this->ingestionService->ingest([
            ...$request->validated(),
            'source' => $request->input('source', 'manual'),
            'created_by' => $request->user()->id,
            'assigned_to' => $request->user()->id,
        ]);

        if ($result->status === 'duplicate') {
            return redirect()
                ->route('leads.create')
                ->withErrors(['duplicate' => 'A lead with this email, phone, or website already exists.'])
                ->with('duplicate_lead_id', $result->duplicateLead?->id);
        }

        return redirect()->route('leads.show', $result->lead);
    }

    public function edit(Lead $lead): Response
    {
        $lead->load('assignedTo');

        return Inertia::render('Leads/Edit', [
            'lead' => [
                'id' => $lead->id,
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'website' => $lead->website,
                'company' => $lead->company,
                'job_title' => $lead->job_title,
                'notes' => $lead->notes,
                'status' => $lead->status ?: $this->pipeline->defaultStage(),
                'assigned_to' => $lead->assigned_to,
                'lost_reason' => data_get($lead->metadata, 'lost_reason'),
            ],
            'pipelineStages' => $this->pipeline->stageOptions(),
            'lostReasons' => collect(config('lead_pipeline.lost_reasons', []))
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        if (array_key_exists('status', $validated)) {
            $this->pipeline->updateStage(
                $lead,
                $validated['status'],
                $user,
                $validated['lost_reason'] ?? null,
            );
            unset($validated['status'], $validated['lost_reason']);
        }

        if (array_key_exists('assigned_to', $validated)) {
            $this->pipeline->updateAssignment($lead, $validated['assigned_to'], $user);
            unset($validated['assigned_to']);
        }

        if ($validated !== []) {
            $lead->update($validated);
        }

        return redirect()->route('leads.show', $lead)->with('success', 'Lead updated.');
    }

    public function show(Request $request, Lead $lead): Response
    {
        $lead->load([
            'scores' => fn ($query) => $query->latest('calculated_at'),
            'assignedTo',
            'createdBy',
            'deals.service',
            'deals.assignee',
            'tasks.assignee',
            'quotes.creator',
        ]);

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
                'status' => $lead->status ?: $this->pipeline->defaultStage(),
                'notes' => $lead->notes,
                'metadata' => $lead->metadata,
                'verified_at' => $lead->verified_at?->toIso8601String(),
                'assigned_to' => $lead->assignedTo?->name,
                'assigned_to_id' => $lead->assigned_to,
                'created_by' => $lead->createdBy?->name,
                'created_at' => $lead->created_at?->toIso8601String(),
                'latest_score' => $lead->latestScore ? $this->formatScore($lead->latestScore) : null,
                'pitch_recommendations' => $this->pitchService->recommendations($lead),
                'scores' => $lead->scores->map(fn ($score) => [
                    ...$this->formatScore($score),
                    'id' => $score->id,
                    'calculated_at' => $score->calculated_at?->toIso8601String(),
                ]),
            ],
            'crm' => $this->crmPayload($lead, $request->user()),
            'workforce' => $this->workforcePanel->forLead($lead, $request->user()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function crmPayload(Lead $lead, User $user): array
    {
        return [
            'timeline' => $this->timeline->forLead($lead),
            'pipeline_stages' => $this->pipeline->stageOptions(),
            'deal_stages' => $this->dealService->stageOptions(),
            'lost_reasons' => collect(config('lead_pipeline.lost_reasons', []))
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'deal_lost_reasons' => collect(config('deals.lost_reasons', []))
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'services' => $this->catalogService->activeServices()->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'short_description' => $service->short_description,
            ])->values()->all(),
            'deals' => $lead->deals->map(fn ($deal) => [
                'id' => $deal->id,
                'uuid' => $deal->uuid,
                'title' => $deal->title,
                'stage' => $deal->stage,
                'stage_label' => $this->dealService->stageLabel($deal->stage),
                'value' => $deal->value,
                'currency' => $deal->currency,
                'probability' => $deal->probability,
                'expected_close_date' => $deal->expected_close_date?->format('Y-m-d'),
                'service' => $deal->service?->name,
                'assignee' => $deal->assignee?->name,
            ])->values()->all(),
            'tasks' => $lead->tasks->map(fn ($task) => [
                'id' => $task->id,
                'uuid' => $task->uuid,
                'title' => $task->title,
                'status' => $task->status->value,
                'priority' => $task->priority,
                'due_at' => $task->due_at?->toIso8601String(),
                'assignee' => $task->assignee?->name,
                'is_overdue' => $task->due_at !== null && $task->due_at->isPast() && $task->isOpen(),
            ])->values()->all(),
            'quotes' => $lead->quotes->map(fn ($quote) => [
                'id' => $quote->id,
                'uuid' => $quote->uuid,
                'title' => $quote->title,
                'status' => $quote->status->value,
                'status_label' => $quote->status->label(),
                'sent_at' => $quote->sent_at?->toIso8601String(),
                'created_at' => $quote->created_at?->toIso8601String(),
            ])->values()->all(),
            'activity_types' => collect([
                ['value' => 'note', 'label' => 'Note'],
                ['value' => 'call', 'label' => 'Call'],
                ['value' => 'email', 'label' => 'Email'],
                ['value' => 'meeting', 'label' => 'Meeting'],
            ])->values()->all(),
        ];
    }

    public function reverify(Request $request, Lead $lead): RedirectResponse
    {
        VerifyLeadJob::dispatch($lead);

        return redirect()->back()->with('reverify_queued', true);
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
            'full_name' => $lead->company ?: $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'website' => $lead->website,
            'company' => $lead->company,
            'source' => $lead->source,
            'address' => data_get($lead->metadata, 'address'),
            'scrape_country' => data_get($lead->metadata, 'scrape_country'),
            'scrape_city' => data_get($lead->metadata, 'scrape_city'),
            'scrape_area' => data_get($lead->metadata, 'scrape_area'),
            'scrape_keyword' => data_get($lead->metadata, 'scrape_keyword'),
            'rating' => data_get($lead->metadata, 'rating'),
            'review_count' => data_get($lead->metadata, 'review_count'),
            'pitch_summary' => $this->pitchService->primaryRecommendation($lead),
            'verification_status' => data_get($lead->metadata, 'verification.status'),
            'verified_at' => $lead->verified_at?->toIso8601String(),
            'assigned_to' => $lead->assignedTo?->name,
            'status' => $lead->status ?: $this->pipeline->defaultStage(),
            'status_label' => $this->pipeline->stageLabel($lead->status ?: $this->pipeline->defaultStage()),
            'latest_score' => $lead->latestScore ? $this->formatScore($lead->latestScore) : null,
            'created_at' => $lead->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatScore(LeadScore $score): array
    {
        return [
            'score' => $score->score,
            'intent_score' => $score->intent_score,
            'opportunity_score' => $score->opportunity_score,
            'authenticity_score' => $score->authenticity_score,
            'scoring_version' => $score->scoring_version,
            'score_grade' => $score->score_grade,
            'temperature' => $score->temperature,
            'factors' => $score->factors,
            'calculated_at' => $score->calculated_at?->toIso8601String(),
        ];
    }
}
