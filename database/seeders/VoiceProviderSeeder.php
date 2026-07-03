<?php

namespace Database\Seeders;

use App\Domains\Voice\Enums\VoiceProviderStatus;
use App\Domains\Voice\Models\VoiceProvider;
use Illuminate\Database\Seeder;

class VoiceProviderSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'slug' => 'retell',
                'name' => 'Retell AI',
                'priority' => 10,
                'metadata' => [
                    'default_from_number' => null,
                    'agent_id' => null,
                ],
            ],
            [
                'slug' => 'livekit',
                'name' => 'LiveKit',
                'priority' => 20,
                'metadata' => [
                    'default_from_number' => null,
                    'outbound_path' => '/sip/outbound',
                    'fetch_path' => '/sip/calls/',
                    'cancel_path' => '/sip/calls/',
                ],
            ],
            [
                'slug' => 'vapi',
                'name' => 'Vapi',
                'priority' => 30,
                'metadata' => [
                    'phone_number_id' => null,
                    'agent_id' => null,
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            $slug = $definition['slug'];
            $defaults = config("voice_platform.provider_defaults.{$slug}", []);

            VoiceProvider::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    ...$definition,
                    'api_key' => null,
                    'api_base_url' => $defaults['api_base_url'] ?? null,
                    'timeout_seconds' => 30,
                    'retry_count' => 2,
                    'status' => VoiceProviderStatus::Active,
                ],
            );
        }
    }
}
