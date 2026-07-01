<?php

namespace Database\Factories;

use App\Domains\Voice\Enums\VoiceCallDirection;
use App\Domains\Voice\Enums\VoiceCallStatus;
use App\Domains\Voice\Models\VoiceCall;
use App\Domains\Voice\Models\VoiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VoiceCall>
 */
class VoiceCallFactory extends Factory
{
    protected $model = VoiceCall::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'voice_provider_id' => VoiceProvider::factory(),
            'external_call_id' => 'call_'.fake()->uuid(),
            'direction' => VoiceCallDirection::Outbound,
            'from_number' => '+14155550100',
            'to_number' => '+14155550199',
            'status' => VoiceCallStatus::Queued,
        ];
    }
}
