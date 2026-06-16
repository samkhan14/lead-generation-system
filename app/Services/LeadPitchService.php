<?php

namespace App\Services;

use App\Models\Lead;

class LeadPitchService
{
    /**
     * @return array<int, array{
     *     type: string,
     *     service: string,
     *     priority: string,
     *     reason: string,
     *     opener: string
     * }>
     */
    public function recommendations(Lead $lead): array
    {
        $recommendations = [];

        foreach (config('lead_pitches.types', []) as $type => $definition) {
            if (! $this->matchesType($lead, $type)) {
                continue;
            }

            $recommendations[] = $this->formatRecommendation($lead, $type, $definition);
        }

        return $this->sortByPriority($recommendations);
    }

    /**
     * @return array{type: string, service: string, priority: string, reason: string, opener: string}|null
     */
    public function primaryRecommendation(Lead $lead): ?array
    {
        return $this->recommendations($lead)[0] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public function matchingTypes(Lead $lead): array
    {
        return collect(config('lead_pitches.types', []))
            ->keys()
            ->filter(fn (string $type) => $this->matchesType($lead, $type))
            ->values()
            ->all();
    }

    public function matchesType(Lead $lead, string $type): bool
    {
        $reviewThreshold = (int) config('lead_pitches.reviews_growth_threshold', 20);
        $ratingThreshold = (float) config('lead_pitches.reputation_rating_threshold', 4.0);
        $rating = data_get($lead->metadata, 'rating');
        $reviewCount = data_get($lead->metadata, 'review_count');

        return match ($type) {
            'website_build' => blank($lead->website),
            'website_audit' => filled($lead->website),
            'reviews_growth' => is_numeric($reviewCount) && (int) $reviewCount < $reviewThreshold,
            'reputation_repair' => is_numeric($rating) && (float) $rating < $ratingThreshold,
            'gbp_optimization' => $lead->source === 'google_maps',
            'phone_outreach' => filled($lead->phone) && blank($lead->email),
            'reddit_outreach' => $lead->source === 'reddit',
            default => false,
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function pitchTypeOptions(): array
    {
        return collect(config('lead_pitches.types', []))
            ->map(fn (array $definition, string $key) => [
                'value' => $key,
                'label' => $definition['label'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $definition
     * @return array{type: string, service: string, priority: string, reason: string, opener: string}
     */
    private function formatRecommendation(Lead $lead, string $type, array $definition): array
    {
        $reason = $definition['reason'];
        $rating = data_get($lead->metadata, 'rating');
        $reviewCount = data_get($lead->metadata, 'review_count');

        if ($type === 'reviews_growth' && is_numeric($reviewCount)) {
            $reason = "Only {$reviewCount} Google reviews were found, which leaves room to build trust and win more local searches.";
        }

        if ($type === 'reputation_repair' && is_numeric($rating)) {
            $reason = "The Google rating is {$rating}, so reputation improvement can directly affect customer trust.";
        }

        return [
            'type' => $type,
            'service' => $definition['service'],
            'priority' => $definition['priority'],
            'reason' => $reason,
            'opener' => $definition['opener'],
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $recommendations
     * @return array<int, array<string, string>>
     */
    private function sortByPriority(array $recommendations): array
    {
        $rank = ['high' => 0, 'medium' => 1, 'low' => 2];

        usort(
            $recommendations,
            fn (array $a, array $b): int => ($rank[$a['priority']] ?? 9) <=> ($rank[$b['priority']] ?? 9),
        );

        return $recommendations;
    }
}
