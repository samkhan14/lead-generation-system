<?php

namespace App\Services;

use App\Models\Lead;
use App\Support\LeadDataQuality;
use App\Support\LeadIdentifiers;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class LeadEnrichmentService
{
    public function __construct(
        private LeadScoringService $scoringService,
    ) {}

    /**
     * @return array{updated: int, skipped: int, failed: int}
     */
    public function enrichDirectoryLeads(bool $dryRun = false, ?int $limit = null): array
    {
        $apiKey = config('google_places.api_key');

        if (blank($apiKey)) {
            throw new \RuntimeException('GOOGLE_PLACES_API_KEY is not configured in .env');
        }

        $stats = ['updated' => 0, 'skipped' => 0, 'failed' => 0];

        $query = Lead::query()
            ->whereIn('source', config('lead_quality.directory_sources', []))
            ->where(function ($builder): void {
                $builder->whereNull('website')->orWhere('website', '=', '');
            });

        if ($limit !== null) {
            $query->limit($limit);
        }

        /** @var Collection<int, Lead> $leads */
        $leads = $query->get();

        foreach ($leads as $lead) {
            try {
                $details = $this->fetchPlaceDetails($apiKey, $lead);

                if ($details === null) {
                    $stats['skipped']++;

                    continue;
                }

                if ($dryRun) {
                    $stats['updated']++;

                    continue;
                }

                $updates = [];
                $metadata = $lead->metadata ?? [];

                if (blank($lead->website) && filled($details['website'] ?? null)) {
                    $updates['website'] = LeadIdentifiers::sanitizeBusinessWebsite($details['website']);
                }

                if (blank($lead->phone) && filled($details['phone'] ?? null)) {
                    $updates['phone'] = $details['phone'];
                }

                if (blank($metadata['address'] ?? null) && filled($details['address'] ?? null)) {
                    $metadata['address'] = LeadIdentifiers::sanitizeAddress($details['address']);
                }

                if (filled($details['google_place_id'] ?? null)) {
                    $metadata['google_place_id'] = $details['google_place_id'];
                }

                $merged = array_merge($lead->toArray(), $updates, ['metadata' => $metadata]);
                $metadata['data_quality'] = LeadDataQuality::assess($merged);
                $updates['metadata'] = $metadata;

                if ($updates === []) {
                    $stats['skipped']++;

                    continue;
                }

                $lead->update($updates);
                $this->scoringService->score($lead->fresh());
                $stats['updated']++;
            } catch (\Throwable) {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * @return array{website: ?string, phone: ?string, address: ?string, google_place_id: ?string}|null
     */
    private function fetchPlaceDetails(string $apiKey, Lead $lead): ?array
    {
        $placeId = LeadIdentifiers::normalizeGooglePlaceId(data_get($lead->metadata, 'google_place_id'));

        if ($placeId !== null) {
            $fromId = $this->requestPlaceDetails($apiKey, $placeId);

            if ($fromId !== null) {
                return $fromId;
            }
        }

        $query = trim(implode(' ', array_filter([
            $lead->company,
            data_get($lead->metadata, 'address'),
            data_get($lead->metadata, 'scrape_city'),
            data_get($lead->metadata, 'scrape_country'),
        ])));

        if ($query === '') {
            return null;
        }

        $response = Http::withHeaders([
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => 'places.id,places.websiteUri,places.nationalPhoneNumber,places.formattedAddress',
        ])->post('https://places.googleapis.com/v1/places:searchText', [
            'textQuery' => $query,
            'pageSize' => 1,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $place = $response->json('places.0');

        if (! is_array($place)) {
            return null;
        }

        return [
            'website' => $place['websiteUri'] ?? null,
            'phone' => $place['nationalPhoneNumber'] ?? null,
            'address' => $place['formattedAddress'] ?? null,
            'google_place_id' => isset($place['id']) ? str_replace('places/', '', (string) $place['id']) : null,
        ];
    }

    /**
     * @return array{website: ?string, phone: ?string, address: ?string, google_place_id: ?string}|null
     */
    private function requestPlaceDetails(string $apiKey, string $placeId): ?array
    {
        $url = rtrim((string) config('google_places.details_url'), '/').'/'.urlencode($placeId);

        $response = Http::withHeaders([
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => 'websiteUri,nationalPhoneNumber,formattedAddress,id',
        ])->get($url);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (! is_array($data)) {
            return null;
        }

        return [
            'website' => $data['websiteUri'] ?? null,
            'phone' => $data['nationalPhoneNumber'] ?? null,
            'address' => $data['formattedAddress'] ?? null,
            'google_place_id' => isset($data['id']) ? str_replace('places/', '', (string) $data['id']) : $placeId,
        ];
    }
}
