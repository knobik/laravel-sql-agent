<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Agent;

use Prism\Prism\Contracts\Message;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class MessageBuilder
{
    /**
     * Build Prism message objects for the conversation.
     *
     * The system prompt is handled separately via withSystemPrompt() on the Prism request.
     *
     * @param  string  $question  The current user question
     * @param  array  $history  Previous conversation messages with 'role' and 'content'
     * @return Message[]
     */
    public function buildPrismMessages(string $question, array $history = []): array
    {
        $messages = [];

        foreach ($history as $msg) {
            $role = $msg['role'] ?? '';
            $content = $msg['content'] ?? '';

            $messages[] = match ($role) {
                'user' => new UserMessage($content),
                'assistant' => new AssistantMessage($content),
                default => null,
            };
        }

        // Filter out nulls (skip system/tool messages from history)
        $messages = array_values(array_filter($messages));

        // Add current question
        $messages[] = new UserMessage($question);

        return $messages;
    }
}
