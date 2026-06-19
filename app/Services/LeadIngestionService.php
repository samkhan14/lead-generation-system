<?php

namespace App\Services;

use App\DataTransferObjects\LeadIngestResult;
use App\Jobs\VerifyLeadJob;
use App\Models\Lead;
use App\Support\LeadBusinessName;
use App\Support\LeadDataQuality;
use App\Support\LeadIdentifiers;
use Illuminate\Support\Facades\DB;

class LeadIngestionService
{
    public function __construct(
        private LeadScoringService $scoringService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingest(array $payload): LeadIngestResult
    {
        $normalized = $this->normalizePayload($payload);

        if ($duplicate = $this->findDuplicate($normalized)) {
            $merged = $this->mergeDirectoryLeadIfImproved($duplicate, $normalized);
            $this->dispatchVerificationIfNeeded($merged);

            return LeadIngestResult::duplicate($merged);
        }

        return DB::transaction(function () use ($normalized): LeadIngestResult {
            $lead = Lead::query()->create($normalized);
            $this->scoringService->score($lead);
            $fresh = $lead->fresh(['latestScore']);
            $this->dispatchVerificationIfNeeded($fresh);

            return LeadIngestResult::created($fresh);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizePayload(array $payload): array
    {
        $businessName = trim((string) ($payload['business_name'] ?? $payload['company'] ?? ''));

        if ($businessName !== '') {
            $nameParts = LeadBusinessName::fromBusinessName($businessName);
        } else {
            $nameParts = [
                'company' => $payload['company'] ?? null,
                'first_name' => $payload['first_name'] ?? 'Unknown',
                'last_name' => $payload['last_name'] ?? 'Lead',
            ];
        }

        $metadata = array_merge(
            is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
            array_filter([
                'google_place_id' => LeadIdentifiers::normalizeGooglePlaceId($payload['google_place_id'] ?? null),
                'yelp_business_id' => $payload['yelp_business_id'] ?? null,
                'yelp_url' => $payload['yelp_url'] ?? null,
                'osm_id' => $payload['osm_id'] ?? null,
                'osm_type' => $payload['osm_type'] ?? null,
                'osm_url' => $payload['osm_url'] ?? null,
                'address' => LeadIdentifiers::sanitizeAddress($payload['address'] ?? null),
                'rating' => isset($payload['rating']) ? (float) $payload['rating'] : null,
                'review_count' => isset($payload['review_count']) ? (int) $payload['review_count'] : null,
                'scrape_keyword' => $payload['scrape_keyword'] ?? null,
                'scrape_country' => $payload['scrape_country'] ?? null,
                'scrape_city' => $payload['scrape_city'] ?? null,
                'scrape_area' => $payload['scrape_area'] ?? null,
                'scrape_industry' => $payload['scrape_industry'] ?? null,
                'scrape_job_uuid' => $payload['scrape_job_uuid'] ?? null,
                'intent_level' => $this->intentLevelFromRating($payload['rating'] ?? null),
            ], fn ($value) => $value !== null && $value !== ''),
        );

        $notes = $payload['notes'] ?? $this->buildNotesFromMetadata($metadata, $payload);

        $normalized = [
            'first_name' => $payload['first_name'] ?? $nameParts['first_name'],
            'last_name' => $payload['last_name'] ?? $nameParts['last_name'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'website' => LeadIdentifiers::sanitizeBusinessWebsite($payload['website'] ?? null),
            'company' => $nameParts['company'] ?? $payload['company'] ?? null,
            'job_title' => $payload['job_title'] ?? null,
            'source' => $payload['source'] ?? 'google_maps',
            'notes' => $notes,
            'metadata' => $metadata,
            'assigned_to' => $payload['assigned_to'] ?? null,
            'created_by' => $payload['created_by'] ?? null,
        ];

        $normalized['metadata']['data_quality'] = LeadDataQuality::assess($normalized);

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $normalized
     */
    public function mergeDirectoryLeadIfImproved(Lead $existing, array $normalized): Lead
    {
        $directorySources = config('lead_quality.directory_sources', []);

        if (! in_array($existing->source, $directorySources, true)) {
            return $existing;
        }

        $updates = [];
        $metadata = $existing->metadata ?? [];

        if (blank($existing->website) && filled($normalized['website'] ?? null)) {
            $updates['website'] = $normalized['website'];
        }

        if (blank($existing->phone) && filled($normalized['phone'] ?? null)) {
            $updates['phone'] = $normalized['phone'];
        }

        foreach (['address', 'rating', 'review_count', 'yelp_url', 'osm_url'] as $key) {
            $incoming = data_get($normalized, "metadata.{$key}");

            if (filled($incoming) && blank($metadata[$key] ?? null)) {
                $metadata[$key] = $incoming;
            }
        }

        $incomingPlaceId = data_get($normalized, 'metadata.google_place_id');
        $existingPlaceId = $metadata['google_place_id'] ?? null;

        if (filled($incomingPlaceId) && ($this->isMalformedGooglePlaceId($existingPlaceId) || blank($existingPlaceId))) {
            $metadata['google_place_id'] = $incomingPlaceId;
        }

        $mergedForQuality = array_merge($existing->toArray(), $updates, [
            'metadata' => array_merge($metadata, array_diff_key($normalized['metadata'] ?? [], ['data_quality' => true])),
        ]);
        $metadata['data_quality'] = LeadDataQuality::assess($mergedForQuality);

        $originalMetadata = $existing->metadata ?? [];

        if ($updates === [] && $metadata === $originalMetadata) {
            return $existing;
        }

        $updates['metadata'] = $metadata;

        $existing->update($updates);
        $this->scoringService->score($existing->fresh());

        return $existing->fresh(['latestScore']);
    }

    private function isMalformedGooglePlaceId(mixed $placeId): bool
    {
        if (! is_string($placeId) || $placeId === '') {
            return true;
        }

        return str_contains($placeId, 'data=') || str_contains($placeId, '!4m');
    }

    /**
     * @param  array<string, mixed>  $normalized
     */
    public function findDuplicate(array $normalized): ?Lead
    {
        $placeId = data_get($normalized, 'metadata.google_place_id');

        if ($placeId) {
            $existing = Lead::query()
                ->where('metadata->google_place_id', $placeId)
                ->first();

            if ($existing) {
                return $existing;
            }

            if (str_starts_with($placeId, 'ChIJ')) {
                $existing = Lead::query()
                    ->where('metadata->google_place_id', 'like', '%'.$placeId.'%')
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }
        }

        $redditPostId = data_get($normalized, 'metadata.reddit_post_id');

        if ($redditPostId) {
            $existing = Lead::query()
                ->where('metadata->reddit_post_id', $redditPostId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $yelpBusinessId = data_get($normalized, 'metadata.yelp_business_id');

        if ($yelpBusinessId) {
            $existing = Lead::query()
                ->where('metadata->yelp_business_id', $yelpBusinessId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $osmId = data_get($normalized, 'metadata.osm_id');

        if ($osmId) {
            $existing = Lead::query()
                ->where('metadata->osm_id', $osmId)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return Lead::findDuplicate(
            $normalized['email'] ?? null,
            $normalized['phone'] ?? null,
            $normalized['website'] ?? null,
        );
    }

    private function intentLevelFromRating(mixed $rating): ?string
    {
        if ($rating === null || $rating === '') {
            return null;
        }

        $rating = (float) $rating;

        return match (true) {
            $rating >= 4.5 => 'high',
            $rating >= 4.0 => 'medium',
            default => 'low',
        };
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $payload
     */
    private function buildNotesFromMetadata(array $metadata, array $payload): ?string
    {
        $lines = [];

        if (! empty($metadata['address'])) {
            $lines[] = 'Address: '.$metadata['address'];
        }

        if (! empty($metadata['rating'])) {
            $reviewCount = $metadata['review_count'] ?? 0;
            $sourceLabel = match ($payload['source'] ?? 'google_maps') {
                'yelp' => 'Yelp',
                'openstreetmap' => 'OSM',
                default => 'Google',
            };
            $lines[] = "{$sourceLabel} rating: {$metadata['rating']} ({$reviewCount} reviews)";
        }

        if (! empty($payload['scrape_keyword']) && ! empty($payload['scrape_city'])) {
            $lines[] = "Discovered via: {$payload['scrape_keyword']} in {$payload['scrape_city']}";
        }

        return $lines === [] ? null : implode("\n", $lines);
    }

    private function dispatchVerificationIfNeeded(Lead $lead): void
    {
        if (! app(LeadVerificationService::class)->shouldVerify($lead)) {
            return;
        }

        VerifyLeadJob::dispatch($lead)->afterCommit();
    }
}
