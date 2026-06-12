<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadScore;

class LeadScoringService
{
    /**
     * @return array<string, int>
     */
    public function calculateFactors(Lead $lead): array
    {
        $completeness = 0;

        if ($lead->email) {
            $completeness += 10;
        }

        if ($lead->phone) {
            $completeness += 10;
        }

        if ($lead->website) {
            $completeness += 10;
        }

        if ($lead->company) {
            $completeness += 5;
        }

        if ($lead->job_title) {
            $completeness += 5;
        }

        $sourceQuality = match (strtolower((string) $lead->source)) {
            'api' => 25,
            'scraper' => 20,
            'import' => 15,
            'manual' => 10,
            default => $lead->source ? 8 : 0,
        };

        $contactRichness = 0;

        if ($lead->email && $lead->phone) {
            $contactRichness += 15;
        }

        if ($lead->website) {
            $contactRichness += 10;
        }

        if ($lead->notes) {
            $contactRichness += 5;
        }

        return [
            'completeness' => $completeness,
            'source_quality' => $sourceQuality,
            'contact_richness' => $contactRichness,
        ];
    }

    public function score(Lead $lead): LeadScore
    {
        $factors = $this->calculateFactors($lead);
        $score = min(100, array_sum($factors));

        return LeadScore::query()->create([
            'lead_id' => $lead->id,
            'score' => $score,
            'score_grade' => LeadScore::gradeForScore($score),
            'temperature' => LeadScore::temperatureForScore($score),
            'factors' => $factors,
            'calculated_at' => now(),
        ]);
    }
}
