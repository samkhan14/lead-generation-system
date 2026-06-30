<?php

namespace App\Domains\AI\Enums;

enum AiEmployeeRole: string
{
    case Sales = 'sales';
    case VoiceSales = 'voice_sales';
    case Email = 'email';
    case WhatsApp = 'whatsapp';
    case Support = 'support';
    case AppointmentSetter = 'appointment_setter';
    case Proposal = 'proposal';
    case Collection = 'collection';
    case General = 'general';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Sales => 'Sales Agent',
            self::VoiceSales => 'Voice Sales Agent',
            self::Email => 'Email Agent',
            self::WhatsApp => 'WhatsApp Agent',
            self::Support => 'Support Agent',
            self::AppointmentSetter => 'Appointment Setter',
            self::Proposal => 'Proposal Agent',
            self::Collection => 'Collection Agent',
            self::General => 'General Agent',
        };
    }
}
