<?php

namespace App\Services;

use App\Jobs\VerifyLeadJob;
use App\Models\Lead;
use App\Support\GooglePlacesConfig;
use App\Support\LeadDataQuality;
use App\Support\LeadIdentifiers;
use Illuminate\Support\Facades\Http;

class LeadVerificationService
{
    public function __construct(
        private LeadScoringService $scoringService,
        private LeadEnrichmentService $enrichmentService,
    ) {}

    /**
     * @return array{updated: bool, verification: array<string, mixed>}
     */
    public function verify(Lead $lead): array
    {
        if (! $this->shouldVerify($lead)) {
            return ['updated' => false, 'verification' => $lead->metadata['verification'] ?? []];
        }

        $checks = [];
        $updates = [];
        $metadata = $lead->metadata ?? [];

        $website = $lead->website;
        $phone = $lead->phone;
        $address = data_get($metadata, 'address');
        $placeId = data_get($metadata, 'google_place_id');
        $displayName = $this->leadDisplayName($lead);

        if ($this->layerEnabled('places_api') && GooglePlacesConfig::isConfigured() && $this->canUsePlacesApi($lead)) {
            $lookup = $this->enrichmentService->lookupPlaceDetails($lead);

            $checks['places_api'] = [
                'layer' => 'places_api',
                'status' => $lookup['data'] !== null ? 'pass' : 'fail',
                'message' => $lookup['error'] ?? 'Places API matched business',
            ];

            if ($lookup['data'] !== null) {
                $data = $lookup['data'];

                if (blank($website) && filled($data['website'] ?? null)) {
                    $website = LeadIdentifiers::sanitizeBusinessWebsite($data['website']);
                    $updates['website'] = $website;
                }

                if (blank($phone) && filled($data['phone'] ?? null)) {
                    $phone = $data['phone'];
                    $updates['phone'] = $phone;
                }

                if (blank($address) && filled($data['address'] ?? null)) {
                    $address = LeadIdentifiers::sanitizeAddress($data['address']);
                    $metadata['address'] = $address;
                }

                if (filled($data['google_place_id'] ?? null)) {
                    $metadata['google_place_id'] = $data['google_place_id'];
                }
            }
        } elseif ($this->layerEnabled('places_api')) {
            $checks['places_api'] = [
                'layer' => 'places_api',
                'status' => 'skip',
                'message' => GooglePlacesConfig::isConfigured()
                    ? 'Not enough location data for Places lookup'
                    : GooglePlacesConfig::unavailableMessage(),
            ];
        }

        if ($this->layerEnabled('playwright_google_search') && blank($website) && $displayName !== null) {
            $srp = $this->verifyViaSrp($lead, $website, $phone, $address, $placeId, $displayName);

            $checks['google_search'] = $srp['check'];

            if (filled($srp['website'] ?? null)) {
                $website = LeadIdentifiers::sanitizeBusinessWebsite($srp['website']);
                $updates['website'] = $website;
            }

            if (blank($phone) && filled($srp['phone'] ?? null)) {
                $phone = $srp['phone'];
                $updates['phone'] = $phone;
            }

            foreach ($srp['extra_checks'] ?? [] as $key => $check) {
                $checks[$key] = $check;
            }
        } elseif ($this->layerEnabled('playwright_google_search') && blank($website)) {
            $checks['google_search'] = [
                'layer' => 'playwright_google_search',
                'status' => 'skip',
                'message' => 'No business name available for Google Search',
            ];
        }

        if ($this->layerEnabled('website_http') && filled($website)) {
            $checks['website_http'] = $this->checkWebsiteReachable($website);
        }

        $phoneDigits = LeadIdentifiers::normalizePhone($phone);
        $minDigits = (int) config('lead_quality.verification.min_phone_digits', 7);
        $checks['phone'] = [
            'layer' => 'validation',
            'status' => $phoneDigits && strlen($phoneDigits) >= $minDigits ? 'pass' : 'fail',
            'message' => $phoneDigits && strlen($phoneDigits) >= $minDigits
                ? 'Phone has usable digit length'
                : 'Phone missing or too short',
        ];

        if (filled($lead->email)) {
            $checks['email'] = [
                'layer' => 'validation',
                'status' => filter_var($lead->email, FILTER_VALIDATE_EMAIL) ? 'pass' : 'fail',
                'message' => filter_var($lead->email, FILTER_VALIDATE_EMAIL)
                    ? 'Email format is valid'
                    : 'Email format is invalid',
            ];
        }

        $verification = $this->buildVerificationSummary($checks);
        $metadata['verification'] = $verification;

        $merged = array_merge($lead->toArray(), $updates, ['metadata' => $metadata]);
        $metadata['data_quality'] = LeadDataQuality::assess($merged);

        $dirty = $updates !== [] || ($lead->metadata['verification'] ?? null) !== $verification;

        if ($dirty) {
            $updates['metadata'] = $metadata;
            $lead->update($updates);
            $this->scoringService->score($lead->fresh());
        }

        return [
            'updated' => $dirty,
            'verification' => $verification,
        ];
    }

    public function dispatchPendingVerifications(int $limit = 25): int
    {
        $dispatched = 0;

        Lead::query()
            ->orderBy('id')
            ->limit($limit * 3)
            ->get()
            ->each(function (Lead $lead) use (&$dispatched, $limit): void {
                if ($dispatched >= $limit) {
                    return;
                }

                if (! $this->needsVerification($lead)) {
                    return;
                }

                VerifyLeadJob::dispatch($lead);
                $dispatched++;
            });

        return $dispatched;
    }

    public function shouldVerify(Lead $lead): bool
    {
        return $this->leadDisplayName($lead) !== null;
    }

    public function needsVerification(Lead $lead): bool
    {
        if (! $this->shouldVerify($lead)) {
            return false;
        }

        $verification = $lead->metadata['verification'] ?? null;

        if (! is_array($verification)) {
            return true;
        }

        if (($verification['status'] ?? '') === 'verified') {
            return false;
        }

        return blank($lead->website)
            || blank($lead->phone)
            || ! ($verification['is_complete'] ?? false);
    }

    public function leadDisplayName(Lead $lead): ?string
    {
        $company = trim((string) ($lead->company ?? ''));
        if ($company !== '') {
            return $company;
        }

        $name = trim(trim((string) ($lead->first_name ?? '')).' '.trim((string) ($lead->last_name ?? '')));

        if ($name === '' || strcasecmp($name, 'Unknown Lead') === 0) {
            return null;
        }

        return $name;
    }

    private function canUsePlacesApi(Lead $lead): bool
    {
        if (filled(data_get($lead->metadata, 'google_place_id'))) {
            return true;
        }

        return $this->leadDisplayName($lead) !== null
            && (
                filled(data_get($lead->metadata, 'address'))
                || filled(data_get($lead->metadata, 'scrape_city'))
                || filled(data_get($lead->metadata, 'scrape_country'))
            );
    }

    /**
     * @param  array<string, array{layer: string, status: string, message: string}>  $checks
     * @return array<string, mixed>
     */
    private function buildVerificationSummary(array $checks): array
    {
        $failed = collect($checks)->where('status', 'fail')->count();
        $passed = collect($checks)->where('status', 'pass')->count();

        $status = match (true) {
            $failed === 0 && $passed > 0 => 'verified',
            $passed > 0 => 'partial',
            default => 'unverified',
        };

        return [
            'status' => $status,
            'is_complete' => $failed === 0 && $passed > 0,
            'checks' => $checks,
            'verified_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{
     *   website: ?string,
     *   phone: ?string,
     *   check: array{layer: string, status: string, message: string},
     *   extra_checks: array<string, array{layer: string, status: string, message: string}>
     * }
     */
    private function verifyViaSrp(
        Lead $lead,
        ?string $website,
        ?string $phone,
        ?string $address,
        mixed $placeId,
        string $displayName,
    ): array {
        $serviceUrl = rtrim((string) config('scraper.service_url'), '/');
        $timeout = (int) config('lead_quality.verification.srp_timeout', 90);

        try {
            $response = Http::timeout($timeout)->post("{$serviceUrl}/verify", [
                'company' => $displayName,
                'phone' => $phone,
                'website' => $website,
                'address' => $address,
                'city' => data_get($lead->metadata, 'scrape_city'),
                'country' => data_get($lead->metadata, 'scrape_country'),
                'google_place_id' => $placeId,
                'source' => $lead->source,
            ]);
        } catch (\Throwable $e) {
            return [
                'website' => null,
                'phone' => null,
                'check' => [
                    'layer' => 'playwright_google_search',
                    'status' => 'fail',
                    'message' => "SRP verify unreachable: {$e->getMessage()}",
                ],
                'extra_checks' => [],
            ];
        }

        if (! $response->successful()) {
            return [
                'website' => null,
                'phone' => null,
                'check' => [
                    'layer' => 'playwright_google_search',
                    'status' => 'fail',
                    'message' => "SRP verify failed (HTTP {$response->status()}): {$response->body()}",
                ],
                'extra_checks' => [],
            ];
        }

        $data = $response->json();
        $srpChecks = is_array($data['checks'] ?? null) ? $data['checks'] : [];
        $searchCheck = $srpChecks['google_search'] ?? [
            'layer' => 'playwright_google_search',
            'status' => filled($data['website'] ?? null) ? 'pass' : 'fail',
            'message' => filled($data['website'] ?? null)
                ? 'Google Search discovered website via SRP'
                : 'Google Search did not find a website',
        ];

        unset($srpChecks['google_search']);

        return [
            'website' => $data['website'] ?? null,
            'phone' => $data['phone'] ?? null,
            'check' => $searchCheck,
            'extra_checks' => $srpChecks,
        ];
    }

    /**
     * @return array{layer: string, status: string, message: string}
     */
    private function checkWebsiteReachable(string $website): array
    {
        $url = str_starts_with($website, 'http') ? $website : "https://{$website}";

        try {
            $response = Http::timeout(8)->withOptions(['allow_redirects' => true])->head($url);

            if ($response->successful() || in_array($response->status(), [401, 403, 405], true)) {
                return [
                    'layer' => 'website_http',
                    'status' => 'pass',
                    'message' => "Website responded (HTTP {$response->status()})",
                ];
            }

            $get = Http::timeout(8)->withOptions(['allow_redirects' => true])->get($url);
            if ($get->successful()) {
                return [
                    'layer' => 'website_http',
                    'status' => 'pass',
                    'message' => "Website responded via GET (HTTP {$get->status()})",
                ];
            }

            return [
                'layer' => 'website_http',
                'status' => 'fail',
                'message' => "Website unreachable (HTTP {$get->status()})",
            ];
        } catch (\Throwable $e) {
            return [
                'layer' => 'website_http',
                'status' => 'fail',
                'message' => "Website check failed: {$e->getMessage()}",
            ];
        }
    }

    private function layerEnabled(string $layer): bool
    {
        return in_array($layer, config('lead_quality.verification.layers', []), true);
    }
}
