<?php

use App\Domains\AI\Enums\AiEmployeeStatus;
use App\Domains\AI\Enums\AiProviderStatus;
use App\Domains\AI\Enums\KnowledgeBaseStatus;
use App\Domains\AI\Enums\PromptTemplateStatus;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use App\Domains\AI\Models\KnowledgeBase;
use App\Domains\AI\Models\PromptTemplate;
use App\Domains\Voice\Enums\VoiceProviderStatus;
use App\Domains\Voice\Models\VoiceProvider;
use Database\Seeders\AiProviderSeeder;
use Database\Seeders\AiWorkforceCatalogSeeder;
use Database\Seeders\KnowledgeBaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\VoiceProviderSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('ai and voice seeders enable platform without api keys', function () {
    $this->seed(AiProviderSeeder::class);
    $this->seed(VoiceProviderSeeder::class);
    $this->seed(KnowledgeBaseSeeder::class);
    $this->seed(AiWorkforceCatalogSeeder::class);

    expect(AiProvider::query()->where('status', AiProviderStatus::Active)->count())->toBe(5)
        ->and(AiProvider::query()->whereNotNull('api_key')->count())->toBe(0)
        ->and(AiModel::query()->where('status', 'active')->count())->toBeGreaterThan(0)
        ->and(VoiceProvider::query()->where('status', VoiceProviderStatus::Active)->count())->toBe(3)
        ->and(VoiceProvider::query()->whereNotNull('api_key')->count())->toBe(0);

    $alex = AiEmployee::query()->where('name', 'Alex — Voice Sales Agent')->first();

    expect($alex)->not->toBeNull()
        ->and($alex->status)->toBe(AiEmployeeStatus::Active)
        ->and($alex->ai_provider_id)->not->toBeNull()
        ->and($alex->ai_model_id)->not->toBeNull();

    expect(KnowledgeBase::query()->where('status', KnowledgeBaseStatus::Active)->count())->toBe(15)
        ->and(PromptTemplate::query()->where('status', PromptTemplateStatus::Active)->count())->toBe(6);
});

test('active voice providers without keys are not usable for calls', function () {
    $this->seed(VoiceProviderSeeder::class);

    $retell = VoiceProvider::query()->where('slug', 'retell')->first();

    expect($retell->status)->toBe(VoiceProviderStatus::Active)
        ->and($retell->isUsable())->toBeFalse();
});

test('active ai providers without keys are not usable for gateway', function () {
    $this->seed(AiProviderSeeder::class);

    $openAi = AiProvider::query()->where('slug', 'openai')->first();

    expect($openAi->status)->toBe(AiProviderStatus::Active)
        ->and($openAi->isUsable())->toBeFalse();
});

test('service catalog seeds active business knowledge', function () {
    $this->seed(ServiceSeeder::class);

    expect(\App\Domains\BusinessKnowledge\Models\Service::query()
        ->where('status', \App\Domains\BusinessKnowledge\Enums\ServiceStatus::Active)
        ->count())->toBeGreaterThan(3);
});
