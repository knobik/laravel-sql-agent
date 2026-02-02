<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Data;

use Spatie\LaravelData\Data;

class RelationshipInfo extends Data
{
    public function __construct(
        public string $type,
        public string $relatedTable,
        public string $foreignKey,
        public ?string $localKey = null,
        public ?string $pivotTable = null,
        public ?string $description = null,
    ) {}

    public function toPromptString(): string
    {
        $localKey = $this->localKey ?? 'id';
        $description = $this->description ? " - {$this->description}" : '';

        return match (strtolower($this->type)) {
            'hasone', 'has_one' => "hasOne {$this->relatedTable} via {$this->relatedTable}.{$this->foreignKey}{$description}",
            'hasmany', 'has_many' => "hasMany {$this->relatedTable} via {$this->relatedTable}.{$this->foreignKey}{$description}",
            'belongsto', 'belongs_to' => "belongsTo {$this->relatedTable} via {$this->foreignKey} -> {$this->relatedTable}.{$localKey}{$description}",
            'belongstomany', 'belongs_to_many' => "belongsToMany {$this->relatedTable} via {$this->pivotTable}{$description}",
            default => "{$this->type} {$this->relatedTable} via {$this->foreignKey}{$description}",
        };
    }

    public function isHasOne(): bool
    {
        return in_array(strtolower($this->type), ['hasone', 'has_one']);
    }

    public function isHasMany(): bool
    {
        return in_array(strtolower($this->type), ['hasmany', 'has_many']);
    }

    public function isBelongsTo(): bool
    {
        return in_array(strtolower($this->type), ['belongsto', 'belongs_to']);
    }

    public function isBelongsToMany(): bool
    {
        return in_array(strtolower($this->type), ['belongstomany', 'belongs_to_many']);
    }
}
