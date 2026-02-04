<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Knobik\SqlAgent\Models\Learning;

class LearningCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Learning $learning,
    ) {}
}
