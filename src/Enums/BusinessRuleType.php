<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Enums;

enum BusinessRuleType: string
{
    case Metric = 'metric';
    case Rule = 'rule';
    case Gotcha = 'gotcha';

    public function label(): string
    {
        return match ($this) {
            self::Metric => 'Metric',
            self::Rule => 'Business Rule',
            self::Gotcha => 'Gotcha',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Metric => 'A business metric with a specific calculation or definition',
            self::Rule => 'A business rule that affects query logic',
            self::Gotcha => 'A common pitfall or gotcha to watch out for',
        };
    }
}
