<?php

namespace Database\Factories;

use App\Domains\AI\Enums\AiEmployeeRole;
use App\Domains\AI\Enums\AiEmployeeStatus;
use App\Domains\AI\Models\AiEmployee;
use App\Domains\AI\Models\AiModel;
use App\Domains\AI\Models\AiProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AiEmployee>
 */
class AiEmployeeFactory extends Factory
{
    protected $model = AiEmployee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->name().' AI Agent',
            'role' => AiEmployeeRole::VoiceSales,
            'department' => 'Sales',
            'description' => fake()->sentence(),
            'system_prompt' => 'You are a helpful sales agent.',
            'behavior_prompt' => 'Be concise and professional.',
            'knowledge_sources' => ['services', 'knowledge_bases', 'lead'],
            'allowed_actions' => ['schedule_meeting'],
            'allowed_tools' => [],
            'memory_enabled' => true,
            'context_window' => 8192,
            'temperature' => 0.7,
            'language' => 'en',
            'status' => AiEmployeeStatus::Active,
        ];
    }

    public function withProviderSetup(?AiProvider $provider = null, ?AiModel $model = null): static
    {
        return $this->state(function () use ($provider, $model) {
            $provider ??= AiProvider::factory()->create();
            $model ??= AiModel::factory()->for($provider, 'provider')->create();

            return [
                'ai_provider_id' => $provider->id,
                'ai_model_id' => $model->id,
            ];
        });
    }
}
