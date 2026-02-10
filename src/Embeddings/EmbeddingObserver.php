<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Embeddings;

use Illuminate\Database\Eloquent\Model;
use Knobik\SqlAgent\Contracts\Searchable;
use Knobik\SqlAgent\Search\Drivers\PgvectorSearchDriver;
use Throwable;

class EmbeddingObserver
{
    public function __construct(
        protected PgvectorSearchDriver $driver,
    ) {}

    public function created(Model $model): void
    {
        if (! ($model instanceof Searchable)) {
            return;
        }

        try {
            $this->driver->index($model);
        } catch (Throwable) {
            // Embedding failure must not block model operations
        }
    }

    public function updated(Model $model): void
    {
        if (! ($model instanceof Searchable)) {
            return;
        }

        // Only re-embed if searchable columns changed
        $searchableColumns = $model->getSearchableColumns();
        $changed = array_intersect($searchableColumns, array_keys($model->getDirty()));

        if ($changed === []) {
            return;
        }

        try {
            $this->driver->index($model);
        } catch (Throwable) {
            // Embedding failure must not block model operations
        }
    }

    public function deleted(Model $model): void
    {
        try {
            $this->driver->delete($model);
        } catch (Throwable) {
            // Embedding failure must not block model operations
        }
    }
}
