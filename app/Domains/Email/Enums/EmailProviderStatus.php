<?php

namespace App\Domains\Email\Enums;

enum EmailProviderStatus: string
{
    case Active = 'active';
    case Degraded = 'degraded';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Degraded => 'Degraded',
            self::Disabled => 'Disabled',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
