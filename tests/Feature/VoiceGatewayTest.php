<?php

use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Models\VoiceCall;
use App\Domains\Voice\Models\VoiceProvider;
use App\Domains\Voice\Services\VoiceGateway;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;

test('voice gateway initiates outbound retell call and persists voice call record', function () {
    Http::fake([
        'api.retellai.com/v2/create-phone-call' => Http::response([
            'call_id' => 'call_test_123',
            'call_status' => 'registered',
            'from_number' => '+14155550100',
            'to_number' => '+14155550199',
        ], 200),
    ]);

    $aiProvider = AiProvider::factory()->create();
    $aiModel = AiModel::factory()->for($aiProvider, 'provider')->create();
    $employee = AiEmployee::factory()->withProviderSetup($aiProvider, $aiModel)->create([
        'voice_id' => 'agent_test_123',
    ]);

    $voiceProvider = VoiceProvider::factory()->create([
        'slug' => 'retell',
        'metadata' => [
            'default_from_number' => '+14155550100',
        ],
    ]);

    $lead = Lead::query()->create([
        'first_name' => 'Jane',
        'last_name' => 'Lead',
        'phone' => '+14155550199',
        'source' => 'manual',
    ]);

    $call = app(VoiceGateway::class)->initiateOutbound($employee, $lead);

    expect($call)
        ->toBeInstanceOf(VoiceCall::class)
        ->and($call->external_call_id)->toBe('call_test_123')
        ->and($call->status)->toBe(VoiceCallStatus::Queued)
        ->and($call->voice_provider_id)->toBe($voiceProvider->id)
        ->and($call->lead_id)->toBe($lead->id)
        ->and($call->ai_employee_id)->toBe($employee->id);

    Http::assertSent(fn ($request) => $request->url() === 'https://api.retellai.com/v2/create-phone-call'
        && $request['to_number'] === '+14155550199'
        && $request['override_agent_id'] === 'agent_test_123');
});

test('voice gateway syncs call status from provider', function () {
    Http::fake([
        'api.retellai.com/v2/get-call/*' => Http::response([
            'call_id' => 'call_sync_456',
            'call_status' => 'ended',
            'duration_ms' => 120000,
            'transcript' => 'Hello, thanks for calling.',
        ], 200),
    ]);

    $provider = VoiceProvider::factory()->create(['slug' => 'retell']);
    $call = VoiceCall::factory()->for($provider, 'provider')->create([
        'external_call_id' => 'call_sync_456',
        'status' => VoiceCallStatus::InProgress,
    ]);

    $synced = app(VoiceGateway::class)->syncCall($call);

    expect($synced->status)->toBe(VoiceCallStatus::Completed)
        ->and($synced->duration_seconds)->toBe(120)
        ->and($synced->transcript)->toBe('Hello, thanks for calling.')
        ->and($synced->ended_at)->not->toBeNull();
});

test('voice webhook updates existing call', function () {
    $provider = VoiceProvider::factory()->create([
        'slug' => 'retell',
        'webhook_secret' => 'secret-test',
    ]);

    $call = VoiceCall::factory()->for($provider, 'provider')->create([
        'external_call_id' => 'call_webhook_789',
        'status' => VoiceCallStatus::InProgress,
    ]);

    $response = $this->postJson(route('api.voice.webhooks', $provider->slug), [
        'call' => [
            'call_id' => 'call_webhook_789',
            'call_status' => 'ended',
            'transcript' => 'Webhook transcript.',
        ],
    ], [
        'X-Voice-Webhook-Secret' => 'secret-test',
    ]);

    $response->assertOk()
        ->assertJsonPath('call_id', $call->id)
        ->assertJsonPath('status', VoiceCallStatus::Completed->value);

    expect($call->fresh())
        ->status->toBe(VoiceCallStatus::Completed)
        ->transcript->toBe('Webhook transcript.');
});

test('voice gateway fails gracefully when no providers are usable', function () {
    VoiceProvider::factory()->disabled()->create(['slug' => 'retell']);

    $aiProvider = AiProvider::factory()->create();
    $aiModel = AiModel::factory()->for($aiProvider, 'provider')->create();
    $employee = AiEmployee::factory()->withProviderSetup($aiProvider, $aiModel)->create();
    $lead = Lead::query()->create([
        'first_name' => 'John',
        'last_name' => 'Lead',
        'phone' => '+14155550199',
        'source' => 'manual',
    ]);

    expect(fn () => app(VoiceGateway::class)->initiateOutbound($employee, $lead))
        ->toThrow(RuntimeException::class, 'No usable voice providers are configured.');
});
