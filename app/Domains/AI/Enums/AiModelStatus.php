<?php

namespace App\Domains\AI\Enums;

enum AiModelStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
