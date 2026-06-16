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
