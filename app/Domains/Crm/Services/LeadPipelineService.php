<?php

namespace App\Domains\Crm\Services;

use App\Models\Lead;
use App\Models\User;
use InvalidArgumentException;

class LeadPipelineService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function stages(): array
    {
        return config('lead_pipeline.stages', []);
    }

    public function defaultStage(): string
    {
        return (string) config('lead_pipeline.default_stage', 'new');
    }

    public function stageLabel(string $stage): string
    {
        return (string) ($this->stages()[$stage]['label'] ?? ucfirst(str_replace('_', ' ', $stage)));
    }

    public function isValidStage(?string $stage): bool
    {
        return filled($stage) && array_key_exists($stage, $this->stages());
    }

    public function isTerminalStage(string $stage): bool
    {
        return (bool) ($this->stages()[$stage]['terminal'] ?? false);
    }

    /**
     * @return array<int, array{value: string, label: string, color: string, terminal: bool}>
     */
    public function stageOptions(): array
    {
        return collect($this->stages())
            ->sortBy(fn (array $stage) => $stage['sort'] ?? 0)
            ->map(fn (array $stage, string $key) => [
                'value' => $key,
                'label' => $stage['label'] ?? ucfirst($key),
                'color' => $stage['color'] ?? 'slate',
                'terminal' => (bool) ($stage['terminal'] ?? false),
            ])
            ->values()
            ->all();
    }

    public function updateStage(Lead $lead, string $stage, User $user, ?string $lostReason = null): Lead
    {
        if (! $this->isValidStage($stage)) {
            throw new InvalidArgumentException("Invalid pipeline stage: {$stage}");
        }

        if ($stage === 'lost' && blank($lostReason)) {
            throw new InvalidArgumentException('Lost reason is required when marking a lead as lost.');
        }

        $previous = $lead->status ?: $this->defaultStage();

        if ($previous === $stage) {
            return $lead;
        }

        $metadata = $lead->metadata ?? [];

        if ($stage === 'lost') {
            $metadata['lost_reason'] = $lostReason;
        } elseif ($previous === 'lost') {
            unset($metadata['lost_reason']);
        }

        $lead->update([
            'status' => $stage,
            'metadata' => $metadata,
        ]);

        app(LeadActivityService::class)->logStatusChange($lead, $previous, $stage, $user, $lostReason);

        return $lead->fresh();
    }

    public function updateAssignment(Lead $lead, ?int $assigneeId, User $actor): Lead
    {
        $previousId = $lead->assigned_to;

        if ($previousId === $assigneeId) {
            return $lead;
        }

        $lead->update(['assigned_to' => $assigneeId]);

        app(LeadActivityService::class)->logAssignment($lead, $previousId, $assigneeId, $actor);

        return $lead->fresh(['assignedTo']);
    }
}
