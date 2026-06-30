<?php

namespace Database\Factories;

use App\Domains\AI\Enums\AiModelStatus;
use App\Domains\AI\Models\AiModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiModel>
 */
class AiModelFactory extends Factory
{
    protected $model = AiModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => 'gpt-4o-mini',
            'name' => 'GPT-4o Mini',
            'capabilities' => [
                'text' => true,
                'vision' => false,
                'tools' => true,
                'voice' => false,
            ],
            'max_tokens' => 8192,
            'input_price_per_1k' => 0.15,
            'output_price_per_1k' => 0.60,
            'status' => AiModelStatus::Active,
        ];
    }
}
