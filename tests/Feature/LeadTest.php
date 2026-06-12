<?php

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function createAgent(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole('agent');

    return $user;
}

function createSuperAdmin(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole('super_admin');

    return $user;
}

function createLead(array $attributes = []): Lead
{
    return Lead::query()->create(array_merge([
        'first_name' => 'John',
        'last_name' => 'Smith',
        'email' => 'john@example.com',
    ], $attributes));
}

test('super admin can view leads without assigned permissions', function () {
    $superAdmin = createSuperAdmin();

    createLead();
    createLead(['email' => 'jane@example.com']);

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

test('agent can create a lead with an initial score', function () {
    $agent = createAgent();

    $response = $this->actingAs($agent)->post(route('leads.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'source' => 'scraper',
        'score' => 85,
    ]);

    $lead = Lead::query()->where('email', 'jane@example.com')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->created_by)->toBe($agent->id)
        ->and($lead->scores)->toHaveCount(1)
        ->and($lead->scores->first()->score)->toBe(85)
        ->and($lead->scores->first()->score_grade)->toBe('A');

    $response->assertRedirect(route('leads.show', $lead));
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
