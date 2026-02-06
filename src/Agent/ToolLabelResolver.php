<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Agent;

use Knobik\SqlAgent\Llm\StreamChunk;
use Knobik\SqlAgent\Llm\ToolCall;

class ToolLabelResolver
{
    protected const LABELS = [
        'run_sql' => 'Running SQL query',
        'introspect_schema' => 'Inspecting schema',
        'search_knowledge' => 'Searching knowledge base',
        'save_learning' => 'Saving learning',
        'save_validated_query' => 'Saving query pattern',
    ];

    protected const TYPES = [
        'run_sql' => 'sql',
        'introspect_schema' => 'schema',
        'search_knowledge' => 'search',
        'save_learning' => 'save',
        'save_validated_query' => 'save',
    ];

    public function getLabel(string $toolName): string
    {
        return self::LABELS[$toolName] ?? $toolName;
    }

    public function getType(string $toolName): string
    {
        return self::TYPES[$toolName] ?? 'default';
    }

    public function buildStreamChunk(ToolCall $toolCall): StreamChunk
    {
        $label = $this->getLabel($toolCall->name);
        $type = $this->getType($toolCall->name);

        $sqlData = '';
        if ($toolCall->name === 'run_sql') {
            $sql = $toolCall->arguments['sql'] ?? $toolCall->arguments['query'] ?? '';
            $sqlData = ' data-sql="'.htmlspecialchars($sql, ENT_QUOTES, 'UTF-8').'"';
        }

        return new StreamChunk(
            content: "\n<tool data-type=\"{$type}\"{$sqlData}>{$label}</tool>\n",
        );
    }
}
