<?php

namespace App\Domains\Voice\Enums;

enum VoiceCallStatus: string
{
    case Queued = 'queued';
    case Ringing = 'ringing';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case NoAnswer = 'no_answer';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Completed,
            self::Failed,
            self::Cancelled,
            self::NoAnswer,
        ], true);
    }
}
