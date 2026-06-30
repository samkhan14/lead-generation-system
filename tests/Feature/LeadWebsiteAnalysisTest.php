<?php

use App\Models\Lead;
use App\Services\LeadScoringService;
use App\Services\LeadVerificationService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['ingest.token' => 'test-ingest-token']);
});

function analysisPayload(bool $exists = true, string $revamp = 'high', bool $hasBooking = false, bool $hasAnalytics = false): array
{
    return [
        'exists' => $exists,
        'final_url' => 'https://example.com',
        'tech_stack' => ['WordPress'],
        'cms' => 'WordPress',
        'is_mobile_responsive' => true,
        'has_contact_info' => true,
        'has_booking' => $hasBooking,
        'has_chat' => false,
        'has_analytics' => $hasAnalytics,
        'has_crm' => false,
        'copyright_year' => 2019,
        'quality_score' => 45,
        'revamp_potential' => $revamp,
        'automation_opportunities' => $hasBooking ? [] : ['booking', 'live_chat', 'analytics'],
        'pages_analyzed' => 2,
        'analyzed_at' => now()->toIso8601String(),
    ];
}

test('website_analysis layer runs when website is present and layer is enabled', function () {
    config([
        'scraper.service_url' => 'http://localhost:3100',
        'lead_quality.verification.layers' => ['website_http', 'website_analysis'],
        'google_places.api_key' => null,
    ]);

    Http::fake([
        'localhost:3100/analyze-website' => Http::response([
            'status' => 'ok',
            ...analysisPayload(),
        ]),
        '*' => Http::response('ok', 200),
    ]);

    $lead = Lead::query()->create([
        'first_name' => 'Acme',
        'last_name' => 'Gym',
        'company' => 'Acme Gym',
        'source' => 'google_maps',
        'website' => 'https://acmegym.com',
        'phone' => '+61 2 9567 0900',
        'metadata' => ['scrape_city' => 'Sydney', 'scrape_country' => 'Australia'],
    ]);

    $result = app(LeadVerificationService::class)->verify($lead);

    $lead->refresh();

    expect($lead->metadata['verification']['checks']['website_analysis']['layer'])
        ->toBe('website_analysis')
        ->and($lead->metadata['website_analysis']['exists'])->toBeTrue()
        ->and($lead->metadata['website_analysis']['quality_score'])->toBe(45);
});

test('verification status is fully_verified when website_analysis passes with no other failures', function () {
    config([
        'scraper.service_url' => 'http://localhost:3100',
        'lead_quality.verification.layers' => ['website_http', 'website_analysis'],
        'google_places.api_key' => null,
    ]);

    Http::fake([
        'localhost:3100/analyze-website' => Http::response([
            'status' => 'ok',
            ...analysisPayload(exists: true),
        ]),
        '*' => Http::response('ok', 200),
    ]);

    $lead = Lead::query()->create([
        'first_name' => 'Gold',
        'last_name' => 'Spa',
        'company' => "Gold's Spa",
        'source' => 'google_maps',
        'website' => 'https://goldsspa.com',
        'phone' => '+61 2 9956 5230',
        'email' => 'info@goldsspa.com',
        'metadata' => ['scrape_city' => 'Sydney', 'scrape_country' => 'Australia'],
    ]);

    app(LeadVerificationService::class)->verify($lead);
    $lead->refresh();

    expect($lead->metadata['verification']['status'])->toBe('fully_verified')
        ->and($lead->verified_at)->not->toBeNull();
});

test('website_analysis layer is skipped when no website is available', function () {
    config([
        'scraper.service_url' => 'http://localhost:3100',
        'lead_quality.verification.layers' => ['website_analysis'],
        'google_places.api_key' => null,
    ]);

    Http::fake(['*' => Http::response('ok', 200)]);

    $lead = Lead::query()->create([
        'first_name' => 'No',
        'last_name' => 'Site',
        'company' => 'No Site Biz',
        'source' => 'google_maps',
        'website' => null,
        'phone' => '+61 2 9567 0000',
        'metadata' => [],
    ]);

    app(LeadVerificationService::class)->verify($lead);
    $lead->refresh();

    expect($lead->metadata['verification']['checks']['website_analysis']['status'])->toBe('skip');
});

test('scoring adds opportunity points for high revamp potential', function () {
    $lead = Lead::query()->create([
        'first_name' => 'Old',
        'last_name' => 'Site',
        'company' => 'Old Site Co',
        'source' => 'google_maps',
        'website' => 'https://example.com',
        'phone' => '+1 555 000 0001',
        'metadata' => [
            'website_analysis' => analysisPayload(exists: true, revamp: 'high', hasBooking: false, hasAnalytics: false),
        ],
    ]);

    $service = app(LeadScoringService::class);
    $opportunity = $service->calculateOpportunityScore($lead);

    $signals = implode(' ', $opportunity['signals']);
    expect($signals)->toContain('revamp')
        ->and($signals)->toContain('booking');
});

test('scoring adds authenticity points when website is confirmed by analysis', function () {
    $lead = Lead::query()->create([
        'first_name' => 'Real',
        'last_name' => 'Biz',
        'company' => 'Real Biz',
        'source' => 'google_maps',
        'website' => 'https://example.com',
        'phone' => '+1 555 000 0002',
        'metadata' => [
            'website_analysis' => analysisPayload(exists: true, revamp: 'low'),
        ],
    ]);

    $service = app(LeadScoringService::class);
    $authenticity = $service->calculateAuthenticityScore($lead);

    $signals = implode(' ', $authenticity['signals']);
    expect($signals)->toContain('verified and analyzed');
});

test('scoring does not add analysis signals when website_analysis is absent', function () {
    $lead = Lead::query()->create([
        'first_name' => 'No',
        'last_name' => 'Analysis',
        'company' => 'No Analysis Co',
        'source' => 'google_maps',
        'website' => 'https://example.com',
        'phone' => '+1 555 000 0003',
        'metadata' => [],
    ]);

    $service = app(LeadScoringService::class);
    $opportunity = $service->calculateOpportunityScore($lead);
    $authenticity = $service->calculateAuthenticityScore($lead);

    expect(collect($opportunity['signals'])->filter(fn ($s) => str_contains($s, 'revamp')))->toBeEmpty()
        ->and(collect($authenticity['signals'])->filter(fn ($s) => str_contains($s, 'verified and analyzed')))->toBeEmpty();
});

test('reverify route queues verification job', function () {
    \Illuminate\Support\Facades\Queue::fake();
    (new \Database\Seeders\RolesAndPermissionsSeeder())->run();

    $user = \App\Models\User::factory()->create();
    $user->assignRole('agent'); // agent has leads.view permission

    $lead = Lead::query()->create([
        'first_name' => 'Rev',
        'last_name' => 'Erify',
        'company' => 'Rev Erify Co',
        'source' => 'google_maps',
        'website' => 'https://reverify.com',
        'phone' => '+1 555 000 0004',
        'metadata' => [],
    ]);

    $this->actingAs($user)
        ->post(route('leads.reverify', $lead->id))
        ->assertRedirect();

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\VerifyLeadJob::class);
});
