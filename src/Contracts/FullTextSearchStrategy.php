<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Strategy interface for database-specific full-text search implementations.
 */
interface FullTextSearchStrategy
{
    /**
     * Apply full-text search to a query builder.
     *
     * @param  Builder<Model>  $query  The query builder to modify
     * @param  string  $searchTerm  The search term to use
     * @param  array<string>  $columns  The columns to search
     * @param  int  $limit  Maximum number of results
     * @return Builder<Model> The modified query builder with ordering applied
     */
    public function apply(Builder $query, string $searchTerm, array $columns, int $limit): Builder;

    /**
     * Get the name of this search strategy.
     */
    public function getName(): string;
}
