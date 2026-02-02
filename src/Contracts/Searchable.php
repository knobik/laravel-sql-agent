<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Contracts;

interface Searchable
{
    /**
     * Get the columns that should be indexed for search.
     *
     * @return array<string>
     */
    public function getSearchableColumns(): array;

    /**
     * Get the searchable representation of the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array;
}
