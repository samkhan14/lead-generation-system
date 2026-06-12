<?php

namespace App\DataTransferObjects;

use App\Models\Lead;

readonly class LeadIngestResult
{
    public function __construct(
        public string $status,
        public ?Lead $lead = null,
        public ?Lead $duplicateLead = null,
    ) {}

    public static function created(Lead $lead): self
    {
        return new self('created', $lead);
    }

    public static function duplicate(Lead $duplicateLead): self
    {
        return new self('duplicate', duplicateLead: $duplicateLead);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $latestScore = $this->lead?->latestScore;

        return [
            'status' => $this->status,
            'lead_id' => $this->lead?->id,
            'duplicate_lead_id' => $this->duplicateLead?->id,
            'score' => $latestScore?->score,
            'temperature' => $latestScore?->temperature,
            'intent_score' => $latestScore?->intent_score,
            'opportunity_score' => $latestScore?->opportunity_score,
            'authenticity_score' => $latestScore?->authenticity_score,
        ];
    }
}
