<?php

namespace App\Domains\AI\Enums;

enum AiProviderStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
    case Degraded = 'degraded';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
