<?php

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadIngestionService;
use App\Services\LeadScoringService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('ingests a yelp lead with business metadata', function () {
    $user = User::factory()->create();

    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'Joe\'s Plumbing',
        'phone' => '+1-555-0100',
        'website' => 'https://joesplumbing.example',
        'address' => '123 Main St, Austin, TX',
        'rating' => 4.2,
        'review_count' => 87,
        'yelp_business_id' => 'joes-plumbing-austin',
        'source' => 'yelp',
        'scrape_keyword' => 'plumber',
        'scrape_country' => 'United States',
        'scrape_city' => 'Austin',
        'created_by' => $user->id,
    ]);

    expect($result->status)->toBe('created');

    $lead = Lead::query()->first();
    expect($lead->source)->toBe('yelp');
    expect($lead->metadata['yelp_business_id'])->toBe('joes-plumbing-austin');
    expect($lead->company)->toBe('Joe\'s Plumbing');
});

it('dedupes yelp leads by yelp_business_id', function () {
    Lead::query()->create([
        'first_name' => 'Joe',
        'last_name' => 'Plumber',
        'source' => 'yelp',
        'metadata' => ['yelp_business_id' => 'same-biz-slug'],
    ]);

    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'Same Biz',
        'yelp_business_id' => 'same-biz-slug',
        'source' => 'yelp',
    ]);

    expect($result->status)->toBe('duplicate');
    expect(Lead::query()->count())->toBe(1);
});

it('strips yelp listing urls from website field on ingest', function () {
    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'Test Shop',
        'website' => 'https://www.yelp.com/biz/test-shop-london',
        'yelp_business_id' => 'test-shop-london',
        'yelp_url' => 'https://www.yelp.com/biz/test-shop-london',
        'source' => 'yelp',
    ]);

    expect($result->status)->toBe('created');
    expect(Lead::query()->first()->website)->toBeNull();
    expect(Lead::query()->first()->metadata['yelp_url'])->toContain('yelp.com');
});

it('scores yelp leads using directory scoring profile', function () {
    $lead = Lead::query()->create([
        'first_name' => 'Corner',
        'last_name' => 'Cafe',
        'source' => 'yelp',
        'company' => 'Corner Cafe',
        'phone' => '+15550123456',
        'website' => null,
        'metadata' => [
            'yelp_business_id' => 'corner-cafe-nyc',
            'rating' => 3.8,
            'review_count' => 12,
            'address' => '99 Broadway, New York',
        ],
    ]);

    $evaluation = app(LeadScoringService::class)->evaluate($lead);

    expect($evaluation['opportunity_score'])->toBeGreaterThan(0);
    expect($evaluation['factors']['opportunity']['signals'])->toContain('No website found (website pitch opportunity)');
});
