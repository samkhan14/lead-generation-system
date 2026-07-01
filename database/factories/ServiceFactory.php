<?php

namespace Database\Factories;

use App\Domains\BusinessKnowledge\Enums\ServiceComplexity;
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
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'detailed_description' => fake()->paragraphs(2, true),
            'target_audience' => ['Startups', 'SMBs'],
            'ideal_customer_profile' => fake()->paragraph(),
            'problems_solved' => fake()->sentences(3),
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
            'discovery_questions' => ['What problem are you trying to solve?'],
            'quotation_requirements' => ['Current system overview'],
            'cross_sell_ids' => [],
            'upsell_ids' => [],
            'related_service_ids' => [],
            'tags' => ['software', 'laravel'],
            'technologies' => ['Laravel', 'PHP', 'Vue.js'],
            'complexity_level' => ServiceComplexity::Moderate,
            'typical_timeline' => '4-8 weeks',
            'sort_order' => 0,
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
