<?php

namespace App\Support;

use BackedEnum;

class EmailAdminOptions
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
        return collect(array_keys(config('email_platform.provider_drivers', [])))
            ->map(fn (string $slug) => [
                'value' => $slug,
                'label' => match ($slug) {
                    'smtp' => 'SMTP (Laravel Mail)',
                    'resend' => 'Resend',
                    'mailgun' => 'Mailgun',
                    'log' => 'Log (development)',
                    default => ucfirst($slug),
                },
            ])
            ->values()
            ->all();
    }
}
