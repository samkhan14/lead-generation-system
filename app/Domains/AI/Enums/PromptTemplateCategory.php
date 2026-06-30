<?php

namespace App\Domains\AI\Enums;

enum PromptTemplateCategory: string
{
    case System = 'system';
    case Behavior = 'behavior';
    case Task = 'task';
    case ObjectionHandling = 'objection_handling';
    case Outreach = 'outreach';
    case Summarization = 'summarization';
    case Tool = 'tool';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
