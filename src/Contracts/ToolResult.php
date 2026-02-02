<?php

namespace Knobik\SqlAgent\Contracts;

class ToolResult
{
    public function __construct(
        public readonly mixed $data,
        public readonly bool $success = true,
        public readonly ?string $error = null,
    ) {}

    public static function success(mixed $data): self
    {
        return new self(data: $data, success: true);
    }

    public static function failure(string $error): self
    {
        return new self(data: null, success: false, error: $error);
    }
}
