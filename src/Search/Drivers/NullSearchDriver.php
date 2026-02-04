<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Search\Drivers;

use Illuminate\Support\Collection;
use Knobik\SqlAgent\Contracts\SearchDriver;
use Knobik\SqlAgent\Search\SearchResult;

/**
 * Null search driver for testing and disabled search scenarios.
 */
class NullSearchDriver implements SearchDriver
{
    /**
     * @return Collection<int, SearchResult>
     */
    public function search(string $query, string $index, int $limit = 10): Collection
    {
        return collect();
    }

    /**
     * Search across multiple indexes.
     *
     * @param  array<string>  $indexes
     * @return Collection<int, SearchResult>
     */
    public function searchMultiple(string $query, array $indexes, int $limit = 10): Collection
    {
        return collect();
    }

    public function index(mixed $model): void
    {
        // No-op
    }

    public function delete(mixed $model): void
    {
        // No-op
    }
}
