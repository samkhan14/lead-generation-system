<?php

use App\Domains\Voice\Enums\VoiceProviderStatus;
use App\Domains\Voice\Models\VoiceCall;
use App\Domains\Voice\Models\VoiceProvider;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function createVoiceAgent(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole('agent');

    return $user;
}

test('agent with voice permissions can view voice providers index', function () {
    $agent = createVoiceAgent();

    VoiceProvider::factory()->create();

    $this->actingAs($agent)
        ->get(route('admin.voice.providers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Voice/Providers/Index'));
});

test('user without voice permissions cannot view voice providers index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.voice.providers.index'))
        ->assertForbidden();
});

test('agent can create voice provider without exposing api key', function () {
    $agent = createVoiceAgent();

    $response = $this->actingAs($agent)->post(route('admin.voice.providers.store'), [
        'slug' => 'retell',
        'name' => 'Retell Production',
        'api_key' => 'retell-secret-key',
        'webhook_secret' => 'whsec_test',
        'priority' => 10,
        'timeout_seconds' => 30,
        'retry_count' => 2,
        'status' => VoiceProviderStatus::Active->value,
        'metadata' => [
            'default_from_number' => '+14155550100',
            'agent_id' => 'agent_abc',
        ],
    ]);

    $provider = VoiceProvider::query()->where('name', 'Retell Production')->first();

    expect($provider)->not->toBeNull()
        ->and($provider->api_key)->toBe('retell-secret-key');

    $response->assertRedirect(route('admin.voice.providers.show', $provider));

    $this->actingAs($agent)
        ->get(route('admin.voice.providers.show', $provider))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('provider.has_api_key', true)
            ->missing('provider.api_key')
        );
});

test('agent can view voice calls index', function () {
    $agent = createVoiceAgent();
    $provider = VoiceProvider::factory()->create();

    VoiceCall::factory()->for($provider, 'provider')->create();

    $this->actingAs($agent)
        ->get(route('admin.voice.calls.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Voice/Calls/Index'));
});
