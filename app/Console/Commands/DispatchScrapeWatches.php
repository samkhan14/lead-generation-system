<?php

namespace App\Console\Commands;

use App\Enums\ScrapeJobStatus;
use App\Jobs\ProcessScrapeJob;
use App\Models\ScrapeJob;
use Illuminate\Console\Command;

class DispatchScrapeWatches extends Command
{
    protected $signature = 'scrape:watch';

    protected $description = 'Re-dispatch due scrape watch templates as fresh runs (dedupe keeps only new leads)';

    public function handle(): int
    {
        $watches = ScrapeJob::query()->watches()->get();
        $dispatched = 0;

        foreach ($watches as $watch) {
            if (! $watch->isWatchDue()) {
                continue;
            }

            $this->dispatchRun($watch);
            $watch->update(['last_dispatched_at' => now()]);
            $dispatched++;
        }

        $this->info("Dispatched {$dispatched} watch run(s) from {$watches->count()} watch(es).");

        return self::SUCCESS;
    }

    private function dispatchRun(ScrapeJob $watch): void
    {
        $run = ScrapeJob::query()->create([
            'source_channel' => $watch->source_channel,
            'is_watch' => false,
            'keyword' => $watch->keyword,
            'industry' => $watch->industry,
            'country' => $watch->country,
            'city' => $watch->city,
            'area' => $watch->area,
            'status' => ScrapeJobStatus::Pending,
            'max_results' => $watch->max_results,
            'created_by' => $watch->created_by,
        ]);

        ProcessScrapeJob::dispatch($run);
    }
}
