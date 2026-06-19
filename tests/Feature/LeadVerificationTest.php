<?php

use App\Jobs\VerifyLeadJob;
use App\Models\Lead;
use App\Services\LeadVerificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config(['ingest.token' => 'test-ingest-token']);
});

function verificationIngestHeaders(): array
{
    return [
        'Authorization' => 'Bearer test-ingest-token',
        'Accept' => 'application/json',
    ];
}

function verificationGoogleMapsPayload(array $overrides = []): array
{
    return array_merge([
        'business_name' => 'Acme Dental Clinic',
        'phone' => '+92 300 1234567',
        'website' => 'https://www.acmedental.com',
        'address' => 'Clifton, Karachi, Pakistan',
        'rating' => 4.6,
        'review_count' => 128,
        'google_place_id' => 'ChIJ_test_place_id_001',
        'source' => 'google_maps',
        'scrape_keyword' => 'dentist',
        'scrape_city' => 'Karachi',
    ], $overrides);
}

test('ingest dispatches verification job for every lead with a name', function () {
    Queue::fake();

    $this->postJson('/api/leads/ingest', verificationGoogleMapsPayload([
        'website' => null,
    ]), verificationIngestHeaders())->assertCreated();

    Queue::assertPushed(VerifyLeadJob::class);
});

test('ingest dispatches verification for reddit leads too', function () {
    Queue::fake();

    $this->postJson('/api/leads/ingest', [
        'first_name' => 'John',
        'last_name' => 'Smith',
        'source' => 'reddit',
        'notes' => 'Looking for a website developer',
        'metadata' => [
            'reddit_post_id' => 't3_test_reddit_verify',
            'subreddit' => 'smallbusiness',
        ],
    ], verificationIngestHeaders())->assertCreated();

    Queue::assertPushed(VerifyLeadJob::class);
});

test('verification service discovers website via srp google search', function () {
    config(['scraper.service_url' => 'http://localhost:3100']);

    Http::fake([
        'localhost:3100/verify' => Http::response([
            'status' => 'ok',
            'website' => 'https://www.foodsinn.pk',
            'phone' => '+92 21 11111111',
            'address' => null,
            'google_place_id' => 'ChIJ_foods_inn_test',
            'checks' => [
                'google_search' => [
                    'layer' => 'playwright_google_search',
                    'status' => 'pass',
                    'message' => 'Google Search found website (score 3)',
                ],
            ],
            'scraper_used' => 'playwright',
        ]),
        'www.foodsinn.pk/*' => Http::response('ok', 200),
    ]);

    $lead = Lead::query()->create([
        'first_name' => 'Foods',
        'last_name' => 'Inn',
        'company' => 'Foods Inn',
        'source' => 'google_maps',
        'website' => null,
        'phone' => '+92 21 11111111',
        'metadata' => [
            'scrape_city' => 'Karachi',
            'scrape_country' => 'Pakistan',
        ],
    ]);

    $result = app(LeadVerificationService::class)->verify($lead);

    expect($result['updated'])->toBeTrue();

    $lead->refresh();

    expect($lead->website)->toBe('https://www.foodsinn.pk')
        ->and($lead->metadata['verification']['status'])->toBeIn(['verified', 'partial'])
        ->and($lead->metadata['verification']['checks']['google_search']['status'])->toBe('pass');
});

test('verification skips places api layer when key not configured', function () {
    config([
        'google_places.api_key' => null,
        'scraper.service_url' => 'http://localhost:3100',
    ]);

    Http::fake([
        'localhost:3100/verify' => Http::response([
            'status' => 'ok',
            'website' => null,
            'phone' => '+92 300 1234567',
            'address' => null,
            'google_place_id' => null,
            'checks' => [
                'google_search' => [
                    'layer' => 'playwright_google_search',
                    'status' => 'fail',
                    'message' => 'Google Search returned no usable website links',
                ],
            ],
            'scraper_used' => 'playwright',
        ]),
    ]);

    $lead = Lead::query()->create([
        'first_name' => 'Test',
        'last_name' => 'Biz',
        'company' => 'Test Biz',
        'source' => 'google_maps',
        'website' => null,
        'phone' => '+92 300 1234567',
        'metadata' => ['scrape_city' => 'Karachi'],
    ]);

    app(LeadVerificationService::class)->verify($lead);

    $lead->refresh();

    expect($lead->metadata['verification']['checks']['places_api']['status'])->toBe('skip')
        ->and($lead->metadata['verification']['checks']['google_search']['message'])
        ->toContain('no usable website');
});
