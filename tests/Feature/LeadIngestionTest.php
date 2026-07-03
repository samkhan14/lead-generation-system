<?php

use App\Models\Lead;
use App\Services\LeadIngestionService;

beforeEach(function () {
    config(['ingest.token' => 'test-ingest-token']);
});

function ingestHeaders(): array
{
    return [
        'Authorization' => 'Bearer test-ingest-token',
        'Accept' => 'application/json',
    ];
}

function googleMapsPayload(array $overrides = []): array
{
    return array_merge([
        'business_name' => 'Acme Dental Clinic',
        'email' => 'info@acmedental.com',
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

test('ingest endpoint requires valid token', function () {
    $this->postJson('/api/leads/ingest', googleMapsPayload())
        ->assertUnauthorized();
});

test('ingest endpoint creates scored google maps lead', function () {
    $response = $this->postJson('/api/leads/ingest', googleMapsPayload(), ingestHeaders());

    $response->assertCreated()
        ->assertJson([
            'status' => 'created',
        ])
        ->assertJsonStructure([
            'lead_id',
            'score',
            'temperature',
            'intent_score',
            'opportunity_score',
            'authenticity_score',
        ]);

    $lead = Lead::query()->find($response->json('lead_id'));

    expect($lead)->not->toBeNull()
        ->and($lead->company)->toBe('Acme Dental Clinic')
        ->and($lead->source)->toBe('google_maps')
        ->and($lead->metadata['google_place_id'])->toBe('ChIJ_test_place_id_001')
        ->and($lead->metadata['intent_level'])->toBe('high')
        ->and($lead->latestScore)->not->toBeNull();
});

test('ingest endpoint rejects lead before storage when email is missing', function () {
    $this->postJson('/api/leads/ingest', googleMapsPayload([
        'email' => null,
    ]), ingestHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['contact_verification']);

    expect(Lead::query()->count())->toBe(0);
});

test('ingest endpoint rejects lead before storage when phone is missing', function () {
    $this->postJson('/api/leads/ingest', googleMapsPayload([
        'phone' => null,
    ]), ingestHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['contact_verification']);

    expect(Lead::query()->count())->toBe(0);
});

test('ingest endpoint discovers email from website before storage', function () {
    \Illuminate\Support\Facades\Http::fake([
        'https://www.acmedental.com' => \Illuminate\Support\Facades\Http::response(
            '<html><a href="mailto:hello@acmedental.com">Email us</a></html>',
            200,
        ),
    ]);

    $response = $this->postJson('/api/leads/ingest', googleMapsPayload([
        'email' => null,
    ]), ingestHeaders());

    $response->assertCreated();

    $lead = Lead::query()->find($response->json('lead_id'));

    expect($lead->email)->toBe('hello@acmedental.com')
        ->and($lead->metadata['contact_verification']['email']['passed'])->toBeTrue();
});

test('ingest endpoint returns duplicate for matching google place id', function () {
    $this->postJson('/api/leads/ingest', googleMapsPayload(), ingestHeaders())->assertCreated();

    $this->postJson('/api/leads/ingest', googleMapsPayload([
        'business_name' => 'Different Name',
        'phone' => '+92 300 9999999',
    ]), ingestHeaders())
        ->assertStatus(409)
        ->assertJson([
            'status' => 'duplicate',
        ]);
});

test('ingest endpoint deduplicates by phone when place id differs', function () {
    app(LeadIngestionService::class)->ingest(googleMapsPayload());

    $this->postJson('/api/leads/ingest', googleMapsPayload([
        'google_place_id' => 'ChIJ_different_place',
        'phone' => '+92 300 1234567',
    ]), ingestHeaders())
        ->assertStatus(409);
});

test('lead ingestion service maps business name to company and names', function () {
    $result = app(LeadIngestionService::class)->ingest(googleMapsPayload([
        'business_name' => 'Smile Studio',
    ]));

    expect($result->lead->company)->toBe('Smile Studio')
        ->and($result->lead->first_name)->toBe('Smile')
        ->and($result->lead->last_name)->toBe('Studio');
});
