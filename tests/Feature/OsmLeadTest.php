<?php

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadIngestionService;
use App\Services\LeadScoringService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('ingests an openstreetmap lead with osm metadata', function () {
    $user = User::factory()->create();

    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'Corner Cafe',
        'email' => 'info@cornercafe.com',
        'phone' => '+442071234567',
        'website' => 'https://cornercafe.example',
        'address' => '12 High Street, London',
        'osm_id' => 'node/123456789',
        'osm_type' => 'node',
        'osm_url' => 'https://www.openstreetmap.org/node/123456789',
        'source' => 'openstreetmap',
        'scrape_keyword' => 'cafe',
        'scrape_country' => 'United Kingdom',
        'scrape_city' => 'London',
        'created_by' => $user->id,
    ]);

    expect($result->status)->toBe('created');

    $lead = Lead::query()->first();
    expect($lead->source)->toBe('openstreetmap');
    expect($lead->metadata['osm_id'])->toBe('node/123456789');
    expect($lead->metadata['osm_url'])->toContain('openstreetmap.org');
});

it('dedupes openstreetmap leads by osm_id', function () {
    Lead::query()->create([
        'first_name' => 'Corner',
        'last_name' => 'Cafe',
        'source' => 'openstreetmap',
        'metadata' => ['osm_id' => 'way/987654'],
    ]);

    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'Same Place',
        'email' => 'info@sameplace.com',
        'phone' => '+44 20 7123 4568',
        'osm_id' => 'way/987654',
        'osm_type' => 'way',
        'osm_url' => 'https://www.openstreetmap.org/way/987654',
        'source' => 'openstreetmap',
    ]);

    expect($result->status)->toBe('duplicate');
    expect(Lead::query()->count())->toBe(1);
});

it('strips openstreetmap profile urls from website field', function () {
    app(LeadIngestionService::class)->ingest([
        'business_name' => 'OSM Only',
        'email' => 'info@osmonly.com',
        'phone' => '+44 20 7123 4569',
        'website' => 'https://www.openstreetmap.org/node/111',
        'osm_id' => 'node/111',
        'osm_type' => 'node',
        'osm_url' => 'https://www.openstreetmap.org/node/111',
        'source' => 'openstreetmap',
    ]);

    expect(Lead::query()->first()->website)->toBeNull();
});

it('scores openstreetmap leads using directory scoring profile', function () {
    $lead = Lead::query()->create([
        'first_name' => 'Local',
        'last_name' => 'Shop',
        'source' => 'openstreetmap',
        'company' => 'Local Shop',
        'phone' => '+15550123456',
        'website' => null,
        'metadata' => [
            'osm_id' => 'node/555',
            'address' => '1 Main St',
        ],
    ]);

    $evaluation = app(LeadScoringService::class)->evaluate($lead);

    expect($evaluation['opportunity_score'])->toBeGreaterThan(0);
    expect($evaluation['factors']['opportunity']['signals'])->toContain('No website found (website pitch opportunity)');
});
