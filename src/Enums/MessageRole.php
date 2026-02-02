<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Enums;

enum MessageRole: string
{
    case User = 'user';
    case Assistant = 'assistant';
    case System = 'system';
    case Tool = 'tool';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Assistant => 'Assistant',
            self::System => 'System',
            self::Tool => 'Tool',
        };
    }

    public function isFromUser(): bool
    {
        return $this === self::User;
    }

    public function isFromAssistant(): bool
    {
        return $this === self::Assistant;
    }
}
