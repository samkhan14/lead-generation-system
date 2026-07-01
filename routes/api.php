<?php

use App\Http\Controllers\Api\LeadIngestController;
use App\Http\Controllers\Api\ScrapeCallbackController;
use App\Http\Controllers\Api\VoiceWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/voice/webhooks/{slug}', VoiceWebhookController::class)
    ->name('api.voice.webhooks');

Route::middleware(['ingest.token', 'throttle:ingest'])->group(function () {
    Route::post('/leads/ingest', [LeadIngestController::class, 'store'])->name('api.leads.ingest');
});

Route::middleware('ingest.token')->prefix('scrape/jobs/{scrapeJob:uuid}')->group(function () {
    Route::post('/start', [ScrapeCallbackController::class, 'start'])->name('api.scrape.start');
    Route::post('/log', [ScrapeCallbackController::class, 'log'])->name('api.scrape.log');
    Route::post('/complete', [ScrapeCallbackController::class, 'complete'])->name('api.scrape.complete');
});
