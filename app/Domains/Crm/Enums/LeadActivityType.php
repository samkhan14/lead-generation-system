<?php

namespace App\Domains\Crm\Enums;

enum LeadActivityType: string
{
    case Note = 'note';
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case StatusChange = 'status_change';
    case Assignment = 'assignment';
    case DealCreated = 'deal_created';
    case TaskCreated = 'task_created';
    case QuoteSent = 'quote_sent';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Call => 'Call',
            self::Email => 'Email',
            self::Meeting => 'Meeting',
            self::StatusChange => 'Status change',
            self::Assignment => 'Assignment',
            self::DealCreated => 'Deal created',
            self::TaskCreated => 'Task created',
            self::QuoteSent => 'Quote sent',
            self::System => 'System',
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
