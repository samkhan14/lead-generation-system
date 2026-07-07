<?php

/**
 * Temporary QA probe tests — documents edge-case behavior for marketing module review.
 * Safe to keep: encodes expected business rules as regression guards.
 */

use App\Services\LeadPitchService;
use Database\Seeders\AiProviderSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('qa: maps lead with low reviews prioritizes reviews_growth over local_seo as primary', function () {
    $lead = createLead([
        'source' => 'google_maps',
        'website' => 'https://acme.com',
        'phone' => '5550001111',
        'metadata' => ['review_count' => 5, 'rating' => 3.2],
    ]);

    $primary = app(LeadPitchService::class)->primaryRecommendation($lead);

    expect($primary['type'])->toBe('reviews_growth');
});

test('qa: maps lead with low reviews routes to maya when reviews_growth is primary', function () {
    $this->seed(AiProviderSeeder::class);

    $agent = createAgent();
    $lead = createLead([
        'source' => 'google_maps',
        'website' => 'https://acme.com',
        'phone' => '5550001111',
        'metadata' => ['review_count' => 5, 'rating' => 3.2],
    ]);

    $maya = \App\Domains\AI\Models\AiEmployee::query()->where('name', 'Maya — SEO & Marketing Specialist')->first();

    $this->actingAs($agent)
        ->get(route('leads.show', $lead))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('workforce.default_employee_id', $maya->id)
        );
});

test('qa: pitch filter sql parity for google_ads is broader than marketing-only leads', function () {
    $agent = createAgent();

    $devLead = createLead([
        'email' => 'dev@example.com',
        'source' => 'manual',
        'website' => 'https://devco.com',
        'phone' => '5559990000',
    ]);

    $this->actingAs($agent)
        ->get(route('leads.index', ['pitch_type' => 'google_ads']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.id', $devLead->id)
        );
});
