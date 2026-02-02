<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Data;

use Spatie\LaravelData\Data;

class ColumnInfo extends Data
{
    public function __construct(
        public string $name,
        public string $type,
        public ?string $description = null,
        public bool $nullable = true,
        public bool $isPrimaryKey = false,
        public bool $isForeignKey = false,
        public ?string $foreignTable = null,
        public ?string $foreignColumn = null,
        public ?string $defaultValue = null,
    ) {}

    public function toPromptString(): string
    {
        $parts = ["{$this->name} ({$this->type})"];

        if ($this->isPrimaryKey) {
            $parts[] = '[PK]';
        }

        if ($this->isForeignKey) {
            $parts[] = "[FK -> {$this->foreignTable}.{$this->foreignColumn}]";
        }

        if (! $this->nullable) {
            $parts[] = 'NOT NULL';
        }

        if ($this->description) {
            $parts[] = "- {$this->description}";
        }

        return implode(' ', $parts);
    }
}
