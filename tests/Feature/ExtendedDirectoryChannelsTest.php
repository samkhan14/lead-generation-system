<?php

use App\Models\Lead;
use App\Services\LeadIngestionService;
use App\Services\LeadScoringService;
use App\Support\ScraperChannels;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function listingUrlField(string $idKey): string
{
    return match (true) {
        str_ends_with($idKey, '_listing_id') => str_replace('_listing_id', '_url', $idKey),
        str_ends_with($idKey, '_business_id') => str_replace('_business_id', '_url', $idKey),
        str_ends_with($idKey, '_place_id') => str_replace('_place_id', '_url', $idKey),
        str_ends_with($idKey, '_profile_id') => str_replace('_profile_id', '_url', $idKey),
        default => $idKey.'_url',
    };
}

dataset('extended_directory_channels', [
    'yellow_pages' => ['yellow_pages', 'yellow_pages_listing_id', '12345678', 'https://www.yellowpages.com/mip/test-12345678'],
    'manta' => ['manta', 'manta_business_id', 'acme-plumbing-ny', 'https://www.manta.com/c/acme-plumbing-ny'],
    'foursquare' => ['foursquare', 'foursquare_place_id', '4b1234567890abcd', 'https://foursquare.com/v/4b1234567890abcd'],
    'the_manifest' => ['the_manifest', 'manifest_profile_id', 'acme-agency', 'https://themanifest.com/company/acme-agency'],
    'goodfirms' => ['goodfirms', 'goodfirms_profile_id', 'acme-dev', 'https://www.goodfirms.co/company/acme-dev'],
    'designrush' => ['designrush', 'designrush_profile_id', 'acme-studio', 'https://www.designrush.com/agency/acme-studio'],
    'upcity' => ['upcity', 'upcity_profile_id', 'acme-marketing', 'https://upcity.com/profiles/acme-marketing'],
]);

it('registers all extended directory channels as runnable', function () {
    $expected = [
        'yellow_pages', 'manta', 'foursquare', 'the_manifest', 'goodfirms', 'designrush', 'upcity',
    ];

    foreach ($expected as $channel) {
        expect(ScraperChannels::isEnabled($channel))->toBeTrue()
            ->and(ScraperChannels::isImplemented($channel))->toBeTrue()
            ->and(ScraperChannels::requiresLocation($channel))->toBeTrue()
            ->and(ScraperChannels::runnableKeys())->toContain($channel);
    }
});

it('ingests and dedupes extended directory channel leads', function (string $source, string $idKey, string $idValue, string $listingUrl) {
    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'Acme Services Co',
        'phone' => '+1-555-0100',
        'website' => 'https://acme.example',
        'address' => '123 Main St',
        $idKey => $idValue,
        listingUrlField($idKey) => $listingUrl,
        'source' => $source,
        'scrape_keyword' => 'plumber',
        'scrape_country' => 'United States',
        'scrape_city' => 'New York',
    ]);

    expect($result->status)->toBe('created');

    $lead = Lead::query()->first();
    expect($lead->source)->toBe($source)
        ->and($lead->metadata[$idKey])->toBe($idValue);

    $duplicate = app(LeadIngestionService::class)->ingest([
        'business_name' => 'Acme Services Co',
        $idKey => $idValue,
        'source' => $source,
    ]);

    expect($duplicate->status)->toBe('duplicate')
        ->and(Lead::query()->count())->toBe(1);
})->with('extended_directory_channels');

it('strips directory listing urls from website field on ingest', function (string $source, string $idKey, string $idValue, string $listingUrl) {
    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'Listing Only Co',
        'website' => $listingUrl,
        $idKey => $idValue,
        listingUrlField($idKey) => $listingUrl,
        'source' => $source,
    ]);

    expect($result->status)->toBe('created');
    expect(Lead::query()->first()->website)->toBeNull();
})->with('extended_directory_channels');

it('scores extended directory leads using directory scoring profile', function (string $source, string $idKey, string $idValue) {
    $lead = Lead::query()->create([
        'first_name' => 'Acme',
        'last_name' => 'Co',
        'source' => $source,
        'company' => 'Acme Co',
        'phone' => '+15550123456',
        'website' => null,
        'metadata' => [
            $idKey => $idValue,
            'address' => '99 Broadway',
        ],
    ]);

    $evaluation = app(LeadScoringService::class)->evaluate($lead);

    expect($evaluation['opportunity_score'])->toBeGreaterThan(0)
        ->and($evaluation['factors']['opportunity']['signals'])->toContain('No website found (website pitch opportunity)')
        ->and($evaluation['factors']['authenticity']['signals'])->toContain('Directory business ID present');
})->with('extended_directory_channels');

it('passes foursquare config in scraper channel payload', function () {
    $job = \App\Models\ScrapeJob::factory()->make(['source_channel' => 'foursquare']);
    $payload = ScraperChannels::payloadFor($job);

    expect($payload)->toHaveKey('foursquare')
        ->and($payload['foursquare']['api_base_url'])->toBe(config('foursquare.api_base_url'));
});
