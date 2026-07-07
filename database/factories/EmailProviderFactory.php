<?php

namespace Database\Factories;

use App\Domains\Email\Enums\EmailProviderStatus;
use App\Domains\Email\Models\EmailProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EmailProvider> */
class EmailProviderFactory extends Factory
{
    protected $model = EmailProvider::class;

    public function definition(): array
    {
        $slug = 'log';

        return [
            'uuid' => (string) Str::uuid(),
            'slug' => $slug,
            'name' => 'Log Provider',
            'api_key' => null,
            'api_base_url' => null,
            'priority' => 10,
            'timeout_seconds' => 30,
            'retry_count' => 2,
            'status' => EmailProviderStatus::Active,
            'metadata' => [
                'default_from_email' => 'test@example.com',
                'default_from_name' => 'Test Sender',
            ],
        ];
    }

    public function resend(): static
    {
        return $this->state(fn () => [
            'slug' => 'resend',
            'name' => 'Resend',
            'api_key' => 're_test_key',
            'api_base_url' => 'https://api.resend.com',
        ]);
    }
}
