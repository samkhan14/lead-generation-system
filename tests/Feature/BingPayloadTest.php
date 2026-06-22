<?php

use App\Models\ScrapeJob;
use App\Support\ScraperChannels;

it('includes azure maps settings in bing places srp payload', function () {
    $job = ScrapeJob::factory()->make(['source_channel' => 'bing_places']);

    $payload = ScraperChannels::payloadFor($job);

    expect($payload['bing']['api_base_url'] ?? null)
        ->toBe('https://atlas.microsoft.com')
        ->and($payload['bing']['search_path'] ?? null)
        ->toBe('/search/fuzzy/json')
        ->and($payload['bing']['entity_type'] ?? null)
        ->toBe('POI');
});

it('bing places is a runnable warm channel', function () {
    expect(ScraperChannels::runnableKeys())->toContain('bing_places');
    expect(ScraperChannels::isImplemented('bing_places'))->toBeTrue();
    expect(ScraperChannels::tier('bing_places'))->toBe('warm');
});
