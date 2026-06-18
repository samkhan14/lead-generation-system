<?php

use App\Models\Lead;
use App\Services\LeadIngestionService;
use App\Support\LeadDataQuality;
use App\Support\LeadIdentifiers;
use Illuminate\Support\Facades\Http;

function qualityGoogleMapsPayload(array $overrides = []): array
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

test('normalizes google place id from malformed maps url blob', function () {
    $blob = 'data=!4m7!3m6!1s0x3eb33e85e5081aef:0x1d27428ccae4d8f5!19sChIJ7xoI5YU-sz4R9djkyoxCJx0';

    expect(LeadIdentifiers::normalizeGooglePlaceId($blob))->toBe('ChIJ7xoI5YU-sz4R9djkyoxCJx0');
});

test('sanitizes google maps icon characters from address', function () {
    $address = "\u{E0C8}Plot # 21-22, Karachi";

    expect(LeadIdentifiers::sanitizeAddress($address))->toBe('Plot # 21-22, Karachi');
});

test('strips yelp listing urls from website field', function () {
    expect(LeadIdentifiers::sanitizeBusinessWebsite('https://www.yelp.com/biz/foo'))->toBeNull()
        ->and(LeadIdentifiers::sanitizeBusinessWebsite('https://foodsinn.pk'))->toBe('https://foodsinn.pk');
});

test('lead data quality flags missing website', function () {
    $quality = LeadDataQuality::assess([
        'company' => 'Foods Inn',
        'phone' => '+923001234567',
        'website' => null,
        'metadata' => ['address' => 'Karachi'],
    ]);

    expect($quality['score'])->toBe(75)
        ->and($quality['missing_fields'])->toContain('website')
        ->and($quality['has_website'])->toBeFalse();
});

test('duplicate directory lead merges missing website from re-ingest', function () {
    $service = app(LeadIngestionService::class);

    $existing = $service->ingest(qualityGoogleMapsPayload([
        'website' => null,
        'google_place_id' => 'ChIJ_foods_inn_test',
    ]))->lead;

    expect($existing->website)->toBeNull();

    $service->ingest(qualityGoogleMapsPayload([
        'google_place_id' => 'ChIJ_foods_inn_test',
        'website' => 'https://www.foodsinn.pk',
    ]));

    $existing->refresh();

    expect($existing->website)->toBe('https://www.foodsinn.pk')
        ->and($existing->metadata['data_quality']['has_website'])->toBeTrue();
});

test('enrichment service fills missing website from places api', function () {
    config(['google_places.api_key' => 'test-key']);

    Http::fake([
        'places.googleapis.com/v1/places/ChIJ_foods_inn_test' => Http::response([
            'id' => 'places/ChIJ_foods_inn_test',
            'websiteUri' => 'https://www.foodsinn.pk',
            'nationalPhoneNumber' => '+92 21 11111111',
            'formattedAddress' => 'Karachi, Pakistan',
        ]),
    ]);

    $lead = Lead::query()->create([
        'first_name' => 'Foods',
        'last_name' => 'Inn',
        'company' => 'Foods Inn',
        'source' => 'google_maps',
        'website' => null,
        'metadata' => [
            'google_place_id' => 'ChIJ_foods_inn_test',
            'scrape_city' => 'Karachi',
            'scrape_country' => 'Pakistan',
        ],
    ]);

    $stats = app(\App\Services\LeadEnrichmentService::class)->enrichDirectoryLeads();

    expect($stats['updated'])->toBe(1);

    $lead->refresh();

    expect($lead->website)->toBe('https://www.foodsinn.pk')
        ->and($lead->metadata['google_place_id'])->toBe('ChIJ_foods_inn_test');
});
