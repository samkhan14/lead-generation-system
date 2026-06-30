<?php

namespace App\Domains\AI\Enums;

enum AiRequestType: string
{
    case Chat = 'chat';
    case Completion = 'completion';
    case ToolCall = 'tool_call';
    case Voice = 'voice';
    case Structured = 'structured';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
