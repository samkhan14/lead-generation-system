<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $recentLeads = Lead::query()
            ->with('latestScore')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'full_name' => $lead->full_name,
                'email' => $lead->email,
                'source' => $lead->source,
                'latest_score' => $lead->latestScore?->score,
                'created_at' => $lead->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_leads' => Lead::query()->count(),
                'leads_with_scores' => Lead::query()->whereHas('scores')->count(),
                'leads_today' => Lead::query()->whereDate('created_at', today())->count(),
            ],
            'recent_leads' => $recentLeads,
        ]);
    }
}
