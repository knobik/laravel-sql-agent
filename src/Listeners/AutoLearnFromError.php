<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Listeners;

use Knobik\SqlAgent\Events\SqlErrorOccurred;
use Knobik\SqlAgent\Services\LearningMachine;

class AutoLearnFromError
{
    public function __construct(
        protected LearningMachine $learningMachine,
    ) {}

    public function handle(SqlErrorOccurred $event): void
    {
        if (! $this->learningMachine->shouldAutoLearn()) {
            return;
        }

        $this->learningMachine->learnFromError(
            $event->sql,
            $event->error,
            $event->question,
        );
    }
}
