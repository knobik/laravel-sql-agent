<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Agent;

use Knobik\SqlAgent\Contracts\Tool;

class AgentLoopContext
{
    /**
     * @param  string  $systemPrompt  The rendered system prompt
     * @param  array  $messages  The initial message array for the LLM
     * @param  Tool[]  $tools  The prepared tools for the connection
     * @param  int  $maxIterations  Maximum agent loop iterations
     */
    public function __construct(
        public readonly string $systemPrompt,
        public readonly array $messages,
        public readonly array $tools,
        public readonly int $maxIterations,
    ) {}
}
