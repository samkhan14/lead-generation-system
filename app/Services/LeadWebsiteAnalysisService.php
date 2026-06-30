<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\Http;

class LeadWebsiteAnalysisService
{
    /**
     * Call SRP /analyze-website and return a shaped result.
     *
     * @return array{
     *   check: array{layer: string, status: string, message: string},
     *   data: array<string, mixed>|null
     * }
     */
    public function analyze(Lead $lead, string $website): array
    {
        $serviceUrl = rtrim((string) config('scraper.service_url'), '/');
        $timeout = (int) config('lead_quality.website_analysis.timeout', 120);

        $payload = [
            'url' => $website,
            'company' => (string) ($lead->company ?: "{$lead->first_name} {$lead->last_name}"),
            'city' => data_get($lead->metadata, 'scrape_city'),
            'country' => data_get($lead->metadata, 'scrape_country'),
        ];

        try {
            $response = Http::timeout($timeout)->post("{$serviceUrl}/analyze-website", $payload);
        } catch (\Throwable $e) {
            return $this->errorResult("SRP analyze-website unreachable: {$e->getMessage()}");
        }

        if (! $response->successful()) {
            return $this->errorResult("SRP analyze-website failed (HTTP {$response->status()}): {$response->body()}");
        }

        $data = $response->json();
        $exists = (bool) ($data['exists'] ?? false);
        $qualityScore = $data['quality_score'] ?? 0;
        $revampPotential = $data['revamp_potential'] ?? 'unknown';

        return [
            'check' => [
                'layer' => 'website_analysis',
                'status' => $exists ? 'pass' : 'fail',
                'message' => $exists
                    ? "Website analyzed — quality score: {$qualityScore}, revamp: {$revampPotential}"
                    : 'Website analysis: site unavailable or unreadable — '.($data['error'] ?? 'no details'),
            ],
            'data' => $data,
        ];
    }

    /**
     * @return array{check: array{layer: string, status: string, message: string}, data: null}
     */
    private function errorResult(string $message): array
    {
        return [
            'check' => [
                'layer' => 'website_analysis',
                'status' => 'fail',
                'message' => $message,
            ],
            'data' => null,
        ];
    }
}
