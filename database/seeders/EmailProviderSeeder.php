<?php

namespace Database\Seeders;

use App\Domains\Email\Enums\EmailProviderStatus;
use App\Domains\Email\Models\EmailProvider;
use Illuminate\Database\Seeder;

class EmailProviderSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'slug' => 'log',
                'name' => 'Log (Development)',
                'priority' => 100,
                'metadata' => [
                    'default_from_email' => 'noreply@example.com',
                    'default_from_name' => 'Lead CRM',
                ],
            ],
            [
                'slug' => 'smtp',
                'name' => 'SMTP (Laravel Mail)',
                'priority' => 20,
                'metadata' => [
                    'default_from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                    'default_from_name' => env('MAIL_FROM_NAME', 'Lead CRM'),
                ],
            ],
            [
                'slug' => 'resend',
                'name' => 'Resend',
                'priority' => 10,
                'metadata' => [
                    'default_from_email' => null,
                    'default_from_name' => null,
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            $slug = $definition['slug'];
            $defaults = config("email_platform.provider_defaults.{$slug}", []);

            EmailProvider::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    ...$definition,
                    'api_key' => null,
                    'api_base_url' => $defaults['api_base_url'] ?? null,
                    'timeout_seconds' => 30,
                    'retry_count' => 2,
                    'status' => EmailProviderStatus::Active,
                ],
            );
        }
    }
}
