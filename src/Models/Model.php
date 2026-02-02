<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

abstract class Model extends EloquentModel
{
    public function getConnectionName(): ?string
    {
        return config('sql-agent.database.storage_connection')
            ?? parent::getConnectionName();
    }
}
