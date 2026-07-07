<?php

namespace App\Domains\Email\Enums;

enum EmailSendStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Queued => 'Queued',
            self::Sending => 'Sending',
            self::Sent => 'Sent',
            self::Failed => 'Failed',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Sent, self::Failed], true);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
