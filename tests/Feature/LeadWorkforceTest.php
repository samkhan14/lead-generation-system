<?php

use App\Domains\AI\Enums\AiLogStatus;
use App\Domains\AI\Enums\AiRequestType;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiLog;
use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Models\VoiceCall;
use App\Domains\Voice\Models\VoiceProvider;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['queue.default' => 'sync']);
});

test('lead show includes workforce panel data for permitted agent', function () {
    $agent = createAgent();
    $lead = createLead(['phone' => '+14155550199']);

    $this->actingAs($agent)
        ->get(route('leads.show', $lead))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Leads/Show')
            ->has('workforce')
            ->where('workforce.can_view_ai', true)
            ->where('workforce.can_view_voice', true)
        );
});

test('agent can start ai voice call from lead detail', function () {
    Http::fake([
        'api.retellai.com/v2/create-phone-call' => Http::response([
            'call_id' => 'call_lead_detail_1',
            'call_status' => 'registered',
            'from_number' => '+14155550100',
            'to_number' => '+14155550199',
        ], 200),
    ]);

    $agent = createAgent();
    $lead = createLead(['phone' => '+14155550199']);

    $aiProvider = AiProvider::factory()->create();
    $aiModel = AiModel::factory()->for($aiProvider, 'provider')->create();
    $employee = AiEmployee::factory()->withProviderSetup($aiProvider, $aiModel)->create([
        'name' => 'Alex — Voice Sales Agent',
        'voice_id' => 'agent_lead_test',
    ]);

    VoiceProvider::factory()->create([
        'slug' => 'retell',
        'metadata' => ['default_from_number' => '+14155550100'],
    ]);

    $this->actingAs($agent)
        ->post(route('leads.voice-calls.store', $lead), [
            'ai_employee_id' => $employee->id,
        ])
        ->assertRedirect(route('leads.show', $lead))
        ->assertSessionHas('voice_call_started');

    expect(VoiceCall::query()->where('lead_id', $lead->id)->first())
        ->not->toBeNull()
        ->external_call_id->toBe('call_lead_detail_1')
        ->status->toBe(VoiceCallStatus::Queued);
});

test('lead workforce panel aggregates ai logs voice calls timeline and costs', function () {
    $agent = createAgent();
    $lead = createLead(['phone' => '+14155550199']);

    $aiProvider = AiProvider::factory()->create();
    $aiModel = AiModel::factory()->for($aiProvider, 'provider')->create();
    $employee = AiEmployee::factory()->withProviderSetup($aiProvider, $aiModel)->create([
        'name' => 'Alex — Voice Sales Agent',
    ]);
    $voiceProvider = VoiceProvider::factory()->create(['slug' => 'retell']);

    AiLog::query()->create([
        'uuid' => (string) Str::uuid(),
        'ai_employee_id' => $employee->id,
        'ai_provider_id' => $aiProvider->id,
        'ai_model_id' => $aiModel->id,
        'lead_id' => $lead->id,
        'request_type' => AiRequestType::Chat,
        'prompt_tokens' => 100,
        'completion_tokens' => 50,
        'total_tokens' => 150,
        'cost_usd' => 0.0025,
        'status' => AiLogStatus::Success,
    ]);

    VoiceCall::factory()->for($voiceProvider, 'provider')->create([
        'ai_employee_id' => $employee->id,
        'lead_id' => $lead->id,
        'status' => VoiceCallStatus::Completed,
        'cost_usd' => 0.05,
    ]);

    $this->actingAs($agent)
        ->get(route('leads.show', $lead))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('workforce.ai_activities', fn ($activities) => count($activities) === 1)
            ->where('workforce.voice_calls', fn ($calls) => count($calls) === 1)
            ->where('workforce.timeline', fn ($timeline) => count($timeline) === 2)
            ->where('workforce.costs.ai_total_usd', 0.0025)
            ->where('workforce.costs.voice_total_usd', 0.05)
            ->where('workforce.costs.combined_total_usd', 0.0525)
        );
});

test('user without voice permission cannot start voice call from lead', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('leads.view');

    $lead = createLead(['phone' => '+14155550199']);

    $this->actingAs($user)
        ->post(route('leads.voice-calls.store', $lead))
        ->assertForbidden();
});
