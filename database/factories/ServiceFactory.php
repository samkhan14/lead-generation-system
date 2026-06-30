<?php

namespace Database\Factories;

use App\Domains\BusinessKnowledge\Enums\ServiceStatus;
use App\Domains\BusinessKnowledge\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'uuid' => (string) Str::uuid(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'features' => fake()->sentences(3),
            'benefits' => fake()->sentences(2),
            'deliverables' => ['Discovery call', 'Implementation', 'Handoff documentation'],
            'pricing_notes' => fake()->sentence(),
            'faqs' => [
                ['question' => 'How long does delivery take?', 'answer' => 'Typically 2-4 weeks depending on scope.'],
            ],
            'objections' => [
                ['objection' => 'It is too expensive.', 'response' => 'We can phase the work to match your budget.'],
            ],
            'cross_sell_ids' => [],
            'upsell_ids' => [],
            'tags' => ['web', 'marketing'],
            'status' => ServiceStatus::Active,
            'version' => 1,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ServiceStatus::Draft]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => ServiceStatus::Archived]);
    }
}
