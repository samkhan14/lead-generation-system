<?php

use App\Domains\AI\Enums\AiEmployeeStatus;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\BusinessKnowledge\Enums\ServiceStatus;
use App\Domains\BusinessKnowledge\Models\Service;
use App\Domains\Voice\Services\VoiceContextBuilder;
use App\Services\LeadPitchService;
use App\Services\LeadWorkforcePanelService;
use Database\Seeders\AiProviderSeeder;
use Database\Seeders\AiWorkforceCatalogSeeder;
use Database\Seeders\KnowledgeBaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServiceSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('digital marketing services are seeded and active', function () {
    $this->seed(ServiceSeeder::class);

    $marketingSlugs = [
        'search-engine-optimization',
        'local-seo',
        'google-ads-ppc',
        'social-media-marketing',
        'content-marketing',
        'email-marketing-automation',
        'conversion-rate-optimization',
        'marketing-analytics-reporting',
    ];

    foreach ($marketingSlugs as $slug) {
        $service = Service::query()->where('slug', $slug)->first();

        expect($service)->not->toBeNull("Missing marketing service: {$slug}")
            ->and($service->status)->toBe(ServiceStatus::Active);
    }
});

test('marketing service cross-sell and upsell references resolve to real services', function () {
    $this->seed(ServiceSeeder::class);

    $seo = Service::query()->where('slug', 'search-engine-optimization')->first();

    expect($seo->cross_sell_ids)->not->toBeEmpty()
        ->and($seo->upsell_ids)->not->toBeEmpty();

    $localSeoId = Service::query()->where('slug', 'local-seo')->value('id');

    expect($seo->cross_sell_ids)->toContain($localSeoId);
});

test('local business lead with website gets local seo and google ads pitches', function () {
    $lead = createLead([
        'source' => 'google_maps',
        'website' => 'https://acme-dental.com',
        'phone' => '+14155550142',
        'metadata' => ['rating' => 4.6, 'review_count' => 50],
    ]);

    $types = app(LeadPitchService::class)->matchingTypes($lead);

    expect($types)->toContain('local_seo')
        ->and($types)->toContain('google_ads')
        ->and($types)->not->toContain('seo_growth');
});

test('agency directory lead gets seo growth and content marketing pitches', function () {
    $lead = createLead([
        'source' => 'goodfirms',
        'website' => 'https://agency.example',
        'phone' => '+14155550187',
    ]);

    $types = app(LeadPitchService::class)->matchingTypes($lead);

    expect($types)->toContain('seo_growth')
        ->and($types)->toContain('content_marketing')
        ->and($types)->not->toContain('local_seo');
});

test('local business with low reviews gets social media growth pitch', function () {
    $lead = createLead([
        'source' => 'yelp',
        'website' => null,
        'metadata' => ['review_count' => 3],
    ]);

    $types = app(LeadPitchService::class)->matchingTypes($lead);

    expect($types)->toContain('social_media_growth');
});

test('leads index filters by local_seo pitch type', function () {
    $agent = createAgent();

    $match = createLead([
        'email' => 'local@example.com',
        'phone' => '5551110000',
        'source' => 'google_maps',
        'website' => 'https://local-match.com',
    ]);

    createLead([
        'email' => 'nowebsite@example.com',
        'phone' => '5551110001',
        'source' => 'google_maps',
        'website' => null,
    ]);

    $this->actingAs($agent)
        ->get(route('leads.index', ['pitch_type' => 'local_seo']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.pitch_type', 'local_seo')
            ->has('leads.data', 1)
            ->where('leads.data.0.id', $match->id)
        );
});

test('leads index filters by seo_growth pitch type for agency sources', function () {
    $agent = createAgent();

    $match = createLead([
        'email' => 'agency@example.com',
        'source' => 'the_manifest',
        'website' => 'https://agency-match.com',
    ]);

    createLead([
        'email' => 'maps@example.com',
        'source' => 'google_maps',
        'website' => 'https://maps.com',
    ]);

    $this->actingAs($agent)
        ->get(route('leads.index', ['pitch_type' => 'seo_growth']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('leads.data', 1)
            ->where('leads.data.0.id', $match->id)
        );
});

test('maya seo specialist is seeded as an active voice-capable employee', function () {
    $this->seed(AiProviderSeeder::class);

    $maya = AiEmployee::query()->where('name', 'Maya — SEO & Marketing Specialist')->first();

    $eligibleRoles = config('voice_platform.eligible_employee_roles', []);

    expect($maya)->not->toBeNull()
        ->and($maya->status)->toBe(AiEmployeeStatus::Active)
        ->and(in_array($maya->role->value, $eligibleRoles, true))->toBeTrue();
});

test('marketing knowledge base articles and maya prompt templates are seeded', function () {
    $this->seed(KnowledgeBaseSeeder::class);
    $this->seed(AiWorkforceCatalogSeeder::class);

    expect(\App\Domains\AI\Models\KnowledgeBase::query()->where('slug', 'digital-marketing-agency-overview')->exists())->toBeTrue()
        ->and(\App\Domains\AI\Models\KnowledgeBase::query()->where('slug', 'marketing-pitch-selection')->exists())->toBeTrue()
        ->and(\App\Domains\AI\Models\PromptTemplate::query()->where('slug', 'marketing-voice-opening')->exists())->toBeTrue();
});

test('marketing employee context includes only digital marketing services', function () {
    $this->seed(ServiceSeeder::class);

    $maya = AiEmployee::factory()->create([
        'department' => 'Marketing',
        'knowledge_sources' => ['services'],
    ]);

    $context = app(\App\Domains\AI\Services\ContextBuilder::class)->build($maya);

    $slugs = collect($context->services)->pluck('slug');

    expect($slugs)->toContain('search-engine-optimization')
        ->and($slugs)->toContain('local-seo')
        ->and($slugs)->not->toContain('laravel-development');
});

test('sales employee context excludes digital marketing services', function () {
    $this->seed(ServiceSeeder::class);

    $alex = AiEmployee::factory()->create([
        'department' => 'Sales',
        'knowledge_sources' => ['services'],
    ]);

    $context = app(\App\Domains\AI\Services\ContextBuilder::class)->build($alex);

    $slugs = collect($context->services)->pluck('slug');

    expect($slugs)->toContain('laravel-development')
        ->and($slugs)->not->toContain('search-engine-optimization');
});

test('voice context includes pitch recommendation variables for marketing leads', function () {
    $this->seed(ServiceSeeder::class);

    $employee = AiEmployee::factory()->create(['department' => 'Marketing']);
    $lead = createLead([
        'source' => 'google_maps',
        'website' => 'https://acme-dental.com',
        'company' => 'Acme Dental',
        'phone' => '5551234567',
    ]);

    $vars = app(VoiceContextBuilder::class)->dynamicVariables($employee, $lead);

    expect($vars)->toHaveKeys(['recommended_service', 'pitch_reason', 'pitch_opener'])
        ->and($vars['recommended_service'])->toContain('Local SEO');
});

test('lead workforce panel defaults to maya for marketing pitch leads', function () {
    $this->seed(AiProviderSeeder::class);

    $agent = createAgent();
    $lead = createLead([
        'source' => 'google_maps',
        'website' => 'https://local-match.com',
        'phone' => '5551110000',
    ]);

    $maya = AiEmployee::query()->where('name', 'Maya — SEO & Marketing Specialist')->first();

    $this->actingAs($agent)
        ->get(route('leads.show', $lead))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('workforce.default_employee_id', $maya->id)
        );
});

test('lead workforce panel defaults to alex for website build pitch leads', function () {
    $this->seed(AiProviderSeeder::class);

    $agent = createAgent();
    $lead = createLead([
        'source' => 'manual',
        'website' => null,
        'phone' => '5552220000',
    ]);

    $alex = AiEmployee::query()->where('name', 'Alex — Voice Sales Agent')->first();

    $this->actingAs($agent)
        ->get(route('leads.show', $lead))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('workforce.default_employee_id', $alex->id)
        );
});
