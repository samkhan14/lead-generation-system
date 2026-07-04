<?php

use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use App\Domains\AI\Models\KnowledgeBase;
use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Jobs\InitiateVoiceCall;
use App\Domains\Voice\Models\VoiceCall;
use App\Domains\Voice\Models\VoiceProvider;
use App\Domains\Voice\Services\VoiceCallDispatcher;
use App\Domains\Voice\Services\VoiceGateway;
use Database\Seeders\KnowledgeBaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    config(['queue.default' => 'sync']);
});

test('knowledge base seeder loads production company articles', function () {
    $this->seed(KnowledgeBaseSeeder::class);

    expect(KnowledgeBase::query()->active()->count())->toBe(10)
        ->and(KnowledgeBase::query()->where('slug', 'company-overview')->exists())->toBeTrue()
        ->and(KnowledgeBase::query()->where('slug', 'voice-sales-guidelines')->exists())->toBeTrue()
        ->and(KnowledgeBase::query()->where('slug', 'pricing-philosophy')->exists())->toBeTrue();
});

test('context builder loads seeded knowledge base articles', function () {
    $this->seed(KnowledgeBaseSeeder::class);

    $context = app(\App\Domains\AI\Services\ContextBuilder::class)->build(
        \App\Domains\AI\Models\AiEmployee::factory()->create([
            'knowledge_sources' => ['knowledge_bases'],
        ]),
    );

    expect($context->knowledgeArticles)->toHaveCount(10)
        ->and(collect($context->knowledgeArticles)->pluck('slug'))->toContain('engagement-process');
});

test('lead voice call queues pending record and job executes provider dial', function () {
    Http::fake([
        'api.retellai.com/v2/create-phone-call' => Http::response([
            'call_id' => 'call_async_1',
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
        'voice_id' => 'agent_async_test',
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

    $call = VoiceCall::query()->where('lead_id', $lead->id)->first();

    expect($call)
        ->not->toBeNull()
        ->external_call_id->toBe('call_async_1')
        ->status->toBe(VoiceCallStatus::Queued);
});

test('duplicate active call for same lead and employee is blocked', function () {
    $agent = createAgent();
    $lead = createLead(['phone' => '+14155550199']);

    $aiProvider = AiProvider::factory()->create();
    $aiModel = AiModel::factory()->for($aiProvider, 'provider')->create();
    $employee = AiEmployee::factory()->withProviderSetup($aiProvider, $aiModel)->create([
        'voice_id' => 'agent_dup_test',
    ]);

    $provider = VoiceProvider::factory()->create(['slug' => 'retell']);

    VoiceCall::factory()->for($provider, 'provider')->create([
        'ai_employee_id' => $employee->id,
        'lead_id' => $lead->id,
        'status' => VoiceCallStatus::Pending,
    ]);

    $this->actingAs($agent)
        ->post(route('leads.voice-calls.store', $lead), [
            'ai_employee_id' => $employee->id,
        ])
        ->assertRedirect(route('leads.show', $lead))
        ->assertSessionHasErrors('voice_call');

    expect(VoiceCall::query()->where('lead_id', $lead->id)->count())->toBe(1);
});

test('bulk outbound calls queue multiple leads for same employee', function () {
    Http::fake([
        'api.retellai.com/v2/create-phone-call' => Http::sequence()
            ->push(['call_id' => 'bulk_1', 'call_status' => 'registered', 'from_number' => '+14155550100', 'to_number' => '+14155550101'], 200)
            ->push(['call_id' => 'bulk_2', 'call_status' => 'registered', 'from_number' => '+14155550100', 'to_number' => '+14155550102'], 200)
            ->push(['call_id' => 'bulk_3', 'call_status' => 'registered', 'from_number' => '+14155550100', 'to_number' => '+14155550103'], 200),
    ]);

    $aiProvider = AiProvider::factory()->create();
    $aiModel = AiModel::factory()->for($aiProvider, 'provider')->create();
    $employee = AiEmployee::factory()->withProviderSetup($aiProvider, $aiModel)->create([
        'voice_id' => 'agent_bulk_test',
    ]);

    VoiceProvider::factory()->create([
        'slug' => 'retell',
        'metadata' => ['default_from_number' => '+14155550100'],
    ]);

    $leads = collect([
        createLead(['phone' => '+14155550101']),
        createLead(['phone' => '+14155550102']),
        createLead(['phone' => '+14155550103']),
    ]);

    $result = app(VoiceCallDispatcher::class)->queueBulkOutbound($employee, $leads);

    expect($result->queued)->toBe(3)
        ->and($result->skipped)->toBe(0)
        ->and(VoiceCall::query()->where('ai_employee_id', $employee->id)->count())->toBe(3)
        ->and(VoiceCall::query()->where('external_call_id', 'bulk_1')->exists())->toBeTrue()
        ->and(VoiceCall::query()->where('external_call_id', 'bulk_3')->exists())->toBeTrue();
});

test('execute pending marks call failed when no usable providers exist', function () {
    $provider = VoiceProvider::factory()->disabled()->create(['slug' => 'retell']);

    $aiProvider = AiProvider::factory()->create();
    $aiModel = AiModel::factory()->for($aiProvider, 'provider')->create();
    $employee = AiEmployee::factory()->withProviderSetup($aiProvider, $aiModel)->create();
    $lead = createLead(['phone' => '+14155550199']);

    $call = app(\App\Domains\Voice\Services\VoiceCallManager::class)->createPending(
        provider: $provider,
        toNumber: '+14155550199',
        employeeId: $employee->id,
        leadId: $lead->id,
    );

    $result = app(VoiceGateway::class)->executePending($call);

    expect($result->status)->toBe(VoiceCallStatus::Failed)
        ->and($result->error_message)->toContain('No usable voice providers');
});
