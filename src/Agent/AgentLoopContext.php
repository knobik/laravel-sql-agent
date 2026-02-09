<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Agent;

use Prism\Prism\Contracts\Message;
use Prism\Prism\Tool;

class AgentLoopContext
{
    /**
     * @param  string  $systemPrompt  The rendered system prompt
     * @param  Message[]  $messages  Prism message objects for the conversation
     * @param  Tool[]  $tools  The prepared tools for the connection
     * @param  int  $maxIterations  Maximum agent loop iterations (Prism maxSteps)
     */
    public function __construct(
        public readonly string $systemPrompt,
        public readonly array $messages,
        public readonly array $tools,
        public readonly int $maxIterations,
    ) {}
}
