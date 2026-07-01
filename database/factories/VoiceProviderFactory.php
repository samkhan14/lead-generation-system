<?php

namespace Database\Factories;

use App\Domains\Voice\Enums\VoiceProviderStatus;
use App\Domains\Voice\Models\VoiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoiceProvider>
 */
class VoiceProviderFactory extends Factory
{
    protected $model = VoiceProvider::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => 'retell',
            'name' => 'Retell AI',
            'api_key' => 'key_'.fake()->uuid(),
            'api_base_url' => null,
            'priority' => 10,
            'timeout_seconds' => 30,
            'retry_count' => 2,
            'status' => VoiceProviderStatus::Active,
            'metadata' => [
                'default_from_number' => '+14155550100',
                'agent_id' => 'agent_'.fake()->uuid(),
            ],
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => VoiceProviderStatus::Disabled]);
    }
}
