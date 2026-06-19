<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Re-dispatch due scrape watch templates. The command checks each watch's own
// interval, so running hourly is safe and cheap.
Schedule::command('scrape:watch')->hourly()->withoutOverlapping();
Schedule::command('scrape:fail-stale')->everyFifteenMinutes()->withoutOverlapping();

// Retry leads that never finished verification (no manual command needed).
Schedule::call(function (): void {
    app(\App\Services\LeadVerificationService::class)->dispatchPendingVerifications(25);
})->dailyAt('02:30')->name('leads-retry-verification')->withoutOverlapping();
