<?php

namespace App\Jobs;

use App\Enums\ScrapeJobStatus;
use App\Models\ScrapeJob;
use App\Support\ScraperChannels;
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
        $channel = $job->source_channel ?: config('scraper.default_channel');

        if (! ScraperChannels::isEnabled($channel)) {
            $this->markFailed($job, "Source channel \"{$channel}\" is disabled.");

            return;
        }

        if (! ScraperChannels::isImplemented($channel)) {
            $this->markFailed($job, "Source channel \"{$channel}\" is not implemented yet.");

            return;
        }

        $serviceUrl = rtrim((string) config('scraper.service_url'), '/');
        $timeout = (int) config('scraper.dispatch_timeout', 10);
        $callbackBase = rtrim((string) config('scraper.callback_url'), '/');

        try {
            $health = Http::timeout(3)->get("{$serviceUrl}/health");

            if (! $health->successful()) {
                $this->markFailed($job, "Scraper service unhealthy at {$serviceUrl} (HTTP {$health->status()}). Start it with: npm run dev");

                return;
            }
        } catch (\Throwable $e) {
            Log::error('ScrapeJob SRP health check failed', [
                'uuid' => $job->uuid,
                'service_url' => $serviceUrl,
                'error' => $e->getMessage(),
            ]);
            $this->markFailed(
                $job,
                "Scraper service not reachable at {$serviceUrl}. Run the SRP service (npm run dev) and verify SRP_SERVICE_URL.",
            );

            return;
        }

        $payload = [
            'uuid' => $job->uuid,
            'source_channel' => $channel,
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
            ...ScraperChannels::payloadFor($job),
        ];

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

    private function markFailed(ScrapeJob $job, string $reason): void
    {
        $job->update([
            'status' => ScrapeJobStatus::Failed,
            'completed_at' => now(),
            'error_message' => $reason,
        ]);
    }
}
