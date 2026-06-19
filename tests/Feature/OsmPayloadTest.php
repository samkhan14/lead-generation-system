<?php

use App\Models\ScrapeJob;
use App\Support\OpenStreetMapConfig;
use App\Support\ScraperChannels;

it('builds osm user agent from env contact email and app url', function () {
    config([
        'app.name' => 'TestApp',
        'app.url' => 'http://testapp.test',
    ]);

    putenv('OSM_USER_AGENT=');
    putenv('OSM_CONTACT_EMAIL=devpublic14@gmail.com');

    expect(OpenStreetMapConfig::userAgent())
        ->toBe('testapp-lead-scraper/1.0 (devpublic14@gmail.com; +http://testapp.test)');
});

it('includes user agent in openstreetmap srp payload', function () {
    putenv('OSM_CONTACT_EMAIL=devpublic14@gmail.com');
    putenv('OSM_USER_AGENT=');

    $job = ScrapeJob::factory()->make(['source_channel' => 'openstreetmap']);

    $payload = ScraperChannels::payloadFor($job);

    expect($payload['openstreetmap']['user_agent'] ?? null)
        ->toBeString()
        ->toContain('devpublic14@gmail.com')
        ->not->toContain('example.com');
});
