<?php

use App\Jobs\ProcessScrapeJob;
use App\Models\ScrapeJob;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\artisan;

test('due watch dispatches a fresh run and records dispatch time', function () {
    Queue::fake();

    $watch = ScrapeJob::factory()->create([
        'is_watch' => true,
        'source_channel' => 'reddit',
        'keyword' => 'need a website',
        'last_dispatched_at' => null,
    ]);

    artisan('scrape:watch')->assertSuccessful();

    Queue::assertPushed(ProcessScrapeJob::class);

    expect($watch->fresh()->last_dispatched_at)->not->toBeNull()
        ->and(ScrapeJob::query()->where('is_watch', false)->where('keyword', 'need a website')->count())->toBe(1);
});

test('watch within its interval is not re-dispatched', function () {
    Queue::fake();

    ScrapeJob::factory()->create([
        'is_watch' => true,
        'source_channel' => 'reddit',
        'watch_interval_hours' => 12,
        'last_dispatched_at' => now()->subHours(2),
    ]);

    artisan('scrape:watch')->assertSuccessful();

    Queue::assertNothingPushed();
});

test('non-watch jobs are ignored by the watcher', function () {
    Queue::fake();

    ScrapeJob::factory()->create(['is_watch' => false]);

    artisan('scrape:watch')->assertSuccessful();

    Queue::assertNothingPushed();
});
