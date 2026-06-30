<?php

namespace Database\Factories;

use App\Domains\AI\Enums\AiProviderStatus;
use App\Domains\AI\Models\AiProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiProvider>
 */
class AiProviderFactory extends Factory
{
    protected $model = AiProvider::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => 'openai',
            'name' => 'OpenAI',
            'api_key' => 'sk-test-'.fake()->uuid(),
            'api_base_url' => null,
            'priority' => 10,
            'rate_limit_rpm' => 60,
            'timeout_seconds' => 30,
            'retry_count' => 0,
            'status' => AiProviderStatus::Active,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => AiProviderStatus::Disabled]);
    }
}
