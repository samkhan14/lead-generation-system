<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use App\Models\LeadScore;
use App\Services\LeadIngestionService;
use App\Services\LeadPitchService;
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
    ) {
        $this->middleware('permission:leads.view')->only(['index', 'show']);
        $this->middleware('permission:leads.create')->only(['create', 'store']);
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
                    ['value' => 'manual', 'label' => 'Manual'],
                    ['value' => 'api', 'label' => 'API'],
                    ['value' => 'import', 'label' => 'Import'],
                    ['value' => 'scraper', 'label' => 'Scraper'],
                ],
                'pitch_types' => LeadPitchService::pitchTypeOptions(),
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
                'metadata' => $lead->metadata,
                'assigned_to' => $lead->assignedTo?->name,
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
            'assigned_to' => $lead->assignedTo?->name,
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
