<?php

namespace App\Console\Commands;

use App\Services\LeadEnrichmentService;
use App\Support\GooglePlacesConfig;
use Illuminate\Console\Command;

class EnrichDirectoryLeads extends Command
{
    protected $signature = 'leads:enrich-directory {--dry-run : Preview without saving} {--limit= : Max leads to process}';

    protected $description = 'Backfill missing websites/phones for directory leads via Google Places API';

    public function handle(LeadEnrichmentService $enrichmentService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        try {
            $stats = $enrichmentService->enrichDirectoryLeads($dryRun, $limit);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (($stats['status'] ?? 'completed') === 'unavailable') {
            $this->warn($stats['message'] ?? GooglePlacesConfig::unavailableMessage());

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%s — updated: %d, skipped: %d, failed: %d',
            $dryRun ? 'Dry run' : 'Done',
            $stats['updated'],
            $stats['skipped'],
            $stats['failed'],
        ));

        foreach ($stats['errors'] ?? [] as $error) {
            $this->line("  - {$error}");
        }

        return self::SUCCESS;
    }
}
