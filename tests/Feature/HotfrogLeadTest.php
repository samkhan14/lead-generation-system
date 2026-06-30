<?php

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadIngestionService;
use App\Services\LeadScoringService;
use App\Support\ScraperChannels;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('ingests a hotfrog lead with business metadata', function () {
    $user = User::factory()->create();

    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'Karachi Plumbing Co',
        'phone' => '+92-21-555-0100',
        'website' => 'https://karachiplumbing.example',
        'address' => 'Clifton, Karachi, Pakistan',
        'hotfrog_business_id' => '765cdb7128e815dc42caaefe455a30cd',
        'hotfrog_url' => 'https://www.hotfrog.com.pk/company/765cdb7128e815dc42caaefe455a30cd/karachi-plumbing-co/karachi/plumber',
        'source' => 'hotfrog',
        'scrape_keyword' => 'plumber',
        'scrape_country' => 'Pakistan',
        'scrape_city' => 'Karachi',
        'created_by' => $user->id,
    ]);

    expect($result->status)->toBe('created');

    $lead = Lead::query()->first();
    expect($lead->source)->toBe('hotfrog');
    expect($lead->metadata['hotfrog_business_id'])->toBe('765cdb7128e815dc42caaefe455a30cd');
    expect($lead->company)->toBe('Karachi Plumbing Co');
});

it('dedupes hotfrog leads by hotfrog_business_id', function () {
    Lead::query()->create([
        'first_name' => 'Karachi',
        'last_name' => 'Plumber',
        'source' => 'hotfrog',
        'metadata' => ['hotfrog_business_id' => '765cdb7128e815dc42caaefe455a30cd'],
    ]);

    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'Same Biz',
        'hotfrog_business_id' => '765cdb7128e815dc42caaefe455a30cd',
        'source' => 'hotfrog',
    ]);

    expect($result->status)->toBe('duplicate');
    expect(Lead::query()->count())->toBe(1);
});

it('strips hotfrog listing urls from website field on ingest', function () {
    $result = app(LeadIngestionService::class)->ingest([
        'business_name' => 'Test Shop',
        'website' => 'https://www.hotfrog.com/company/abc123/test-shop/london/retail',
        'hotfrog_business_id' => 'abc123',
        'hotfrog_url' => 'https://www.hotfrog.com/company/abc123/test-shop/london/retail',
        'source' => 'hotfrog',
    ]);

    expect($result->status)->toBe('created');
    expect(Lead::query()->first()->website)->toBeNull();
    expect(Lead::query()->first()->metadata['hotfrog_url'])->toContain('hotfrog.com');
});

it('scores hotfrog leads using directory scoring profile', function () {
    $lead = Lead::query()->create([
        'first_name' => 'Corner',
        'last_name' => 'Cafe',
        'source' => 'hotfrog',
        'company' => 'Corner Cafe',
        'phone' => '+15550123456',
        'website' => null,
        'metadata' => [
            'hotfrog_business_id' => 'abc123def456',
            'address' => '99 Broadway, Sydney',
        ],
    ]);

    $evaluation = app(LeadScoringService::class)->evaluate($lead);

    expect($evaluation['opportunity_score'])->toBeGreaterThan(0);
    expect($evaluation['factors']['opportunity']['signals'])->toContain('No website found (website pitch opportunity)');
});

it('registers hotfrog as a runnable scraper channel', function () {
    expect(ScraperChannels::isEnabled('hotfrog'))->toBeTrue()
        ->and(ScraperChannels::isImplemented('hotfrog'))->toBeTrue()
        ->and(ScraperChannels::requiresLocation('hotfrog'))->toBeTrue()
        ->and(ScraperChannels::runnableKeys())->toContain('hotfrog');
});
