<?php

namespace Database\Seeders;

use App\Domains\AI\Enums\AiEmployeeRole;
use App\Domains\AI\Enums\AiEmployeeStatus;
use App\Domains\AI\Enums\AiModelStatus;
use App\Domains\AI\Enums\AiProviderStatus;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            ['slug' => 'openai', 'name' => 'OpenAI', 'priority' => 10],
            ['slug' => 'anthropic', 'name' => 'Anthropic', 'priority' => 20],
            ['slug' => 'gemini', 'name' => 'Google Gemini', 'priority' => 30],
            ['slug' => 'openrouter', 'name' => 'OpenRouter', 'priority' => 40],
            ['slug' => 'grok', 'name' => 'Grok (xAI)', 'priority' => 50],
        ];

        foreach ($definitions as $definition) {
            AiProvider::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    ...$definition,
                    'api_key' => null,
                    'timeout_seconds' => 30,
                    'retry_count' => 2,
                    'status' => AiProviderStatus::Disabled,
                ],
            );
        }

        $this->seedModels();
        $this->seedDefaultEmployee();
    }

    private function seedModels(): void
    {
        $catalog = [
            'openai' => [
                ['slug' => 'gpt-4o', 'name' => 'GPT-4o', 'input' => 0.0025, 'output' => 0.01, 'max_tokens' => 128000],
                ['slug' => 'gpt-4o-mini', 'name' => 'GPT-4o Mini', 'input' => 0.00015, 'output' => 0.0006, 'max_tokens' => 128000],
            ],
            'anthropic' => [
                ['slug' => 'claude-sonnet-4-20250514', 'name' => 'Claude Sonnet 4', 'input' => 0.003, 'output' => 0.015, 'max_tokens' => 200000],
                ['slug' => 'claude-haiku-4-20250514', 'name' => 'Claude Haiku 4', 'input' => 0.0008, 'output' => 0.004, 'max_tokens' => 200000],
            ],
            'gemini' => [
                ['slug' => 'gemini-2.0-flash', 'name' => 'Gemini 2.0 Flash', 'input' => 0.0001, 'output' => 0.0004, 'max_tokens' => 1000000],
            ],
            'openrouter' => [
                ['slug' => 'openrouter/auto', 'name' => 'OpenRouter Auto', 'input' => 0.001, 'output' => 0.002, 'max_tokens' => 128000],
            ],
            'grok' => [
                ['slug' => 'grok-2-latest', 'name' => 'Grok 2', 'input' => 0.002, 'output' => 0.01, 'max_tokens' => 131072],
            ],
        ];

        foreach ($catalog as $providerSlug => $models) {
            $provider = AiProvider::query()->where('slug', $providerSlug)->first();

            if ($provider === null) {
                continue;
            }

            foreach ($models as $model) {
                AiModel::query()->updateOrCreate(
                    [
                        'ai_provider_id' => $provider->id,
                        'slug' => $model['slug'],
                    ],
                    [
                        'name' => $model['name'],
                        'capabilities' => ['text' => true, 'vision' => false, 'tools' => true, 'voice' => false],
                        'max_tokens' => $model['max_tokens'],
                        'input_price_per_1k' => $model['input'],
                        'output_price_per_1k' => $model['output'],
                        'status' => AiModelStatus::Active,
                    ],
                );
            }
        }
    }

    private function seedDefaultEmployee(): void
    {
        $authorId = User::query()->where('email', 'superadmin@example.com')->value('id');
        $openAi = AiProvider::query()->where('slug', 'openai')->first();
        $model = $openAi
            ? AiModel::query()->where('ai_provider_id', $openAi->id)->where('slug', 'gpt-4o-mini')->first()
            : null;

        AiEmployee::query()->updateOrCreate(
            ['name' => 'Alex — Voice Sales Agent'],
            [
                'role' => AiEmployeeRole::VoiceSales,
                'department' => 'Sales',
                'description' => 'Outbound voice agent for qualified CRM leads. Uses service catalog and lead context.',
                'system_prompt' => 'You are a professional sales agent for a digital agency. Be helpful, concise, and never invent services or pricing not in the knowledge base.',
                'behavior_prompt' => 'Qualify the lead, recommend one relevant service, handle objections calmly, and suggest booking a call when interest is clear.',
                'knowledge_sources' => ['services', 'knowledge_bases', 'lead'],
                'allowed_actions' => ['schedule_meeting', 'log_outcome'],
                'allowed_tools' => [],
                'memory_enabled' => true,
                'context_window' => 8192,
                'temperature' => 0.7,
                'ai_provider_id' => $openAi?->id,
                'ai_model_id' => $model?->id,
                'language' => 'en',
                'status' => AiEmployeeStatus::Training,
                'created_by' => $authorId,
                'updated_by' => $authorId,
            ],
        );
    }
}
