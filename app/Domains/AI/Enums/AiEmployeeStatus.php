<?php

namespace App\Domains\AI\Enums;

enum AiEmployeeStatus: string
{
    case Active = 'active';
    case Training = 'training';
    case Disabled = 'disabled';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
