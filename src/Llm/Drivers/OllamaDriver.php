<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Llm\Drivers;

use Generator;
use Illuminate\Support\Facades\Http;
use Knobik\SqlAgent\Contracts\LlmDriver;
use Knobik\SqlAgent\Contracts\LlmResponse;
use Knobik\SqlAgent\Llm\StreamChunk;
use Knobik\SqlAgent\Llm\ToolCall;
use Knobik\SqlAgent\Llm\ToolFormatter;
use RuntimeException;

class OllamaDriver implements LlmDriver
{
    protected string $baseUrl;

    protected string $model;

    protected float $temperature;

    protected array $modelsWithToolSupport = [
        'llama3.1',
        'llama3.2',
        'llama3.3',
        'mistral',
        'mixtral',
        'qwen2.5',
        'command-r',
        'granite3-dense',
    ];

    public function __construct(array $config = [])
    {
        $this->baseUrl = rtrim($config['base_url'] ?? 'http://localhost:11434', '/');
        $this->model = $config['model'] ?? 'llama3.1';
        $this->temperature = $config['temperature'] ?? 0.0;
    }

    public function chat(array $messages, array $tools = []): LlmResponse
    {
        $payload = [
            'model' => $this->model,
            'messages' => $this->formatMessages($messages),
            'stream' => false,
            'options' => [
                'temperature' => $this->temperature,
            ],
        ];

        if (! empty($tools) && $this->supportsToolCalling()) {
            $payload['tools'] = ToolFormatter::toOllama($tools);
        }

        $response = Http::timeout(300)
            ->post("{$this->baseUrl}/api/chat", $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Ollama API error: {$response->status()} - {$response->body()}"
            );
        }

        return $this->parseResponse($response->json());
    }

    public function stream(array $messages, array $tools = []): Generator
    {
        $payload = [
            'model' => $this->model,
            'messages' => $this->formatMessages($messages),
            'stream' => true,
            'options' => [
                'temperature' => $this->temperature,
            ],
        ];

        if (! empty($tools) && $this->supportsToolCalling()) {
            $payload['tools'] = ToolFormatter::toOllama($tools);
        }

        $response = Http::timeout(300)
            ->withOptions(['stream' => true])
            ->post("{$this->baseUrl}/api/chat", $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Ollama API error: {$response->status()} - {$response->body()}"
            );
        }

        yield from $this->parseStream($response->getBody());
    }

    public function supportsToolCalling(): bool
    {
        $modelBase = strtolower(explode(':', $this->model)[0]);

        foreach ($this->modelsWithToolSupport as $supported) {
            if (str_starts_with($modelBase, strtolower($supported))) {
                return true;
            }
        }

        return false;
    }

    protected function formatMessages(array $messages): array
    {
        $formatted = [];

        foreach ($messages as $message) {
            // Handle tool results
            if ($message['role'] === 'tool') {
                $formatted[] = [
                    'role' => 'tool',
                    'content' => is_string($message['content'])
                        ? $message['content']
                        : json_encode($message['content']),
                ];

                continue;
            }

            // Handle assistant messages with tool calls
            if ($message['role'] === 'assistant' && isset($message['tool_calls'])) {
                $toolCalls = [];
                foreach ($message['tool_calls'] as $toolCall) {
                    if ($toolCall instanceof ToolCall) {
                        $toolCalls[] = $toolCall->toOllamaArray();
                    } else {
                        // If it's already an array, ensure arguments is not a JSON string
                        if (isset($toolCall['function']['arguments']) && is_string($toolCall['function']['arguments'])) {
                            $toolCall['function']['arguments'] = json_decode($toolCall['function']['arguments'], true) ?? [];
                        }
                        $toolCalls[] = $toolCall;
                    }
                }

                $formatted[] = [
                    'role' => 'assistant',
                    'content' => $message['content'] ?? '',
                    'tool_calls' => $toolCalls,
                ];

                continue;
            }

            // Regular messages
            $formatted[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        return $formatted;
    }

    protected function parseResponse(array $data): LlmResponse
    {
        $message = $data['message'] ?? [];
        $content = $message['content'] ?? '';
        $toolCalls = [];

        if (! empty($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $toolCall) {
                $arguments = $toolCall['function']['arguments'] ?? [];

                // Parse arguments if they're a JSON string
                if (is_string($arguments)) {
                    $parsed = json_decode($arguments, true);
                    $arguments = is_array($parsed) ? $parsed : [];
                }

                $toolCalls[] = new ToolCall(
                    id: $toolCall['id'] ?? uniqid('tc_'),
                    name: $toolCall['function']['name'] ?? '',
                    arguments: $arguments,
                );
            }
        }

        return new LlmResponse(
            content: $content,
            toolCalls: $toolCalls,
            finishReason: $data['done'] ? 'stop' : null,
            promptTokens: $data['prompt_eval_count'] ?? null,
            completionTokens: $data['eval_count'] ?? null,
        );
    }

    protected function parseStream($body): Generator
    {
        $toolCalls = [];

        foreach ($this->readLines($body) as $line) {
            $event = json_decode($line, true);
            if ($event === null) {
                continue;
            }

            $message = $event['message'] ?? [];

            // Handle content
            if (! empty($message['content'])) {
                yield StreamChunk::content($message['content']);
            }

            // Handle tool calls
            if (! empty($message['tool_calls'])) {
                foreach ($message['tool_calls'] as $toolCall) {
                    $arguments = $toolCall['function']['arguments'] ?? [];

                    // Parse arguments if they're a JSON string
                    if (is_string($arguments)) {
                        $parsed = json_decode($arguments, true);
                        $arguments = is_array($parsed) ? $parsed : [];
                    }

                    $toolCalls[] = new ToolCall(
                        id: $toolCall['id'] ?? uniqid('tc_'),
                        name: $toolCall['function']['name'] ?? '',
                        arguments: $arguments,
                    );
                }
            }

            // Handle completion
            if ($event['done'] ?? false) {
                $finishReason = ! empty($toolCalls) ? 'tool_calls' : 'stop';
                yield StreamChunk::complete($finishReason, null, $toolCalls);
            }
        }
    }

    protected function readLines($stream): Generator
    {
        $buffer = '';

        while (! $stream->eof()) {
            $buffer .= $stream->read(1024);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);

                $line = trim($line);
                if ($line !== '') {
                    yield $line;
                }
            }
        }

        if (trim($buffer) !== '') {
            yield trim($buffer);
        }
    }
}
