<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadScore;
use App\Support\LeadIdentifiers;

class LeadScoringService
{
    /**
     * @return array{
     *     intent_score: int,
     *     opportunity_score: int,
     *     authenticity_score: int,
     *     final_score: int,
     *     factors: array<string, mixed>
     * }
     */
    public function evaluate(Lead $lead): array
    {
        $intent = $this->calculateIntentScore($lead);
        $opportunity = $this->calculateOpportunityScore($lead);
        $authenticity = $this->calculateAuthenticityScore($lead);

        $finalScore = $this->calculateFinalScore(
            $intent['score'],
            $opportunity['score'],
            $authenticity['score'],
        );

        return [
            'intent_score' => $intent['score'],
            'opportunity_score' => $opportunity['score'],
            'authenticity_score' => $authenticity['score'],
            'final_score' => $finalScore,
            'factors' => [
                'intent' => $intent,
                'opportunity' => $opportunity,
                'authenticity' => $authenticity,
                'final' => [
                    'score' => $finalScore,
                    'weights' => config('lead_scoring.weights'),
                    'version' => config('lead_scoring.version'),
                ],
            ],
        ];
    }

    public function score(Lead $lead): LeadScore
    {
        $evaluation = $this->evaluate($lead);

        return LeadScore::query()->create([
            'lead_id' => $lead->id,
            'score' => $evaluation['final_score'],
            'intent_score' => $evaluation['intent_score'],
            'opportunity_score' => $evaluation['opportunity_score'],
            'authenticity_score' => $evaluation['authenticity_score'],
            'scoring_version' => config('lead_scoring.version'),
            'score_grade' => LeadScore::gradeForScore($evaluation['final_score']),
            'temperature' => LeadScore::temperatureForScore($evaluation['final_score']),
            'factors' => $evaluation['factors'],
            'calculated_at' => now(),
        ]);
    }

    /**
     * @return array{score: int, signals: array<int, string>}
     */
    public function calculateIntentScore(Lead $lead): array
    {
        $config = config('lead_scoring.intent');
        $score = 0;
        $signals = [];

        $notes = strtolower($lead->notes ?? '');
        $keywordMatches = 0;

        foreach ($config['keywords'] as $keyword) {
            if ($notes !== '' && str_contains($notes, $keyword)) {
                $keywordMatches++;
                $signals[] = "Intent keyword: {$keyword}";
            }
        }

        if ($keywordMatches > 0) {
            $keywordScore = min($config['keyword_cap'], $keywordMatches * $config['keyword_points']);
            $score += $keywordScore;
        }

        $metadataLevel = strtolower((string) data_get($lead->metadata, 'intent_level', ''));
        if (! $this->isDirectoryLead($lead) && $metadataLevel !== '' && isset($config['metadata_levels'][$metadataLevel])) {
            $score += $config['metadata_levels'][$metadataLevel];
            $signals[] = "Metadata intent level: {$metadataLevel}";
        }

        $source = strtolower((string) $lead->source);
        if ($source !== '' && isset($config['source'][$source])) {
            $score += $config['source'][$source];
            $signals[] = "Source intent signal: {$source}";
        }

        if ($lead->last_contacted_at?->greaterThanOrEqualTo(now()->subDays($config['recent_contact_days']))) {
            $score += $config['recent_contact_points'];
            $signals[] = 'Recently contacted';
        }

        return [
            'score' => $this->clampScore($score),
            'signals' => $signals,
        ];
    }

    /**
     * @return array{score: int, signals: array<int, string>}
     */
    public function calculateOpportunityScore(Lead $lead): array
    {
        $config = config('lead_scoring.opportunity');
        $score = 0;
        $signals = [];

        if ($lead->company) {
            $score += $config['company_points'];
            $signals[] = 'Company identified';
        }

        $jobTitle = strtolower((string) $lead->job_title);
        foreach ($config['decision_maker_titles'] as $title) {
            if ($jobTitle !== '' && str_contains($jobTitle, $title)) {
                $score += $config['decision_maker_points'];
                $signals[] = 'Decision-maker title detected';
                break;
            }
        }

        if ($lead->email && $lead->phone && $lead->website) {
            $score += $config['full_contact_bundle_points'];
            $signals[] = 'Full contact bundle present';
        }

        $emailDomain = $this->emailDomain($lead->email);
        if ($emailDomain && ! $this->isFreeEmailDomain($emailDomain)) {
            $score += $config['corporate_email_points'];
            $signals[] = 'Corporate email domain';
        }

        if ($emailDomain && $lead->website_normalized && $this->domainsMatch($emailDomain, $lead->website_normalized)) {
            $score += $config['domain_match_points'];
            $signals[] = 'Email domain matches website';
        }

        if ($this->isDirectoryLead($lead)) {
            $googleSignals = $this->googleMapsOpportunitySignals($lead, $config['google_maps']);
            $score += $googleSignals['score'];
            $signals = array_merge($signals, $googleSignals['signals']);
        }

        if ($this->isRedditLead($lead)) {
            $redditSignals = $this->redditOpportunitySignals($lead, $config['reddit']);
            $score += $redditSignals['score'];
            $signals = array_merge($signals, $redditSignals['signals']);
        }

        return [
            'score' => $this->clampScore($score),
            'signals' => $signals,
        ];
    }

    /**
     * @return array{score: int, signals: array<int, string>}
     */
    public function calculateAuthenticityScore(Lead $lead): array
    {
        $config = config('lead_scoring.authenticity');
        $score = 0;
        $signals = [];

        $phoneDigits = LeadIdentifiers::normalizePhone($lead->phone);
        if ($phoneDigits && strlen($phoneDigits) >= $config['min_phone_digits']) {
            $score += $config['phone_points'];
            $signals[] = 'Valid phone number length';
        }

        $emailDomain = $this->emailDomain($lead->email);
        if ($emailDomain && ! $this->isFreeEmailDomain($emailDomain)) {
            $score += $config['corporate_email_points'];
            $signals[] = 'Non-free email provider';
        }

        if ($lead->website) {
            $score += $config['website_points'];
            $signals[] = 'Website provided';
        }

        if ($this->isDirectoryLead($lead) && $lead->company) {
            $score += $config['name_points'];
            $signals[] = 'Business name identified';
        } elseif (! $this->isGenericName($lead->first_name, $lead->last_name)) {
            $score += $config['name_points'];
            $signals[] = 'Name appears genuine';
        } else {
            $score -= $config['generic_name_penalty'];
            $signals[] = 'Generic name detected';
        }

        $source = strtolower((string) $lead->source);
        if ($source !== '' && in_array($source, $config['trusted_sources'], true)) {
            $score += $config['trusted_source_points'];
            $signals[] = "Trusted source: {$source}";
        }

        if ($emailDomain && $this->isFreeEmailDomain($emailDomain) && ! $lead->company) {
            $score -= $config['free_email_no_company_penalty'];
            $signals[] = 'Free email without company context';
        }

        if ($this->isDirectoryLead($lead)) {
            $googleSignals = $this->googleMapsAuthenticitySignals($lead, $config['google_maps']);
            $score += $googleSignals['score'];
            $signals = array_merge($signals, $googleSignals['signals']);
        }

        if ($this->isRedditLead($lead)) {
            $redditSignals = $this->redditAuthenticitySignals($lead, $config['reddit']);
            $score += $redditSignals['score'];
            $signals = array_merge($signals, $redditSignals['signals']);
        }

        return [
            'score' => $this->clampScore($score),
            'signals' => $signals,
        ];
    }

    public function calculateFinalScore(int $intentScore, int $opportunityScore, int $authenticityScore): int
    {
        $weights = config('lead_scoring.weights');

        $weighted = ($intentScore * $weights['intent'])
            + ($opportunityScore * $weights['opportunity'])
            + ($authenticityScore * $weights['authenticity']);

        return $this->clampScore((int) round($weighted));
    }

    private function clampScore(int $score): int
    {
        return max(0, min(100, $score));
    }

    private function emailDomain(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return null;
        }

        return strtolower(trim(substr($email, strrpos($email, '@') + 1)));
    }

    private function isFreeEmailDomain(string $domain): bool
    {
        return in_array($domain, config('lead_scoring.authenticity.free_email_domains'), true);
    }

    private function domainsMatch(string $emailDomain, string $websiteNormalized): bool
    {
        return $emailDomain === $websiteNormalized
            || str_ends_with($emailDomain, '.'.$websiteNormalized)
            || str_ends_with($websiteNormalized, '.'.$emailDomain);
    }

    private function isGenericName(string $firstName, string $lastName): bool
    {
        $genericNames = config('lead_scoring.authenticity.generic_names');
        $first = strtolower(trim($firstName));
        $last = strtolower(trim($lastName));

        return in_array($first, $genericNames, true)
            || in_array($last, $genericNames, true)
            || in_array(trim("{$first} {$last}"), $genericNames, true);
    }

    private function isDirectoryLead(Lead $lead): bool
    {
        return in_array(
            strtolower((string) $lead->source),
            config('lead_scoring.directory_sources', ['google_maps']),
            true,
        );
    }

    private function isGoogleMapsLead(Lead $lead): bool
    {
        return strtolower((string) $lead->source) === 'google_maps';
    }

    private function isRedditLead(Lead $lead): bool
    {
        return strtolower((string) $lead->source) === 'reddit';
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{score: int, signals: array<int, string>}
     */
    private function redditOpportunitySignals(Lead $lead, array $config): array
    {
        $score = 0;
        $signals = [];

        $leadKind = (string) data_get($lead->metadata, 'lead_kind');
        if ($leadKind !== '' && isset($config['lead_kind_points'][$leadKind])) {
            $score += $config['lead_kind_points'][$leadKind];
            $signals[] = "Reddit intent: {$leadKind}";
        }

        if ($lead->website) {
            $score += $config['website_audit_points'];
            $signals[] = 'Shared a website (audit/improvement pitch)';
        } else {
            $score += $config['no_website_points'];
            $signals[] = 'No website shared (website build opportunity)';
        }

        $comments = data_get($lead->metadata, 'comment_count');
        if (is_numeric($comments) && (int) $comments >= $config['engagement_comment_threshold']) {
            $score += $config['engagement_points'];
            $signals[] = 'Active discussion (engaged thread)';
        }

        return ['score' => $score, 'signals' => $signals];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{score: int, signals: array<int, string>}
     */
    private function redditAuthenticitySignals(Lead $lead, array $config): array
    {
        $score = 0;
        $signals = [];

        if (data_get($lead->metadata, 'author')) {
            $score += $config['author_points'];
            $signals[] = 'Identifiable Reddit author';
        }

        $upvotes = data_get($lead->metadata, 'upvotes');
        if (is_numeric($upvotes) && (int) $upvotes >= $config['upvote_threshold']) {
            $score += $config['engagement_points'];
            $signals[] = 'Post has community traction';
        }

        return ['score' => $score, 'signals' => $signals];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{score: int, signals: array<int, string>}
     */
    private function googleMapsOpportunitySignals(Lead $lead, array $config): array
    {
        $score = 0;
        $signals = [];
        $rating = data_get($lead->metadata, 'rating');
        $reviewCount = data_get($lead->metadata, 'review_count');

        if ($lead->website) {
            $score += $config['website_points'];
            $signals[] = 'Website exists (audit/SEO pitch possible)';
        } else {
            $score += $config['no_website_points'];
            $signals[] = 'No website found (website pitch opportunity)';
        }

        if ($lead->phone) {
            $score += $config['phone_points'];
            $signals[] = 'Business has reachable phone';
        }

        if (! $lead->email) {
            $score += $config['no_email_points'];
            $signals[] = 'No public email found (phone-first outreach)';
        }

        if (! $lead->website && is_numeric($reviewCount)) {
            foreach ($config['proven_demand_no_website_points'] as $threshold => $points) {
                if ((int) $reviewCount >= (int) $threshold) {
                    $score += $points;
                    $signals[] = "Strong demand without website ({$reviewCount} reviews)";
                    break;
                }
            }
        }

        if (is_numeric($reviewCount) && (int) $reviewCount < $config['low_review_count_threshold']) {
            $score += $config['low_review_count_points'];
            $signals[] = 'Low review count (reputation pitch opportunity)';
        }

        if (is_numeric($rating) && (float) $rating < $config['low_rating_threshold']) {
            $score += $config['low_rating_points'];
            $signals[] = 'Below-average rating (reputation pitch opportunity)';
        }

        return ['score' => $score, 'signals' => $signals];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{score: int, signals: array<int, string>}
     */
    private function googleMapsAuthenticitySignals(Lead $lead, array $config): array
    {
        $score = 0;
        $signals = [];
        $rating = data_get($lead->metadata, 'rating');
        $reviewCount = data_get($lead->metadata, 'review_count');

        if (data_get($lead->metadata, 'google_place_id') || data_get($lead->metadata, 'yelp_business_id') || data_get($lead->metadata, 'bing_entity_id') || data_get($lead->metadata, 'osm_id')) {
            $score += $config['place_id_points'];
            $signals[] = 'Directory business ID present';
        }

        if (data_get($lead->metadata, 'address')) {
            $score += $config['address_points'];
            $signals[] = 'Physical address found';
        }

        if (is_numeric($rating)) {
            foreach ($config['rating_points'] as $threshold => $points) {
                if ((float) $rating >= (float) $threshold) {
                    $score += $points;
                    $signals[] = "Verified Google rating: {$rating}";
                    break;
                }
            }
        }

        if (is_numeric($reviewCount)) {
            foreach ($config['review_count_points'] as $threshold => $points) {
                if ((int) $reviewCount >= (int) $threshold) {
                    $score += $points;
                    $signals[] = "Review volume: {$reviewCount}";
                    break;
                }
            }
        }

        return ['score' => $score, 'signals' => $signals];
    }
}
