<?php

namespace App\Domains\Crm\Services;

use App\Domains\Crm\Models\LeadActivity;
use App\Models\Lead;
use Illuminate\Support\Collection;

class LeadTimelineService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forLead(Lead $lead): array
    {
        $activities = LeadActivity::query()
            ->with('user:id,name')
            ->where('lead_id', $lead->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (LeadActivity $activity) => $this->formatActivity($activity));

        return $activities->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatActivity(LeadActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'uuid' => $activity->uuid,
            'type' => $activity->type->value,
            'type_label' => $activity->type->label(),
            'subject' => $activity->subject,
            'body' => $activity->body,
            'metadata' => $activity->metadata,
            'occurred_at' => $activity->occurred_at?->toIso8601String(),
            'user' => $activity->user ? [
                'id' => $activity->user->id,
                'name' => $activity->user->name,
            ] : null,
        ];
    }

    /**
     * @return Collection<int, Lead>
     */
    public function pipelineLeads(?string $assignedTo = null): Collection
    {
        $query = Lead::query()
            ->with(['latestScore', 'assignedTo'])
            ->orderByDesc('updated_at');

        if ($assignedTo === 'me') {
            $query->where('assigned_to', auth()->id());
        } elseif ($assignedTo === 'unassigned') {
            $query->whereNull('assigned_to');
        }

        return $query->get();
    }
}
