<?php

namespace Knobik\SqlAgent\Console\Commands;

use Illuminate\Console\Command;

class RunEvalsCommand extends Command
{
    protected $signature = 'sql-agent:eval
                            {--category= : Test category to run}
                            {--llm-grader : Use LLM grading instead of exact match}';

    protected $description = 'Run evaluation tests';

    public function handle(): int
    {
        $this->warn('This command will be implemented in Phase 8.');

        return self::SUCCESS;
    }
}
