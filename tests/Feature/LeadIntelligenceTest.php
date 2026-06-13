<?php

use App\Models\Lead;
use App\Models\User;
use App\Services\LeadPitchService;
use App\Services\LeadScoringService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function intelligenceAgent(): User
{
    $user = User::factory()->create();
    $user->assignRole('agent');

    return $user;
}

test('intelligence engine calculates intent opportunity and authenticity scores', function () {
    $lead = Lead::query()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@acme.com',
        'phone' => '5551234567',
        'website' => 'acme.com',
        'company' => 'Acme',
        'job_title' => 'CEO',
        'source' => 'api',
        'notes' => 'Interested in pricing and demo urgently',
    ]);

    $score = app(LeadScoringService::class)->score($lead);

    expect($score->scoring_version)->toBe('v5')
        ->and($score->intent_score)->toBeGreaterThan(0)
        ->and($score->opportunity_score)->toBeGreaterThan(0)
        ->and($score->authenticity_score)->toBeGreaterThan(0)
        ->and($score->factors)->toHaveKeys(['intent', 'opportunity', 'authenticity', 'final'])
        ->and($score->factors['intent']['signals'])->not->toBeEmpty();
});

test('final score uses weighted intelligence dimensions', function () {
    $service = app(LeadScoringService::class);

    $lead = Lead::query()->create([
        'first_name' => 'Alex',
        'last_name' => 'Rivera',
        'email' => 'alex@riveracorp.com',
        'phone' => '5559876543',
        'website' => 'riveracorp.com',
        'company' => 'Rivera Corp',
        'job_title' => 'Director',
        'source' => 'manual',
        'notes' => 'Ready to buy, need quote asap',
    ]);

    $evaluation = $service->evaluate($lead);
    $expectedFinal = $service->calculateFinalScore(
        $evaluation['intent_score'],
        $evaluation['opportunity_score'],
        $evaluation['authenticity_score'],
    );

    expect($evaluation['final_score'])->toBe($expectedFinal)
        ->and($evaluation['final_score'])->toBeGreaterThanOrEqual(40);
});

test('high intent notes increase intent score', function () {
    $service = app(LeadScoringService::class);

    $baseLead = Lead::query()->make([
        'first_name' => 'Sam',
        'last_name' => 'Lee',
        'source' => 'manual',
    ]);

    $intentLead = Lead::query()->make([
        ...$baseLead->toArray(),
        'notes' => 'Interested in demo pricing and urgent quote',
    ]);

    expect($service->calculateIntentScore($intentLead)['score'])
        ->toBeGreaterThan($service->calculateIntentScore($baseLead)['score']);
});

test('corporate email and matching website increase opportunity score', function () {
    $service = app(LeadScoringService::class);

    $weak = Lead::query()->make([
        'first_name' => 'Sam',
        'last_name' => 'Lee',
        'email' => 'sam@gmail.com',
    ]);

    $strong = Lead::query()->make([
        'first_name' => 'Sam',
        'last_name' => 'Lee',
        'email' => 'sam@acme.com',
        'phone' => '5551112222',
        'website' => 'acme.com',
        'company' => 'Acme',
        'job_title' => 'CEO',
    ]);

    expect($service->calculateOpportunityScore($strong)['score'])
        ->toBeGreaterThan($service->calculateOpportunityScore($weak)['score']);
});

test('free email without company reduces authenticity score', function () {
    $service = app(LeadScoringService::class);

    $risky = Lead::query()->make([
        'first_name' => 'Sam',
        'last_name' => 'Lee',
        'email' => 'sam@gmail.com',
    ]);

    $safer = Lead::query()->make([
        'first_name' => 'Sam',
        'last_name' => 'Lee',
        'email' => 'sam@gmail.com',
        'company' => 'Sam Consulting',
        'phone' => '5551112222',
        'website' => 'samconsulting.com',
    ]);

    expect($service->calculateAuthenticityScore($safer)['score'])
        ->toBeGreaterThan($service->calculateAuthenticityScore($risky)['score']);
});

test('lead show page exposes intelligence breakdown', function () {
    $agent = intelligenceAgent();

    $lead = Lead::query()->create([
        'first_name' => 'Pat',
        'last_name' => 'Kim',
        'email' => 'pat@kimco.com',
        'phone' => '5554443333',
        'website' => 'kimco.com',
        'company' => 'Kim Co',
        'job_title' => 'Manager',
        'source' => 'manual',
        'notes' => 'Looking for pricing',
        'created_by' => $agent->id,
        'assigned_to' => $agent->id,
    ]);

    app(LeadScoringService::class)->score($lead);

    $this->actingAs($agent)
        ->get(route('leads.show', $lead))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('lead.latest_score.intent_score')
            ->has('lead.latest_score.opportunity_score')
            ->has('lead.latest_score.authenticity_score')
            ->where('lead.latest_score.scoring_version', 'v5')
            ->has('lead.pitch_recommendations')
        );
});

test('google maps scoring separates pitch opportunity from business authenticity', function () {
    $service = app(LeadScoringService::class);

    $weakPresence = Lead::query()->make([
        'first_name' => 'Dental',
        'last_name' => 'Clinic',
        'company' => 'Dental Clinic',
        'source' => 'google_maps',
        'phone' => '5551112222',
        'metadata' => [
            'google_place_id' => 'abc123',
            'address' => 'Karachi, Pakistan',
            'rating' => 3.8,
            'review_count' => 8,
        ],
    ]);

    $strongPresence = Lead::query()->make([
        'first_name' => 'Dental',
        'last_name' => 'Clinic',
        'company' => 'Dental Clinic',
        'source' => 'google_maps',
        'phone' => '5551112222',
        'website' => 'https://dental.example',
        'metadata' => [
            'google_place_id' => 'xyz789',
            'address' => 'Karachi, Pakistan',
            'rating' => 4.8,
            'review_count' => 300,
        ],
    ]);

    expect($service->calculateOpportunityScore($weakPresence)['score'])
        ->toBeGreaterThan($service->calculateOpportunityScore($strongPresence)['score'])
        ->and($service->calculateAuthenticityScore($strongPresence)['score'])
        ->toBeGreaterThan($service->calculateAuthenticityScore($weakPresence)['score']);
});

test('pitch service recommends services based on lead gaps', function () {
    $lead = Lead::query()->make([
        'first_name' => 'Dental',
        'last_name' => 'Clinic',
        'company' => 'Dental Clinic',
        'source' => 'google_maps',
        'phone' => '5551112222',
        'metadata' => [
            'rating' => 3.7,
            'review_count' => 9,
        ],
    ]);

    $recommendations = app(LeadPitchService::class)->recommendations($lead);

    expect($recommendations)
        ->not->toBeEmpty()
        ->and(collect($recommendations)->pluck('service')->all())
        ->toContain('Website or landing page build')
        ->toContain('Google reviews and reputation growth')
        ->toContain('Reputation repair and customer feedback workflow');
});
