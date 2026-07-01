<?php

use App\Domains\AI\Enums\AiEmployeeRole;
use App\Domains\AI\Enums\AiEmployeeStatus;
use App\Domains\AI\Enums\AiLogStatus;
use App\Domains\AI\Enums\AiModelStatus;
use App\Domains\AI\Enums\AiProviderStatus;
use App\Domains\AI\Enums\AiRequestType;
use App\Domains\AI\Enums\KnowledgeBaseCategory;
use App\Domains\AI\Enums\KnowledgeBaseStatus;
use App\Domains\AI\Enums\PromptTemplateCategory;
use App\Domains\AI\Enums\PromptTemplateStatus;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiLog;
use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use App\Domains\AI\Models\KnowledgeBase;
use App\Domains\AI\Models\PromptTemplate;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function createAiAgent(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole('agent');

    return $user;
}

test('agent with ai permissions can view providers index', function () {
    $agent = createAiAgent();

    AiProvider::factory()->create();

    $this->actingAs($agent)
        ->get(route('admin.ai.providers.index'))
        ->assertOk();
});

test('user without ai permissions cannot view providers index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.ai.providers.index'))
        ->assertForbidden();
});

test('agent can create and update an ai provider without exposing api key in response', function () {
    $agent = createAiAgent();

    $response = $this->actingAs($agent)->post(route('admin.ai.providers.store'), [
        'slug' => 'openai',
        'name' => 'OpenAI Test',
        'api_key' => 'sk-test-secret-key',
        'priority' => 10,
        'timeout_seconds' => 30,
        'retry_count' => 2,
        'status' => AiProviderStatus::Active->value,
    ]);

    $provider = AiProvider::query()->where('slug', 'openai')->where('name', 'OpenAI Test')->first();

    expect($provider)->not->toBeNull()
        ->and($provider->api_key)->toBe('sk-test-secret-key');

    $response->assertRedirect(route('admin.ai.providers.show', $provider));

    $showResponse = $this->actingAs($agent)
        ->get(route('admin.ai.providers.show', $provider))
        ->assertOk();

    $showResponse->assertInertia(fn ($page) => $page
        ->component('Admin/AI/Providers/Show')
        ->where('provider.has_api_key', true)
        ->missing('provider.api_key')
    );

    $this->actingAs($agent)->put(route('admin.ai.providers.update', $provider), [
        'slug' => 'openai',
        'name' => 'OpenAI Updated',
        'api_key' => '',
        'priority' => 5,
        'timeout_seconds' => 45,
        'retry_count' => 1,
        'status' => AiProviderStatus::Active->value,
    ])->assertRedirect(route('admin.ai.providers.show', $provider));

    expect($provider->fresh())
        ->name->toBe('OpenAI Updated')
        ->api_key->toBe('sk-test-secret-key');
});

test('agent can manage ai models', function () {
    $agent = createAiAgent();
    $provider = AiProvider::factory()->create(['slug' => 'anthropic', 'name' => 'Anthropic']);

    $this->actingAs($agent)->post(route('admin.ai.models.store'), [
        'ai_provider_id' => $provider->id,
        'slug' => 'claude-test',
        'name' => 'Claude Test',
        'max_tokens' => 100000,
        'input_price_per_1k' => 0.003,
        'output_price_per_1k' => 0.015,
        'status' => AiModelStatus::Active->value,
    ])->assertRedirect();

    $model = AiModel::query()->where('slug', 'claude-test')->first();

    expect($model)->not->toBeNull();

    $this->actingAs($agent)
        ->get(route('admin.ai.models.show', $model))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/AI/Models/Show'));
});

test('agent can create ai employees', function () {
    $agent = createAiAgent();
    $provider = AiProvider::factory()->create();
    $model = AiModel::factory()->for($provider, 'provider')->create();

    $this->actingAs($agent)->post(route('admin.ai.employees.store'), [
        'name' => 'Sam — Email Agent',
        'role' => AiEmployeeRole::Email->value,
        'department' => 'Sales',
        'description' => 'Handles outbound email.',
        'system_prompt' => 'You are an email sales agent.',
        'knowledge_sources' => ['services', 'lead'],
        'memory_enabled' => true,
        'context_window' => 8192,
        'temperature' => 0.7,
        'ai_provider_id' => $provider->id,
        'ai_model_id' => $model->id,
        'language' => 'en',
        'status' => AiEmployeeStatus::Training->value,
    ])->assertRedirect();

    $employee = AiEmployee::query()->where('name', 'Sam — Email Agent')->first();

    expect($employee)
        ->not->toBeNull()
        ->role->toBe(AiEmployeeRole::Email)
        ->created_by->toBe($agent->id);
});

test('agent can manage prompt templates and knowledge base entries', function () {
    $agent = createAiAgent();

    $this->actingAs($agent)->post(route('admin.ai.prompts.store'), [
        'name' => 'Objection Handler',
        'category' => PromptTemplateCategory::ObjectionHandling->value,
        'content' => 'When the lead says {{objection}}, respond with empathy.',
        'tags' => ['sales'],
        'status' => PromptTemplateStatus::Active->value,
    ])->assertRedirect();

    $template = PromptTemplate::query()->where('slug', 'objection-handler')->first();
    expect($template)->not->toBeNull();

    $this->actingAs($agent)->post(route('admin.ai.knowledge.store'), [
        'name' => 'Company Overview',
        'category' => KnowledgeBaseCategory::Company->value,
        'content' => 'We are a digital agency specializing in web and AI.',
        'status' => KnowledgeBaseStatus::Active->value,
    ])->assertRedirect();

    $entry = KnowledgeBase::query()->where('slug', 'company-overview')->first();
    expect($entry)->not->toBeNull();
});

test('agent can view ai logs but not create them via admin routes', function () {
    $agent = createAiAgent();
    $provider = AiProvider::factory()->create();
    $model = AiModel::factory()->for($provider, 'provider')->create();
    $employee = AiEmployee::factory()->withProviderSetup($provider, $model)->create();

    $log = AiLog::query()->create([
        'uuid' => (string) Str::uuid(),
        'ai_employee_id' => $employee->id,
        'ai_provider_id' => $provider->id,
        'ai_model_id' => $model->id,
        'request_type' => AiRequestType::Chat,
        'prompt_tokens' => 100,
        'completion_tokens' => 50,
        'total_tokens' => 150,
        'cost_usd' => 0.001,
        'latency_ms' => 500,
        'status' => AiLogStatus::Success,
    ]);

    $this->actingAs($agent)
        ->get(route('admin.ai.logs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/AI/Logs/Index'));

    $this->actingAs($agent)
        ->get(route('admin.ai.logs.show', $log))
        ->assertOk();

    $this->actingAs($agent)
        ->post('/admin/ai/logs')
        ->assertMethodNotAllowed();
});

test('agent can delete ai provider', function () {
    $agent = createAiAgent();
    $provider = AiProvider::factory()->create(['slug' => 'gemini', 'name' => 'Gemini']);

    $this->actingAs($agent)
        ->delete(route('admin.ai.providers.destroy', $provider))
        ->assertRedirect(route('admin.ai.providers.index'));

    expect(AiProvider::query()->find($provider->id))->toBeNull();
});
