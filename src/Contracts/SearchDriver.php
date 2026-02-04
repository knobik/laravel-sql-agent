<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Contracts;

use Illuminate\Support\Collection;
use Knobik\SqlAgent\Search\SearchResult;

interface SearchDriver
{
    /**
     * Search for similar items in an index.
     *
     * @param  string  $query  The search query
     * @param  string  $index  The index/collection name to search
     * @param  int  $limit  Maximum number of results to return
     * @return Collection<int, SearchResult> Collection of search results
     */
    public function search(string $query, string $index, int $limit = 10): Collection;

    /**
     * Search across multiple indexes.
     *
     * @param  string  $query  The search query
     * @param  array<string>  $indexes  The indexes to search
     * @param  int  $limit  Maximum number of results to return
     * @return Collection<int, SearchResult> Collection of search results
     */
    public function searchMultiple(string $query, array $indexes, int $limit = 10): Collection;

    /**
     * Index a model for searching.
     *
     * @param  mixed  $model  The model to index
     */
    public function index(mixed $model): void;

    /**
     * Remove a model from the search index.
     *
     * @param  mixed  $model  The model to remove
     */
    public function delete(mixed $model): void;
}
