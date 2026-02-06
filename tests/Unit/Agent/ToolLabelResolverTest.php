<?php

use Knobik\SqlAgent\Agent\ToolLabelResolver;
use Knobik\SqlAgent\Llm\StreamChunk;
use Knobik\SqlAgent\Llm\ToolCall;

beforeEach(function () {
    $this->resolver = new ToolLabelResolver;
});

describe('getLabel', function () {
    it('returns label for run_sql', function () {
        expect($this->resolver->getLabel('run_sql'))->toBe('Running SQL query');
    });

    it('returns label for introspect_schema', function () {
        expect($this->resolver->getLabel('introspect_schema'))->toBe('Inspecting schema');
    });

    it('returns label for search_knowledge', function () {
        expect($this->resolver->getLabel('search_knowledge'))->toBe('Searching knowledge base');
    });

    it('returns label for save_learning', function () {
        expect($this->resolver->getLabel('save_learning'))->toBe('Saving learning');
    });

    it('returns label for save_validated_query', function () {
        expect($this->resolver->getLabel('save_validated_query'))->toBe('Saving query pattern');
    });

    it('returns tool name for unknown tools', function () {
        expect($this->resolver->getLabel('custom_tool'))->toBe('custom_tool');
    });
});

describe('getType', function () {
    it('returns sql type for run_sql', function () {
        expect($this->resolver->getType('run_sql'))->toBe('sql');
    });

    it('returns schema type for introspect_schema', function () {
        expect($this->resolver->getType('introspect_schema'))->toBe('schema');
    });

    it('returns search type for search_knowledge', function () {
        expect($this->resolver->getType('search_knowledge'))->toBe('search');
    });

    it('returns save type for save_learning', function () {
        expect($this->resolver->getType('save_learning'))->toBe('save');
    });

    it('returns default type for unknown tools', function () {
        expect($this->resolver->getType('unknown'))->toBe('default');
    });
});

describe('buildStreamChunk', function () {
    it('builds chunk for schema tool', function () {
        $toolCall = new ToolCall(id: 'tc_1', name: 'introspect_schema', arguments: ['tables' => ['users']]);
        $chunk = $this->resolver->buildStreamChunk($toolCall);

        expect($chunk)->toBeInstanceOf(StreamChunk::class);
        expect($chunk->content)->toContain('data-type="schema"');
        expect($chunk->content)->toContain('Inspecting schema');
    });

    it('builds chunk for sql tool with data-sql attribute', function () {
        $toolCall = new ToolCall(id: 'tc_2', name: 'run_sql', arguments: ['sql' => 'SELECT * FROM users']);
        $chunk = $this->resolver->buildStreamChunk($toolCall);

        expect($chunk->content)->toContain('data-type="sql"');
        expect($chunk->content)->toContain('data-sql="SELECT * FROM users"');
        expect($chunk->content)->toContain('Running SQL query');
    });

    it('escapes html in sql data attribute', function () {
        $toolCall = new ToolCall(id: 'tc_3', name: 'run_sql', arguments: ['sql' => 'SELECT * FROM users WHERE name = "test"']);
        $chunk = $this->resolver->buildStreamChunk($toolCall);

        expect($chunk->content)->toContain('&quot;test&quot;');
    });

    it('handles sql in query key', function () {
        $toolCall = new ToolCall(id: 'tc_4', name: 'run_sql', arguments: ['query' => 'SELECT 1']);
        $chunk = $this->resolver->buildStreamChunk($toolCall);

        expect($chunk->content)->toContain('data-sql="SELECT 1"');
    });
});
