<?php

namespace App\Services;

use App\Models\Lead;
use App\Support\GooglePlacesConfig;
use App\Support\LeadDataQuality;
use App\Support\LeadIdentifiers;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class LeadEnrichmentService
{
    public function __construct(
        private LeadScoringService $scoringService,
    ) {}

  /**
   * @return array{
   *   updated: int,
   *   skipped: int,
   *   failed: int,
   *   status: 'completed'|'unavailable',
   *   message: ?string,
   *   errors: array<int, string>
   * }
   */
    public function enrichDirectoryLeads(bool $dryRun = false, ?int $limit = null): array
    {
        if (! GooglePlacesConfig::isConfigured()) {
            return [
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'status' => 'unavailable',
                'message' => GooglePlacesConfig::unavailableMessage(),
                'errors' => [],
            ];
        }

        $apiKey = (string) config('google_places.api_key');
        $stats = [
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'status' => 'completed',
            'message' => null,
            'errors' => [],
        ];

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
                $result = $this->fetchPlaceDetails($apiKey, $lead);

                if ($result['data'] === null) {
                    $stats['skipped']++;
                    if ($result['error'] !== null) {
                        $stats['errors'][] = "{$lead->company}: {$result['error']}";
                    }

                    continue;
                }

                $details = $result['data'];

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
            } catch (\Throwable $e) {
                $stats['failed']++;
                $stats['errors'][] = "{$lead->company}: {$e->getMessage()}";
            }
        }

        return $stats;
    }

    /**
     * @return array{data: ?array{website: ?string, phone: ?string, address: ?string, google_place_id: ?string}, error: ?string}
     */
    private function fetchPlaceDetails(string $apiKey, Lead $lead): array
    {
        $placeId = LeadIdentifiers::normalizeGooglePlaceId(data_get($lead->metadata, 'google_place_id'));

        if ($placeId !== null) {
            $fromId = $this->requestPlaceDetails($apiKey, $placeId);

            if ($fromId['data'] !== null) {
                return $fromId;
            }

            $lastError = $fromId['error'];
        } else {
            $lastError = null;
        }

        $query = trim(implode(' ', array_filter([
            $lead->company,
            data_get($lead->metadata, 'address'),
            data_get($lead->metadata, 'scrape_city'),
            data_get($lead->metadata, 'scrape_country'),
        ])));

        if ($query === '') {
            return [
                'data' => null,
                'error' => $lastError ?? 'No place ID or searchable name/address on this lead',
            ];
        }

        $response = Http::withHeaders([
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => 'places.id,places.websiteUri,places.nationalPhoneNumber,places.formattedAddress',
        ])->post((string) config('google_places.search_url'), [
            'textQuery' => $query,
            'pageSize' => 1,
        ]);

        if (! $response->successful()) {
            return [
                'data' => null,
                'error' => $this->formatPlacesError($response, $lastError),
            ];
        }

        $place = $response->json('places.0');

        if (! is_array($place)) {
            return [
                'data' => null,
                'error' => $lastError ?? 'Places API text search returned no match',
            ];
        }

        return [
            'data' => [
                'website' => $place['websiteUri'] ?? null,
                'phone' => $place['nationalPhoneNumber'] ?? null,
                'address' => $place['formattedAddress'] ?? null,
                'google_place_id' => isset($place['id']) ? str_replace('places/', '', (string) $place['id']) : null,
            ],
            'error' => null,
        ];
    }

    /**
     * @return array{data: ?array{website: ?string, phone: ?string, address: ?string, google_place_id: ?string}, error: ?string}
     */
    private function requestPlaceDetails(string $apiKey, string $placeId): array
    {
        $url = rtrim((string) config('google_places.details_url'), '/').'/'.urlencode($placeId);

        $response = Http::withHeaders([
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => 'websiteUri,nationalPhoneNumber,formattedAddress,id',
        ])->get($url);

        if (! $response->successful()) {
            return [
                'data' => null,
                'error' => $this->formatPlacesError($response),
            ];
        }

        $data = $response->json();

        if (! is_array($data)) {
            return [
                'data' => null,
                'error' => 'Places API returned an invalid response',
            ];
        }

        return [
            'data' => [
                'website' => $data['websiteUri'] ?? null,
                'phone' => $data['nationalPhoneNumber'] ?? null,
                'address' => $data['formattedAddress'] ?? null,
                'google_place_id' => isset($data['id']) ? str_replace('places/', '', (string) $data['id']) : $placeId,
            ],
            'error' => null,
        ];
    }

    private function formatPlacesError(Response $response, ?string $previous = null): string
    {
        $body = $response->json();
        $apiMessage = is_array($body)
            ? ($body['error']['message'] ?? $body['message'] ?? null)
            : null;

        $message = $apiMessage
            ? "Places API error ({$response->status()}): {$apiMessage}"
            : "Places API error ({$response->status()}): {$response->body()}";

        return $previous ? "{$previous}; fallback text search — {$message}" : $message;
    }
}
