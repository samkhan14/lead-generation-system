<?php

namespace App\Enums;

enum ScrapeJobStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Running => 'Running',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed]);
    }
}
