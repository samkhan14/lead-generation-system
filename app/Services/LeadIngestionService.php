<?php

namespace App\Services;

use App\DataTransferObjects\LeadIngestResult;
use App\Models\Lead;
use App\Support\LeadBusinessName;
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
            return LeadIngestResult::duplicate($duplicate);
        }

        return DB::transaction(function () use ($normalized): LeadIngestResult {
            $lead = Lead::query()->create($normalized);
            $this->scoringService->score($lead);

            return LeadIngestResult::created($lead->fresh(['latestScore']));
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
                'google_place_id' => $payload['google_place_id'] ?? null,
                'address' => $payload['address'] ?? null,
                'rating' => isset($payload['rating']) ? (float) $payload['rating'] : null,
                'review_count' => isset($payload['review_count']) ? (int) $payload['review_count'] : null,
                'scrape_keyword' => $payload['scrape_keyword'] ?? null,
                'scrape_city' => $payload['scrape_city'] ?? null,
                'intent_level' => $this->intentLevelFromRating($payload['rating'] ?? null),
            ], fn ($value) => $value !== null && $value !== ''),
        );

        $notes = $payload['notes'] ?? $this->buildNotesFromMetadata($metadata, $payload);

        return [
            'first_name' => $payload['first_name'] ?? $nameParts['first_name'],
            'last_name' => $payload['last_name'] ?? $nameParts['last_name'],
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'website' => $payload['website'] ?? null,
            'company' => $nameParts['company'] ?? $payload['company'] ?? null,
            'job_title' => $payload['job_title'] ?? null,
            'source' => $payload['source'] ?? 'google_maps',
            'notes' => $notes,
            'metadata' => $metadata,
            'assigned_to' => $payload['assigned_to'] ?? null,
            'created_by' => $payload['created_by'] ?? null,
        ];
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
            $lines[] = "Google rating: {$metadata['rating']} ({$reviewCount} reviews)";
        }

        if (! empty($payload['scrape_keyword']) && ! empty($payload['scrape_city'])) {
            $lines[] = "Discovered via: {$payload['scrape_keyword']} in {$payload['scrape_city']}";
        }

        return $lines === [] ? null : implode("\n", $lines);
    }
}
