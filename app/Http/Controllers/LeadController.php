<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use App\Models\LeadScore;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:leads.view')->only(['index', 'show']);
        $this->middleware('permission:leads.create')->only(['create', 'store']);
        $this->middleware('permission:leads.delete')->only('destroy');
    }

    public function index(): Response
    {
        $leads = Lead::query()
            ->with(['latestScore', 'assignedTo'])
            ->latest()
            ->paginate(15)
            ->through(fn (Lead $lead) => [
                'id' => $lead->id,
                'uuid' => $lead->uuid,
                'full_name' => $lead->full_name,
                'email' => $lead->email,
                'company' => $lead->company,
                'source' => $lead->source,
                'assigned_to' => $lead->assignedTo?->name,
                'latest_score' => $lead->latestScore ? [
                    'score' => $lead->latestScore->score,
                    'score_grade' => $lead->latestScore->score_grade,
                    'calculated_at' => $lead->latestScore->calculated_at?->toIso8601String(),
                ] : null,
                'created_at' => $lead->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Leads/Create');
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $lead = Lead::query()->create([
            ...$request->safe()->except('score'),
            'created_by' => $request->user()->id,
            'assigned_to' => $request->user()->id,
        ]);

        if ($request->filled('score')) {
            $score = (int) $request->input('score');

            LeadScore::query()->create([
                'lead_id' => $lead->id,
                'score' => $score,
                'score_grade' => LeadScore::gradeForScore($score),
                'calculated_at' => now(),
            ]);
        }

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
                'company' => $lead->company,
                'job_title' => $lead->job_title,
                'source' => $lead->source,
                'status' => $lead->status,
                'notes' => $lead->notes,
                'assigned_to' => $lead->assignedTo?->name,
                'created_by' => $lead->createdBy?->name,
                'created_at' => $lead->created_at?->toIso8601String(),
                'scores' => $lead->scores->map(fn (LeadScore $score) => [
                    'id' => $score->id,
                    'score' => $score->score,
                    'score_grade' => $score->score_grade,
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
}
