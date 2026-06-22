<?php

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadIngestionService;
use App\Services\LeadScoringService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('ingests a bing places lead with business metadata', function () {
    $user = User::factory()->create();

    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'City Jewelers',
        'phone' => '+1-555-0200',
        'website' => 'https://cityjewelers.example',
        'address' => '456 Market St, Houston, TX',
        'bing_entity_id' => 'US/POI/p0/12345',
        'bing_url' => 'https://www.bing.com/maps?cp=29.7~-95.3',
        'source' => 'bing_places',
        'scrape_keyword' => 'jewelry',
        'scrape_country' => 'United States',
        'scrape_city' => 'Houston',
        'created_by' => $user->id,
    ]);

    expect($result->status)->toBe('created');

    $lead = Lead::query()->first();
    expect($lead->source)->toBe('bing_places');
    expect($lead->metadata['bing_entity_id'])->toBe('US/POI/p0/12345');
    expect($lead->company)->toBe('City Jewelers');
});

it('dedupes bing places leads by bing_entity_id', function () {
    Lead::query()->create([
        'first_name' => 'City',
        'last_name' => 'Jewelers',
        'source' => 'bing_places',
        'metadata' => ['bing_entity_id' => 'US/POI/p0/same-id'],
    ]);

    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'City Jewelers',
        'bing_entity_id' => 'US/POI/p0/same-id',
        'source' => 'bing_places',
    ]);

    expect($result->status)->toBe('duplicate');
    expect(Lead::query()->count())->toBe(1);
});

it('strips bing listing urls from website field on ingest', function () {
    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'Map Listing Shop',
        'website' => 'https://www.bing.com/maps?cp=29.7~-95.3',
        'bing_entity_id' => 'US/POI/p0/listing',
        'bing_url' => 'https://www.bing.com/maps?cp=29.7~-95.3',
        'source' => 'bing_places',
    ]);

    expect($result->status)->toBe('created');
    expect(Lead::query()->first()->website)->toBeNull();
    expect(Lead::query()->first()->metadata['bing_url'])->toContain('bing.com');
});

it('scores bing places leads using directory scoring profile', function () {
    $lead = Lead::query()->create([
        'first_name' => 'Corner',
        'last_name' => 'Shop',
        'source' => 'bing_places',
        'company' => 'Corner Shop',
        'phone' => '+15550123456',
        'website' => null,
        'metadata' => [
            'bing_entity_id' => 'US/POI/p0/corner-shop',
            'address' => '99 Broadway, Houston',
        ],
    ]);

    $evaluation = app(LeadScoringService::class)->evaluate($lead);

    expect($evaluation['opportunity_score'])->toBeGreaterThan(0);
    expect($evaluation['factors']['opportunity']['signals'])->toContain('No website found (website pitch opportunity)');
});
