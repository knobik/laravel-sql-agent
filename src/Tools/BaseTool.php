<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Tools;

use Knobik\SqlAgent\Contracts\Tool;
use Knobik\SqlAgent\Contracts\ToolResult;
use Throwable;

abstract class BaseTool implements Tool
{
    /**
     * Get the JSON Schema for this tool's parameters.
     */
    abstract protected function schema(): array;

    /**
     * Handle the tool execution with the given parameters.
     *
     * @return mixed The result data to be wrapped in ToolResult
     */
    abstract protected function handle(array $parameters): mixed;

    /**
     * Get the JSON Schema parameters definition.
     */
    public function parameters(): array
    {
        return $this->schema();
    }

    /**
     * Execute the tool with error handling.
     */
    public function execute(array $parameters): ToolResult
    {
        try {
            $result = $this->handle($parameters);

            return ToolResult::success($result);
        } catch (Throwable $e) {
            return ToolResult::failure($e->getMessage());
        }
    }

    /**
     * Create a standard JSON Schema object type.
     */
    protected function objectSchema(array $properties, array $required = []): array
    {
        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if (! empty($required)) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * Create a string property definition.
     */
    protected function stringProperty(string $description, ?array $enum = null): array
    {
        $property = [
            'type' => 'string',
            'description' => $description,
        ];

        if ($enum !== null) {
            $property['enum'] = $enum;
        }

        return $property;
    }

    /**
     * Create a boolean property definition.
     */
    protected function booleanProperty(string $description, ?bool $default = null): array
    {
        $property = [
            'type' => 'boolean',
            'description' => $description,
        ];

        if ($default !== null) {
            $property['default'] = $default;
        }

        return $property;
    }

    /**
     * Create an integer property definition.
     */
    protected function integerProperty(string $description, ?int $minimum = null, ?int $maximum = null): array
    {
        $property = [
            'type' => 'integer',
            'description' => $description,
        ];

        if ($minimum !== null) {
            $property['minimum'] = $minimum;
        }

        if ($maximum !== null) {
            $property['maximum'] = $maximum;
        }

        return $property;
    }

    /**
     * Create an array property definition.
     */
    protected function arrayProperty(string $description, array $items): array
    {
        return [
            'type' => 'array',
            'description' => $description,
            'items' => $items,
        ];
    }
}
