<?php

namespace App\Http\Controllers;

use App\Domains\Crm\Services\TaskService;
use App\Models\Lead;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(TaskService $tasks): Response
    {
        $user = auth()->user();

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

        $dueTasks = $user
            ? $tasks->formatForDashboard($tasks->dueForUser($user))
            : [];

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_leads' => Lead::query()->count(),
                'leads_with_scores' => Lead::query()->whereHas('scores')->count(),
                'leads_today' => Lead::query()->whereDate('created_at', today())->count(),
                'open_tasks' => $user && $user->can('crm.tasks.view')
                    ? \App\Domains\Crm\Models\Task::query()
                        ->where('assigned_to', $user->id)
                        ->whereIn('status', [
                            \App\Domains\Crm\Enums\TaskStatus::Pending,
                            \App\Domains\Crm\Enums\TaskStatus::InProgress,
                        ])
                        ->count()
                    : 0,
            ],
            'recent_leads' => $recentLeads,
            'due_tasks' => $dueTasks,
        ]);
    }
}
