<?php

use Illuminate\Support\Facades\Http;
use Knobik\SqlAgent\Contracts\LlmResponse;
use Knobik\SqlAgent\Llm\Drivers\OllamaDriver;

beforeEach(function () {
    $this->driver = new OllamaDriver([
        'base_url' => 'http://localhost:11434',
        'model' => 'llama3.1',
        'temperature' => 0.0,
    ]);
});

describe('chat', function () {
    it('sends a chat request and parses response', function () {
        Http::fake([
            'localhost:11434/api/chat' => Http::response([
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Hello! How can I help you?',
                ],
                'done' => true,
                'prompt_eval_count' => 10,
                'eval_count' => 20,
            ]),
        ]);

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Hello'],
        ];

        $response = $this->driver->chat($messages);

        expect($response)->toBeInstanceOf(LlmResponse::class);
        expect($response->content)->toBe('Hello! How can I help you?');
        expect($response->finishReason)->toBe('stop');
        expect($response->promptTokens)->toBe(10);
        expect($response->completionTokens)->toBe(20);
    });

    it('handles tool calls in response', function () {
        Http::fake([
            'localhost:11434/api/chat' => Http::response([
                'message' => [
                    'role' => 'assistant',
                    'content' => '',
                    'tool_calls' => [
                        [
                            'function' => [
                                'name' => 'run_sql',
                                'arguments' => ['sql' => 'SELECT 1'],
                            ],
                        ],
                    ],
                ],
                'done' => true,
            ]),
        ]);

        $response = $this->driver->chat([['role' => 'user', 'content' => 'test']]);

        expect($response->toolCalls)->toHaveCount(1);
        expect($response->toolCalls[0]->name)->toBe('run_sql');
        expect($response->toolCalls[0]->arguments)->toBe(['sql' => 'SELECT 1']);
    });

    it('throws on api error', function () {
        Http::fake([
            'localhost:11434/api/chat' => Http::response('Server error', 500),
        ]);

        expect(fn () => $this->driver->chat([['role' => 'user', 'content' => 'test']]))
            ->toThrow(RuntimeException::class, 'Ollama API error: 500');
    });
});

describe('supportsToolCalling', function () {
    it('returns true when models_with_tool_support is null (wildcard)', function () {
        $driver = new OllamaDriver(['models_with_tool_support' => null]);

        expect($driver->supportsToolCalling())->toBeTrue();
    });

    it('returns false when models_with_tool_support is empty', function () {
        $driver = new OllamaDriver(['models_with_tool_support' => []]);

        expect($driver->supportsToolCalling())->toBeFalse();
    });

    it('returns true when model matches list', function () {
        $driver = new OllamaDriver([
            'model' => 'llama3.1:latest',
            'models_with_tool_support' => ['llama3.1'],
        ]);

        expect($driver->supportsToolCalling())->toBeTrue();
    });

    it('returns false when model does not match list', function () {
        $driver = new OllamaDriver([
            'model' => 'gemma2:latest',
            'models_with_tool_support' => ['llama3.1'],
        ]);

        expect($driver->supportsToolCalling())->toBeFalse();
    });
});

describe('formatMessages', function () {
    it('includes tools in payload when supported', function () {
        Http::fake([
            'localhost:11434/api/chat' => Http::response([
                'message' => ['role' => 'assistant', 'content' => 'ok'],
                'done' => true,
            ]),
        ]);

        $tool = Mockery::mock(\Knobik\SqlAgent\Contracts\Tool::class);
        $tool->shouldReceive('name')->andReturn('run_sql');
        $tool->shouldReceive('description')->andReturn('Run SQL');
        $tool->shouldReceive('parameters')->andReturn([
            'type' => 'object',
            'properties' => ['sql' => ['type' => 'string']],
            'required' => ['sql'],
        ]);

        $this->driver->chat([['role' => 'user', 'content' => 'test']], [$tool]);

        Http::assertSent(function ($request) {
            return isset($request->data()['tools']) && count($request->data()['tools']) === 1;
        });
    });
});
