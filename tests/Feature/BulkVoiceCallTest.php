<?php

use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Models\VoiceCall;
use App\Domains\Voice\Models\VoiceProvider;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['queue.default' => 'sync']);
});

test('leads index includes voice call options for permitted agent', function () {
    $agent = createAgent();

    VoiceProvider::factory()->create(['slug' => 'retell']);

    $aiProvider = AiProvider::factory()->create();
    $aiModel = AiModel::factory()->for($aiProvider, 'provider')->create();
    AiEmployee::factory()->withProviderSetup($aiProvider, $aiModel)->create([
        'name' => 'Alex — Voice Sales Agent',
    ]);

    $this->actingAs($agent)
        ->get(route('leads.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Leads/Index')
            ->has('voiceCallOptions')
            ->where('voiceCallOptions.can_start_voice_call', true)
            ->where('voiceCallOptions.max_bulk_leads', 50)
        );
});

test('agent can queue bulk voice calls from leads index', function () {
    Http::fake([
        'api.retellai.com/v2/create-phone-call' => Http::sequence()
            ->push(['call_id' => 'bulk_http_1', 'call_status' => 'registered', 'from_number' => '+14155550100', 'to_number' => '+14155550101'], 200)
            ->push(['call_id' => 'bulk_http_2', 'call_status' => 'registered', 'from_number' => '+14155550100', 'to_number' => '+14155550102'], 200),
    ]);

    $agent = createAgent();

    $aiProvider = AiProvider::factory()->create();
    $aiModel = AiModel::factory()->for($aiProvider, 'provider')->create();
    $employee = AiEmployee::factory()->withProviderSetup($aiProvider, $aiModel)->create([
        'voice_id' => 'agent_bulk_http',
    ]);

    VoiceProvider::factory()->create([
        'slug' => 'retell',
        'metadata' => ['default_from_number' => '+14155550100'],
    ]);

    $leadOne = createLead(['phone' => '+14155550101']);
    $leadTwo = createLead(['phone' => '+14155550102']);

    $this->actingAs($agent)
        ->from(route('leads.index'))
        ->post(route('leads.voice-calls.bulk'), [
            'lead_ids' => [$leadOne->id, $leadTwo->id],
            'ai_employee_id' => $employee->id,
        ])
        ->assertRedirect(route('leads.index'))
        ->assertSessionHas('bulk_voice_call_result', fn (array $result) => $result['queued'] === 2 && $result['skipped'] === 0);

    expect(VoiceCall::query()->whereIn('lead_id', [$leadOne->id, $leadTwo->id])->count())->toBe(2);
});

test('bulk voice call skips leads without phone', function () {
    Http::fake([
        'api.retellai.com/v2/create-phone-call' => Http::response([
            'call_id' => 'bulk_skip_1',
            'call_status' => 'registered',
            'from_number' => '+14155550100',
            'to_number' => '+14155550101',
        ], 200),
    ]);

    $agent = createAgent();

    $aiProvider = AiProvider::factory()->create();
    $aiModel = AiModel::factory()->for($aiProvider, 'provider')->create();
    $employee = AiEmployee::factory()->withProviderSetup($aiProvider, $aiModel)->create([
        'voice_id' => 'agent_bulk_skip',
    ]);

    VoiceProvider::factory()->create([
        'slug' => 'retell',
        'metadata' => ['default_from_number' => '+14155550100'],
    ]);

    $withPhone = createLead(['phone' => '+14155550101']);
    $withoutPhone = createLead(['phone' => null]);

    $this->actingAs($agent)
        ->from(route('leads.index'))
        ->post(route('leads.voice-calls.bulk'), [
            'lead_ids' => [$withPhone->id, $withoutPhone->id],
            'ai_employee_id' => $employee->id,
        ])
        ->assertRedirect(route('leads.index'))
        ->assertSessionHas('bulk_voice_call_result', fn (array $result) => $result['queued'] === 1 && $result['skipped'] === 1);

    expect(VoiceCall::query()->where('lead_id', $withPhone->id)->exists())->toBeTrue()
        ->and(VoiceCall::query()->where('lead_id', $withoutPhone->id)->exists())->toBeFalse();
});

test('bulk voice call returns blockers when no usable voice provider', function () {
    $agent = createAgent();

    VoiceProvider::factory()->disabled()->create(['slug' => 'retell']);

    $aiProvider = AiProvider::factory()->create();
    $aiModel = AiModel::factory()->for($aiProvider, 'provider')->create();
    $employee = AiEmployee::factory()->withProviderSetup($aiProvider, $aiModel)->create();
    $lead = createLead(['phone' => '+14155550101']);

    $this->actingAs($agent)
        ->from(route('leads.index'))
        ->post(route('leads.voice-calls.bulk'), [
            'lead_ids' => [$lead->id],
            'ai_employee_id' => $employee->id,
        ])
        ->assertRedirect(route('leads.index'))
        ->assertSessionHasErrors('bulk_voice_call');
});

test('user without permission cannot queue bulk voice calls', function () {
    $user = \App\Models\User::factory()->create();
    $lead = createLead(['phone' => '+14155550101']);

    $this->actingAs($user)
        ->post(route('leads.voice-calls.bulk'), [
            'lead_ids' => [$lead->id],
        ])
        ->assertForbidden();
});

test('bulk voice call skips duplicate active call for same lead and employee', function () {
    Http::fake([
        'api.retellai.com/v2/create-phone-call' => Http::response([
            'call_id' => 'bulk_dup_1',
            'call_status' => 'registered',
            'from_number' => '+14155550100',
            'to_number' => '+14155550101',
        ], 200),
    ]);

    $agent = createAgent();

    $aiProvider = AiProvider::factory()->create();
    $aiModel = AiModel::factory()->for($aiProvider, 'provider')->create();
    $employee = AiEmployee::factory()->withProviderSetup($aiProvider, $aiModel)->create([
        'voice_id' => 'agent_bulk_dup',
    ]);

    $provider = VoiceProvider::factory()->create([
        'slug' => 'retell',
        'metadata' => ['default_from_number' => '+14155550100'],
    ]);

    $lead = createLead(['phone' => '+14155550101']);

    VoiceCall::factory()->for($provider, 'provider')->create([
        'ai_employee_id' => $employee->id,
        'lead_id' => $lead->id,
        'status' => VoiceCallStatus::Pending,
    ]);

    $this->actingAs($agent)
        ->from(route('leads.index'))
        ->post(route('leads.voice-calls.bulk'), [
            'lead_ids' => [$lead->id],
            'ai_employee_id' => $employee->id,
        ])
        ->assertRedirect(route('leads.index'))
        ->assertSessionHas('bulk_voice_call_result', fn (array $result) => $result['queued'] === 0 && $result['skipped'] === 1);

    expect(VoiceCall::query()->where('lead_id', $lead->id)->count())->toBe(1);
});
