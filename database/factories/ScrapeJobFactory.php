<?php

namespace Database\Factories;

use App\Enums\ScrapeJobStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScrapeJob>
 */
class ScrapeJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'keyword' => $this->faker->word(),
            'industry' => null,
            'country' => $this->faker->country(),
            'city' => $this->faker->city(),
            'area' => null,
            'status' => ScrapeJobStatus::Pending,
            'max_results' => 20,
            'total_found' => 0,
            'created_count' => 0,
            'duplicate_count' => 0,
            'failed_count' => 0,
        ];
    }

    public function running(): static
    {
        return $this->state(fn () => [
            'status' => ScrapeJobStatus::Running,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => ScrapeJobStatus::Completed,
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
            'scraper_used' => 'playwright',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => ScrapeJobStatus::Failed,
            'completed_at' => now(),
            'error_message' => 'Scraper failed.',
        ]);
    }
}
