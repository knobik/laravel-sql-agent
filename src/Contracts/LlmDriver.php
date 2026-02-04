<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Contracts;

use Generator;

interface LlmDriver
{
    /**
     * Send a chat completion request.
     *
     * @param  array  $messages  Array of message objects with 'role' and 'content'
     * @param  array  $tools  Array of tool definitions
     */
    public function chat(array $messages, array $tools = []): LlmResponse;

    /**
     * Stream a chat completion request.
     *
     * @param  array  $messages  Array of message objects with 'role' and 'content'
     * @param  array  $tools  Array of tool definitions
     * @return Generator<string> Yields content chunks
     */
    public function stream(array $messages, array $tools = []): Generator;

    /**
     * Check if the driver supports tool calling.
     */
    public function supportsToolCalling(): bool;
}
