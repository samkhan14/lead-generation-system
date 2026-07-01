<?php

namespace App\Domains\BusinessKnowledge\Enums;

enum ServiceComplexity: string
{
    case Starter = 'starter';
    case Moderate = 'moderate';
    case Complex = 'complex';
    case Enterprise = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::Starter => 'Starter',
            self::Moderate => 'Moderate',
            self::Complex => 'Complex',
            self::Enterprise => 'Enterprise',
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
