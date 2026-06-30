<?php

use App\Domains\AI\Contracts\AiTextGeneratorInterface;
use App\Domains\AI\Enums\AiLogStatus;
use App\Domains\AI\Enums\KnowledgeBaseCategory;
use App\Domains\AI\Enums\KnowledgeBaseStatus;
use App\Domains\AI\Events\AiRequestSent;
use App\Domains\AI\Events\AiResponseReceived;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiLog;
use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use App\Domains\AI\Models\KnowledgeBase;
use App\Domains\AI\Services\AiGateway;
use App\Domains\AI\Services\ContextBuilder;
use App\Domains\AI\Services\CostTracker;
use App\Domains\AI\Services\PromptBuilder;
use App\Domains\AI\Services\ProviderSelector;
use App\Domains\BusinessKnowledge\Enums\ServiceStatus;
use App\Domains\BusinessKnowledge\Models\Service;
use App\Models\Lead;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\TextResponse;
use Tests\Support\AI\FakeAiTextGenerator;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function createAiStack(): array
{
    $primaryProvider = AiProvider::factory()->create(['slug' => 'openai', 'priority' => 10]);
    $primaryModel = AiModel::factory()->for($primaryProvider, 'provider')->create();
    $fallbackProvider = AiProvider::factory()->create([
        'slug' => 'anthropic',
        'priority' => 20,
        'retry_count' => 0,
    ]);
    $fallbackModel = AiModel::factory()->for($fallbackProvider, 'provider')->create([
        'slug' => 'claude-haiku',
    ]);

    config([
        'ai_platform.provider_drivers' => [
            'openai' => 'openai',
            'anthropic' => 'anthropic',
        ],
    ]);

    $employee = AiEmployee::factory()->create([
        'ai_provider_id' => $primaryProvider->id,
        'ai_model_id' => $primaryModel->id,
        'fallback_provider_id' => $fallbackProvider->id,
        'fallback_model_id' => $fallbackModel->id,
        'knowledge_sources' => ['services', 'knowledge_bases', 'lead'],
    ]);

    return compact('primaryProvider', 'primaryModel', 'fallbackProvider', 'fallbackModel', 'employee');
}

test('context builder loads active services and knowledge for employee', function () {
    $this->seed(ServiceSeeder::class);

    KnowledgeBase::query()->create([
        'slug' => 'company-overview',
        'name' => 'Company Overview',
        'category' => KnowledgeBaseCategory::Company,
        'content' => 'We build websites and AI systems.',
        'status' => KnowledgeBaseStatus::Active,
    ]);

    Service::query()->where('status', ServiceStatus::Active)->update(['status' => ServiceStatus::Archived]);
    Service::query()->where('slug', 'basic-website')->update(['status' => ServiceStatus::Active]);

    $employee = AiEmployee::factory()->create([
        'knowledge_sources' => ['services', 'knowledge_bases'],
    ]);

    $context = app(ContextBuilder::class)->build($employee);

    expect($context->services)->not->toBeEmpty()
        ->and(collect($context->services)->pluck('slug'))->toContain('basic-website')
        ->and($context->knowledgeArticles)->toHaveCount(1)
        ->and($context->lead)->toBeNull();
});

test('prompt builder injects service catalog into instructions', function () {
    Service::query()->create([
        'name' => 'Premium Website',
        'slug' => 'premium-website-test',
        'description' => 'Top tier websites.',
        'status' => ServiceStatus::Active,
        'version' => 1,
    ]);

    $context = app(ContextBuilder::class)->build(
        AiEmployee::factory()->create([
            'system_prompt' => 'You sell digital services.',
            'knowledge_sources' => ['services'],
        ]),
    );

    $instructions = app(PromptBuilder::class)->buildInstructions($context);

    expect($instructions)->toContain('You sell digital services.')
        ->and($instructions)->toContain('Premium Website')
        ->and($instructions)->toContain('Do not invent offerings');
});

test('provider selector returns primary and fallback selections', function () {
    ['employee' => $employee, 'primaryProvider' => $primaryProvider] = createAiStack();

    $primary = app(ProviderSelector::class)->primary($employee);
    $fallback = app(ProviderSelector::class)->fallback($employee);

    expect($primary->provider->is($primaryProvider))->toBeTrue()
        ->and($primary->isFallback)->toBeFalse()
        ->and($fallback)->not->toBeNull()
        ->and($fallback->isFallback)->toBeTrue();
});

test('cost tracker calculates token cost from model pricing', function () {
    $provider = AiProvider::factory()->create();
    $model = AiModel::factory()->for($provider, 'provider')->create([
        'input_price_per_1k' => 1.0,
        'output_price_per_1k' => 2.0,
    ]);

    $cost = app(CostTracker::class)->calculate($model, 1000, 500);

    expect($cost)->toBe(2.0);
});

test('ai gateway sends prompt, logs success, and returns response', function () {
    ['employee' => $employee] = createAiStack();

    $fake = new FakeAiTextGenerator;
    $this->app->instance(AiTextGeneratorInterface::class, $fake);

    $lead = Lead::query()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'company' => 'Acme Co',
        'source' => 'manual',
    ]);

    $context = app(ContextBuilder::class)->build(
        employee: $employee,
        lead: $lead,
        userMessage: 'What should I pitch to this lead?',
    );

    Event::fake();

    $response = app(AiGateway::class)->send($employee, $context);

    expect($response->success)->toBeTrue()
        ->and($response->content)->toContain('website audit')
        ->and($response->promptTokens)->toBe(120)
        ->and($response->costUsd)->toBeGreaterThan(0)
        ->and($response->log)->toBeInstanceOf(AiLog::class)
        ->and($response->log->status)->toBe(AiLogStatus::Success)
        ->and($response->log->lead_id)->toBe($lead->id);

    Event::assertDispatched(AiRequestSent::class);
    Event::assertDispatched(AiResponseReceived::class);
});

test('ai gateway falls back when primary provider fails', function () {
    ['employee' => $employee, 'fallbackProvider' => $fallbackProvider] = createAiStack();

    $fake = new FakeAiTextGenerator;
    $fake->push(new RuntimeException('Primary provider unavailable'));
    $fake->push(new TextResponse(
        'Fallback response content.',
        new Usage(50, 25),
        new Meta('anthropic', 'claude-haiku'),
    ));

    $this->app->instance(AiTextGeneratorInterface::class, $fake);

    $context = app(ContextBuilder::class)->build(
        employee: $employee,
        userMessage: 'Hello lead',
    );

    $response = app(AiGateway::class)->send($employee, $context);

    expect($response->success)->toBeTrue()
        ->and($response->usedFallback)->toBeTrue()
        ->and($response->providerSlug)->toBe($fallbackProvider->slug)
        ->and($response->content)->toBe('Fallback response content.');

    expect(AiLog::query()->count())->toBe(2);
});

test('ai gateway rejects empty user message', function () {
    ['employee' => $employee] = createAiStack();

    $this->app->instance(AiTextGeneratorInterface::class, new FakeAiTextGenerator);

    $context = app(ContextBuilder::class)->build($employee, userMessage: '   ');

    $response = app(AiGateway::class)->send($employee, $context);

    expect($response->success)->toBeFalse()
        ->and($response->error)->toBe('User message is required.');
});
