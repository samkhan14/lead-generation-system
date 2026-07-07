<?php

use App\Domains\BusinessKnowledge\Enums\ServiceStatus;
use App\Domains\BusinessKnowledge\Models\Service;
use App\Models\User;
use App\Services\ServiceCatalogService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function createServiceAgent(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole('agent');

    return $user;
}

test('super admin can view services index without assigned permissions', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    Service::factory()->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.services.index'))
        ->assertOk();
});

test('agent with services permission can view services index', function () {
    $agent = createServiceAgent();

    Service::factory()->create();

    $this->actingAs($agent)
        ->get(route('admin.services.index'))
        ->assertOk();
});

test('user without services permission cannot view services index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.services.index'))
        ->assertForbidden();
});

test('agent can create a service through the catalog', function () {
    $agent = createServiceAgent();

    $response = $this->actingAs($agent)->post(route('admin.services.store'), [
        'name' => 'Website Development',
        'description' => 'Custom websites for local businesses.',
        'features' => ['Responsive design', 'SEO setup'],
        'benefits' => ['More leads', 'Professional presence'],
        'deliverables' => ['Homepage', 'Contact form'],
        'pricing_notes' => 'Starts at $2,500',
        'faqs' => [
            ['question' => 'How long?', 'answer' => '2-4 weeks'],
        ],
        'objections' => [
            ['objection' => 'Too expensive', 'response' => 'We can phase delivery.'],
        ],
        'tags' => ['web', 'seo'],
        'status' => ServiceStatus::Active->value,
    ]);

    $service = Service::query()->where('slug', 'website-development')->first();

    expect($service)->not->toBeNull()
        ->and($service->name)->toBe('Website Development')
        ->and($service->created_by)->toBe($agent->id)
        ->and($service->version)->toBe(1)
        ->and($service->features)->toBe(['Responsive design', 'SEO setup'])
        ->and($service->status)->toBe(ServiceStatus::Active);

    $response->assertRedirect(route('admin.services.show', $service));
});

test('agent can update a service and version increments on content change', function () {
    $agent = createServiceAgent();
    $service = Service::factory()->create([
        'name' => 'SEO Package',
        'version' => 1,
        'created_by' => $agent->id,
    ]);

    $this->actingAs($agent)->put(route('admin.services.update', $service), [
        'name' => 'SEO Package',
        'description' => 'Updated description for local SEO.',
        'features' => ['Keyword research'],
        'benefits' => [],
        'deliverables' => [],
        'pricing_notes' => null,
        'faqs' => [],
        'objections' => [],
        'cross_sell_ids' => [],
        'upsell_ids' => [],
        'tags' => [],
        'status' => ServiceStatus::Active->value,
    ])->assertRedirect(route('admin.services.show', $service));

    $service->refresh();

    expect($service->description)->toBe('Updated description for local SEO.')
        ->and($service->version)->toBe(2)
        ->and($service->updated_by)->toBe($agent->id);
});

test('agent can delete a service', function () {
    $agent = createServiceAgent();
    $service = Service::factory()->create();

    $this->actingAs($agent)
        ->delete(route('admin.services.destroy', $service))
        ->assertRedirect(route('admin.services.index'));

    expect(Service::query()->find($service->id))->toBeNull()
        ->and(Service::withTrashed()->find($service->id))->not->toBeNull();
});

test('user without create permission cannot store a service', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('services.view');

    $this->actingAs($user)
        ->post(route('admin.services.store'), [
            'name' => 'Blocked Service',
            'status' => ServiceStatus::Draft->value,
        ])
        ->assertForbidden();
});

test('service catalog returns active knowledge for ai consumption', function () {
    $active = Service::factory()->create(['name' => 'Active Offer', 'status' => ServiceStatus::Active, 'sort_order' => 1]);
    Service::factory()->draft()->create(['name' => 'Draft Offer']);
    Service::factory()->archived()->create(['name' => 'Archived Offer']);

    $related = Service::factory()->create([
        'name' => 'Maintenance Plan',
        'status' => ServiceStatus::Active,
        'sort_order' => 2,
    ]);

    $active->update([
        'cross_sell_ids' => [$related->id],
        'upsell_ids' => [$related->id],
    ]);

    $catalog = app(ServiceCatalogService::class);
    $knowledge = $catalog->activeKnowledgeForAi();

    expect($knowledge)->toHaveCount(2)
        ->and($knowledge[0]->name)->toBe('Active Offer')
        ->and($knowledge[0]->crossSells[0]['name'])->toBe('Maintenance Plan')
        ->and(collect($knowledge)->pluck('name'))->not->toContain('Draft Offer')
        ->and(collect($knowledge)->pluck('name'))->not->toContain('Archived Offer');
});

test('business service seeder loads the full production catalog', function () {
    $this->seed(\Database\Seeders\ServiceSeeder::class);

    // 15 core (dev/AI/automation/consulting) + 8 digital marketing services.
    expect(Service::query()->where('status', ServiceStatus::Active)->count())->toBe(23)
        ->and(Service::query()->where('slug', 'laravel-development')->exists())->toBeTrue()
        ->and(Service::query()->where('slug', 'ai-employees-voice-agents')->exists())->toBeTrue()
        ->and(Service::query()->where('slug', 'search-engine-optimization')->exists())->toBeTrue()
        ->and(Service::query()->where('slug', 'google-ads-ppc')->exists())->toBeTrue()
        ->and(Service::query()->where('slug', 'basic-website')->exists())->toBeFalse();

    $laravel = Service::query()->where('slug', 'laravel-development')->first();

    expect($laravel)
        ->short_description->not->toBeEmpty()
        ->detailed_description->not->toBeEmpty()
        ->discovery_questions->not->toBeEmpty()
        ->technologies->toContain('Laravel');

    $seo = Service::query()->where('slug', 'search-engine-optimization')->first();

    expect($seo)
        ->short_description->not->toBeEmpty()
        ->detailed_description->not->toBeEmpty()
        ->discovery_questions->not->toBeEmpty()
        ->faqs->not->toBeEmpty()
        ->objections->not->toBeEmpty();
});

test('service catalog paginate supports search and status filters', function () {
    Service::factory()->create(['name' => 'Google Ads Management', 'status' => ServiceStatus::Active]);
    Service::factory()->create(['name' => 'Logo Design', 'status' => ServiceStatus::Draft]);

    $catalog = app(ServiceCatalogService::class);

    $searchResults = $catalog->paginate(['q' => 'Google']);
    expect($searchResults->total())->toBe(1);

    $statusResults = $catalog->paginate(['status' => ServiceStatus::Draft->value]);
    expect($statusResults->total())->toBe(1)
        ->and($statusResults->items()[0]->name)->toBe('Logo Design');
});

test('service slug is generated uniquely from name', function () {
    Service::factory()->create(['name' => 'Web Design', 'slug' => 'web-design']);

    $service = app(ServiceCatalogService::class)->create([
        'name' => 'Web Design',
        'status' => ServiceStatus::Draft->value,
    ]);

    expect($service->slug)->toBe('web-design-1');
});

test('service show page renders for authorized user', function () {
    $agent = createServiceAgent();
    $service = Service::factory()->create();

    $this->actingAs($agent)
        ->get(route('admin.services.show', $service))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Services/Show')
            ->has('service')
            ->where('service.name', $service->name));
});
