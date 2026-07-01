<?php

namespace App\Domains\Voice\Enums;

enum VoiceCallDirection: string
{
    case Outbound = 'outbound';
    case Inbound = 'inbound';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
