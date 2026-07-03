<?php

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadScoringService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('super admin can view leads without assigned permissions', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    createLead();

    $this->actingAs($superAdmin)
        ->get(route('leads.index'))
        ->assertOk();
});

test('agent with permissions can view leads', function () {
    $agent = createAgent();

    createLead();

    $this->actingAs($agent)
        ->get(route('leads.index'))
        ->assertOk();
});

test('user without permissions cannot view leads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('leads.index'))
        ->assertForbidden();
});

test('agent can create a lead with automatic scoring', function () {
    $agent = createAgent();

    $response = $this->actingAs($agent)->post(route('leads.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'phone' => '555-111-2222',
        'website' => 'https://acme.com',
        'company' => 'Acme',
        'source' => 'manual',
    ]);

    $lead = Lead::query()->where('email', 'jane@example.com')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->created_by)->toBe($agent->id)
        ->and($lead->email_normalized)->toBe('jane@example.com')
        ->and($lead->phone_normalized)->toBe('5551112222')
        ->and($lead->website_normalized)->toBe('acme.com')
        ->and($lead->fresh()->latestScore)->not->toBeNull()
        ->and($lead->fresh()->latestScore->temperature)->not->toBeNull();

    $response->assertRedirect(route('leads.show', $lead));
});

test('duplicate email is rejected', function () {
    $agent = createAgent();

    createLead(['email' => 'duplicate@example.com']);

    $this->actingAs($agent)
        ->from(route('leads.create'))
        ->post(route('leads.store'), [
            'first_name' => 'Other',
            'last_name' => 'Person',
            'email' => 'DUPLICATE@example.com',
            'phone' => '555-222-3333',
        ])
        ->assertRedirect(route('leads.create'))
        ->assertSessionHasErrors('duplicate')
        ->assertSessionHas('duplicate_lead_id');
});

test('duplicate phone is rejected even with different formatting', function () {
    $agent = createAgent();

    createLead(['email' => 'first@example.com', 'phone' => '(555) 000-1111']);

    $this->actingAs($agent)
        ->post(route('leads.store'), [
            'first_name' => 'Other',
            'last_name' => 'Person',
            'email' => 'second@example.com',
            'phone' => '5550001111',
        ])
        ->assertSessionHasErrors('duplicate');
});

test('duplicate website is rejected', function () {
    $agent = createAgent();

    createLead(['email' => 'first@example.com', 'website' => 'https://www.shared.com']);

    $this->actingAs($agent)
        ->post(route('leads.store'), [
            'first_name' => 'Other',
            'last_name' => 'Person',
            'email' => 'second@example.com',
            'phone' => '555-222-3333',
            'website' => 'shared.com',
        ])
        ->assertSessionHasErrors('duplicate');
});

test('hot filter returns only hot leads', function () {
    $agent = createAgent();

    $hotLead = createLead([
        'email' => 'ceo@hot.com',
        'phone' => '5550001111',
        'website' => 'hot.com',
        'company' => 'Hot Co',
        'job_title' => 'CEO',
        'source' => 'api',
        'notes' => 'Interested in pricing demo urgent',
    ]);

    $coldLead = createLead([
        'email' => 'cold@example.com',
    ]);

    expect($hotLead->latestScore->temperature)->toBe('hot')
        ->and($coldLead->latestScore->temperature)->toBe('cold');

    $this->actingAs($agent)
        ->get(route('leads.index', ['temperature' => 'hot']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Leads/Index')
            ->where('filters.temperature', 'hot')
            ->has('leads.data', 1)
            ->where('leads.data.0.id', $hotLead->id)
        );
});

test('warm filter returns only warm leads', function () {
    $agent = createAgent();

    $warmLead = createLead([
        'email' => 'person@warmco.com',
        'phone' => '5550002222',
        'company' => 'Warm Co',
        'job_title' => 'Manager',
        'source' => 'manual',
        'notes' => 'Looking for more info',
    ]);

    createLead(['email' => 'cold@example.com']);

    expect($warmLead->latestScore->temperature)->toBe('warm');

    $this->actingAs($agent)
        ->get(route('leads.index', ['temperature' => 'warm']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.id', $warmLead->id)
        );
});

test('agent can soft delete a lead', function () {
    $agent = createAgent();
    $lead = createLead();

    $this->actingAs($agent)
        ->delete(route('leads.destroy', $lead))
        ->assertRedirect(route('leads.index'));

    expect(Lead::query()->find($lead->id))->toBeNull()
        ->and(Lead::withTrashed()->find($lead->id))->not->toBeNull();
});

test('lead creation requires first and last name', function () {
    $agent = createAgent();

    $this->actingAs($agent)
        ->post(route('leads.store'), [
            'first_name' => '',
            'last_name' => '',
        ])
        ->assertSessionHasErrors(['first_name', 'last_name']);
});

test('scoring engine marks complete leads as hot', function () {
    $agent = createAgent();

    $this->actingAs($agent)->post(route('leads.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@acme.com',
        'phone' => '5551234567',
        'website' => 'acme.com',
        'company' => 'Acme',
        'job_title' => 'CEO',
        'source' => 'api',
        'notes' => 'Interested in pricing demo urgent',
    ]);

    $lead = Lead::query()->where('email', 'jane@acme.com')->first();

    expect($lead->latestScore->score)->toBeGreaterThanOrEqual(70)
        ->and($lead->latestScore->temperature)->toBe('hot')
        ->and($lead->latestScore->scoring_version)->toBe('v6')
        ->and($lead->latestScore->factors)->toHaveKeys(['intent', 'opportunity', 'authenticity', 'final']);
});

test('leads index supports search and per page filters', function () {
    $agent = createAgent();

    $matchingLead = createLead([
        'email' => 'match@example.com',
        'company' => 'Karachi Dental Studio',
        'phone' => '5550003333',
    ]);

    createLead([
        'email' => 'other@example.com',
        'company' => 'Other Company',
        'phone' => '5550004444',
    ]);

    $this->actingAs($agent)
        ->get(route('leads.index', ['q' => 'Dental', 'per_page' => 10]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.q', 'Dental')
            ->where('filters.per_page', 10)
            ->has('leads.data', 1)
            ->where('leads.data.0.id', $matchingLead->id)
            ->has('leads.data.0.pitch_summary')
            ->has('filterOptions.countries')
            ->has('filterOptions.pitch_types')
        );
});

test('leads index filters by pitch type and source', function () {
    $agent = createAgent();

    $websitePitchLead = createLead([
        'email' => 'maps@example.com',
        'phone' => '5550005555',
        'source' => 'google_maps',
        'website' => null,
        'metadata' => [
            'rating' => 4.5,
            'review_count' => 30,
            'scrape_country' => 'Pakistan',
            'scrape_city' => 'Karachi',
            'scrape_area' => 'Clifton',
            'scrape_keyword' => 'dentist',
        ],
    ]);

    createLead([
        'email' => 'manual@example.com',
        'phone' => '5550006666',
        'source' => 'manual',
        'website' => 'https://has-site.com',
    ]);

    $this->actingAs($agent)
        ->get(route('leads.index', [
            'pitch_type' => 'website_build',
            'source' => 'google_maps',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.pitch_type', 'website_build')
            ->where('filters.source', 'google_maps')
            ->has('leads.data', 1)
            ->where('leads.data.0.id', $websitePitchLead->id)
        );
});

test('leads index filters by reddit_outreach pitch type returns only reddit leads', function () {
    $agent = createAgent();

    $redditLead = createLead([
        'email' => null,
        'first_name' => 'u/poster',
        'last_name' => '(Reddit)',
        'source' => 'reddit',
        'metadata' => ['reddit_post_id' => 'pitch_filter_test', 'lead_kind' => 'service_request'],
    ]);

    createLead([
        'email' => 'maps@pitchfilter.com',
        'source' => 'google_maps',
        'website' => null,
    ]);

    $this->actingAs($agent)
        ->get(route('leads.index', ['pitch_type' => 'reddit_outreach']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.id', $redditLead->id)
        );
});

test('leads index filters by city area and country metadata', function () {
    $agent = createAgent();

    $karachiLead = createLead([
        'email' => 'karachi@example.com',
        'phone' => '5550007777',
        'metadata' => [
            'scrape_country' => 'Pakistan',
            'scrape_city' => 'Karachi',
            'scrape_area' => 'DHA Phase 5',
        ],
    ]);

    createLead([
        'email' => 'lahore@example.com',
        'phone' => '5550008888',
        'metadata' => [
            'scrape_country' => 'Pakistan',
            'scrape_city' => 'Lahore',
            'scrape_area' => 'Gulberg',
        ],
    ]);

    $this->actingAs($agent)
        ->get(route('leads.index', [
            'country' => 'Pakistan',
            'city' => 'Karachi',
            'area' => 'DHA',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.id', $karachiLead->id)
        );
});

test('leads index supports cold filter and score sorting', function () {
    $agent = createAgent();

    $coldLead = createLead(['email' => 'cold@example.com']);

    $hotLead = createLead([
        'email' => 'ceo@hot.com',
        'phone' => '5550001111',
        'website' => 'hot.com',
        'company' => 'Hot Co',
        'job_title' => 'CEO',
        'source' => 'api',
        'notes' => 'Interested in pricing demo urgent',
    ]);

    expect($coldLead->latestScore->temperature)->toBe('cold')
        ->and($hotLead->latestScore->temperature)->toBe('hot');

    $this->actingAs($agent)
        ->get(route('leads.index', ['temperature' => 'cold', 'sort' => 'score_desc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.temperature', 'cold')
            ->where('filters.sort', 'score_desc')
            ->has('leads.data', 1)
            ->where('leads.data.0.id', $coldLead->id)
        );
});

test('scoring engine marks minimal leads below hot threshold', function () {
    $agent = createAgent();

    $this->actingAs($agent)->post(route('leads.store'), [
        'first_name' => 'Minimal',
        'last_name' => 'Lead',
        'email' => 'minimal@example.com',
        'phone' => '555-333-4444',
    ]);

    $lead = Lead::query()->where('first_name', 'Minimal')->first();

    expect($lead->latestScore->score)->toBeLessThan(70)
        ->and($lead->latestScore->temperature)->toBeIn(['warm', 'cold']);
});
