<?php

namespace App\Domains\AI\Enums;

enum AiLogStatus: string
{
    case Success = 'success';
    case Error = 'error';
    case Timeout = 'timeout';
    case RateLimited = 'rate_limited';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
