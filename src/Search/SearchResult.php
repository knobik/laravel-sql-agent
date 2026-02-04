<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Search;

use Illuminate\Database\Eloquent\Model;

/**
 * DTO for standardized search results.
 */
readonly class SearchResult
{
    public function __construct(
        public Model $model,
        public float $score,
        public string $index,
    ) {}

    /**
     * Create a SearchResult from a model with optional score.
     */
    public static function fromModel(Model $model, string $index, float $score = 0.0): self
    {
        return new self($model, $score, $index);
    }
}
