<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Enums;

enum LearningCategory: string
{
    case TypeError = 'type_error';
    case SchemaFix = 'schema_fix';
    case QueryPattern = 'query_pattern';
    case DataQuality = 'data_quality';
    case BusinessLogic = 'business_logic';

    public function label(): string
    {
        return match ($this) {
            self::TypeError => 'Type Error',
            self::SchemaFix => 'Schema Fix',
            self::QueryPattern => 'Query Pattern',
            self::DataQuality => 'Data Quality',
            self::BusinessLogic => 'Business Logic',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TypeError => 'A correction for a data type mismatch or casting issue',
            self::SchemaFix => 'A correction for incorrect schema assumptions',
            self::QueryPattern => 'A learned pattern for constructing queries',
            self::DataQuality => 'An observation about data quality or anomalies',
            self::BusinessLogic => 'A learned business rule or logic',
        };
    }
}
