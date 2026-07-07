<?php

namespace App\Domains\Crm\Services;

use App\Domains\Crm\Enums\LeadActivityType;
use App\Domains\Crm\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use InvalidArgumentException;

class DealService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function stages(): array
    {
        return config('deals.stages', []);
    }

    /**
     * @return array<int, array{value: string, label: string, probability: int|null, terminal: bool}>
     */
    public function stageOptions(): array
    {
        return collect($this->stages())
            ->sortBy(fn (array $stage) => $stage['sort'] ?? 0)
            ->map(fn (array $stage, string $key) => [
                'value' => $key,
                'label' => $stage['label'] ?? ucfirst($key),
                'probability' => isset($stage['probability']) ? (int) $stage['probability'] : null,
                'terminal' => (bool) ($stage['terminal'] ?? false),
            ])
            ->values()
            ->all();
    }

    public function isValidStage(string $stage): bool
    {
        return array_key_exists($stage, $this->stages());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Lead $lead, array $data, User $user): Deal
    {
        $stage = $data['stage'] ?? config('deals.default_stage', 'qualified');

        if (! $this->isValidStage($stage)) {
            throw new InvalidArgumentException("Invalid deal stage: {$stage}");
        }

        $deal = Deal::query()->create([
            'lead_id' => $lead->id,
            'service_id' => $data['service_id'] ?? null,
            'title' => $data['title'],
            'stage' => $stage,
            'value' => $data['value'] ?? null,
            'currency' => $data['currency'] ?? config('deals.default_currency', 'USD'),
            'probability' => $data['probability'] ?? ($this->stages()[$stage]['probability'] ?? null),
            'expected_close_date' => $data['expected_close_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? $lead->assigned_to,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(LeadActivityService::class)->log(
            lead: $lead,
            type: LeadActivityType::DealCreated,
            subject: 'Deal created',
            body: "Deal \"{$deal->title}\" created at {$this->stageLabel($stage)}.",
            user: $user,
            metadata: ['deal_id' => $deal->id],
        );

        return $deal->fresh(['service', 'assignee']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Deal $deal, array $data, User $user): Deal
    {
        if (isset($data['stage']) && ! $this->isValidStage($data['stage'])) {
            throw new InvalidArgumentException("Invalid deal stage: {$data['stage']}");
        }

        $stage = $data['stage'] ?? $deal->stage;
        $payload = array_merge($data, [
            'updated_by' => $user->id,
        ]);

        if ($stage === 'won') {
            $payload['won_at'] = now();
            $payload['lost_at'] = null;
            $payload['lost_reason'] = null;
            $payload['probability'] = 100;
        } elseif ($stage === 'lost') {
            $payload['lost_at'] = now();
            $payload['won_at'] = null;
            $payload['probability'] = 0;
        }

        if (isset($data['stage']) && ! isset($data['probability'])) {
            $payload['probability'] = $this->stages()[$stage]['probability'] ?? $deal->probability;
        }

        $deal->update($payload);

        return $deal->fresh(['service', 'assignee']);
    }

    public function stageLabel(string $stage): string
    {
        return (string) ($this->stages()[$stage]['label'] ?? ucfirst(str_replace('_', ' ', $stage)));
    }
}
