<?php

namespace App\Jobs;

use App\Enums\ScrapeJobStatus;
use App\Models\ScrapeJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessScrapeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public readonly ScrapeJob $scrapeJob) {}

    public function handle(): void
    {
        $job = $this->scrapeJob;

        $serviceUrl = rtrim((string) config('scraper.service_url'), '/');
        $timeout = (int) config('scraper.dispatch_timeout', 10);
        $callbackBase = rtrim((string) config('app.url'), '/');

        $payload = [
            'uuid' => $job->uuid,
            'source_channel' => $job->source_channel,
            'keyword' => $job->keyword,
            'industry' => $job->industry,
            'country' => $job->country,
            'city' => $job->city,
            'area' => $job->area,
            'max_results' => $job->max_results,
            'ingest_url' => "{$callbackBase}/api/leads/ingest",
            'callback_start' => "{$callbackBase}/api/scrape/jobs/{$job->uuid}/start",
            'callback_log' => "{$callbackBase}/api/scrape/jobs/{$job->uuid}/log",
            'callback_complete' => "{$callbackBase}/api/scrape/jobs/{$job->uuid}/complete",
            'ingest_token' => config('ingest.token'),
        ];

        if ($job->source_channel === 'reddit') {
            $payload['reddit'] = [
                'subreddits' => $this->resolveSubreddits($job),
                'time_filter' => config('reddit.time_filter'),
                'max_age_days' => config('reddit.max_age_days'),
                'min_post_length' => config('reddit.min_post_length'),
                'exclude_flairs' => config('reddit.exclude_flairs'),
                'exclude_keywords' => config('reddit.exclude_keywords'),
                'lead_kinds' => config('reddit.lead_kinds'),
            ];
        }

        try {
            $response = Http::timeout($timeout)->post("{$serviceUrl}/run", $payload);

            if (! $response->successful()) {
                $this->markFailed($job, "SRP service rejected job: HTTP {$response->status()}");
            }
        } catch (\Throwable $e) {
            Log::error('ScrapeJob dispatch failed', [
                'uuid' => $job->uuid,
                'error' => $e->getMessage(),
            ]);
            $this->markFailed($job, "Could not reach scraper service: {$e->getMessage()}");
        }
    }

    /**
     * Resolve default subreddits for a Reddit job by country (merged with global
     * defaults). The SRP connector still lets the Industry field override these.
     *
     * @return array<int, string>
     */
    private function resolveSubreddits(ScrapeJob $job): array
    {
        $country = strtolower(trim((string) $job->country));
        $countryPack = config("reddit.country_subreddits.{$country}", []);

        return array_values(array_unique(array_merge(
            is_array($countryPack) ? $countryPack : [],
            (array) config('reddit.default_subreddits'),
        )));
    }

    private function markFailed(ScrapeJob $job, string $reason): void
    {
        $job->update([
            'status' => ScrapeJobStatus::Failed,
            'completed_at' => now(),
            'error_message' => $reason,
        ]);
    }
}
