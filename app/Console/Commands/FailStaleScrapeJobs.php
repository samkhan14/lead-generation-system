<?php

namespace App\Console\Commands;

use App\Enums\ScrapeJobStatus;
use App\Models\ScrapeJob;
use Illuminate\Console\Command;

class FailStaleScrapeJobs extends Command
{
    protected $signature = 'scrape:fail-stale';

    protected $description = 'Mark scrape jobs stuck in running state as failed (SRP crash or timeout)';

    public function handle(): int
    {
        $minutes = (int) config('scraper.stale_running_minutes', 45);
        $cutoff = now()->subMinutes($minutes);

        $stale = ScrapeJob::query()
            ->where('status', ScrapeJobStatus::Running)
            ->where(function ($query) use ($cutoff) {
                $query->where('started_at', '<=', $cutoff)
                    ->orWhere(function ($q) use ($cutoff) {
                        $q->whereNull('started_at')
                            ->where('updated_at', '<=', $cutoff);
                    });
            })
            ->get();

        foreach ($stale as $job) {
            $job->update([
                'status' => ScrapeJobStatus::Failed,
                'completed_at' => now(),
                'error_message' => "Job timed out after {$minutes} minutes. Check SRP service logs — Playwright may have hung or callbacks failed (verify SCRAPER_CALLBACK_URL).",
            ]);
        }

        $this->info("Marked {$stale->count()} stale running job(s) as failed.");

        return self::SUCCESS;
    }
}
