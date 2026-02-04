<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SqlErrorOccurred
{
    use Dispatchable;

    public function __construct(
        public string $sql,
        public string $error,
        public string $question,
        public ?string $connection = null,
    ) {}
}
