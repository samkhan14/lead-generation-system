<?php

namespace App\Support;

use BackedEnum;

class VoiceAdminOptions
{
    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return array<int, array{value: string, label: string}>
     */
    public static function enumOptions(string $enumClass): array
    {
        return AiAdminOptions::enumOptions($enumClass);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function providerSlugs(): array
    {
        return collect(array_keys(config('voice_platform.provider_drivers', [])))
            ->map(fn (string $slug) => [
                'value' => $slug,
                'label' => match ($slug) {
                    'retell' => 'Retell AI',
                    'livekit' => 'LiveKit',
                    'vapi' => 'Vapi',
                    default => ucfirst($slug),
                },
            ])
            ->values()
            ->all();
    }
}
