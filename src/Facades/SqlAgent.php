<?php

namespace Knobik\SqlAgent\Facades;

use Illuminate\Support\Facades\Facade;

class SqlAgent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sql-agent';
    }
}
