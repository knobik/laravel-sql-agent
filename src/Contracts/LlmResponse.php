<?php

namespace Knobik\SqlAgent\Contracts;

class LlmResponse
{
    public function __construct(
        public readonly string $content,
        public readonly array $toolCalls = [],
        public readonly ?string $finishReason = null,
        public readonly ?int $promptTokens = null,
        public readonly ?int $completionTokens = null,
    ) {}

    public function hasToolCalls(): bool
    {
        return count($this->toolCalls) > 0;
    }

    public function totalTokens(): ?int
    {
        if ($this->promptTokens === null || $this->completionTokens === null) {
            return null;
        }

        return $this->promptTokens + $this->completionTokens;
    }
}
