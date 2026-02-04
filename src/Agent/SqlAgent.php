<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Agent;

use Generator;
use Knobik\SqlAgent\Contracts\Agent;
use Knobik\SqlAgent\Contracts\AgentResponse;
use Knobik\SqlAgent\Contracts\LlmDriver;
use Knobik\SqlAgent\Contracts\Tool;
use Knobik\SqlAgent\Llm\StreamChunk;
use Knobik\SqlAgent\Llm\ToolCall;
use Knobik\SqlAgent\Services\ContextBuilder;
use Knobik\SqlAgent\Tools\IntrospectSchemaTool;
use Knobik\SqlAgent\Tools\RunSqlTool;
use Throwable;

class SqlAgent implements Agent
{
    protected ?string $lastSql = null;

    protected ?array $lastResults = null;

    protected array $iterations = [];

    protected ?string $currentQuestion = null;

    public function __construct(
        protected LlmDriver $llm,
        protected ToolRegistry $toolRegistry,
        protected ContextBuilder $contextBuilder,
        protected PromptRenderer $promptRenderer,
        protected MessageBuilder $messageBuilder,
    ) {}

    public function run(string $question, ?string $connection = null): AgentResponse
    {
        $this->reset();
        $this->currentQuestion = $question;

        try {
            // Build context
            $context = $this->contextBuilder->build($question, $connection);

            // Render system prompt
            $systemPrompt = $this->promptRenderer->renderSystem($context->toPromptString());

            // Build initial messages
            $messages = $this->messageBuilder->build($systemPrompt, $question);

            // Configure tools for the connection
            $tools = $this->prepareTools($connection, $question);

            // Run the agent loop
            $maxIterations = config('sql-agent.agent.max_iterations', 10);

            for ($i = 0; $i < $maxIterations; $i++) {
                $response = $this->llm->chat($messages, $tools);

                $this->iterations[] = [
                    'iteration' => $i + 1,
                    'response' => $response->content,
                    'tool_calls' => array_map(
                        fn (ToolCall $tc) => ['name' => $tc->name, 'arguments' => $tc->arguments],
                        $response->toolCalls
                    ),
                    'finish_reason' => $response->finishReason,
                ];

                // If no tool calls, we're done
                if (! $response->hasToolCalls()) {
                    return new AgentResponse(
                        answer: $response->content,
                        sql: $this->lastSql,
                        results: $this->lastResults,
                        toolCalls: $this->collectToolCalls(),
                        iterations: $this->iterations,
                    );
                }

                // Execute tool calls
                $messages = $this->messageBuilder->append(
                    $messages,
                    $this->messageBuilder->assistantWithToolCalls($response->content, $response->toolCalls)
                );

                foreach ($response->toolCalls as $toolCall) {
                    $result = $this->executeTool($toolCall);
                    $messages = $this->messageBuilder->append(
                        $messages,
                        $this->messageBuilder->toolResult($toolCall, $result)
                    );
                }
            }

            // Max iterations reached
            return new AgentResponse(
                answer: 'I was unable to complete the task within the maximum number of iterations.',
                sql: $this->lastSql,
                results: $this->lastResults,
                toolCalls: $this->collectToolCalls(),
                iterations: $this->iterations,
                error: 'Maximum iterations reached',
            );
        } catch (Throwable $e) {
            return new AgentResponse(
                answer: "An error occurred: {$e->getMessage()}",
                sql: $this->lastSql,
                results: $this->lastResults,
                toolCalls: $this->collectToolCalls(),
                iterations: $this->iterations,
                error: $e->getMessage(),
            );
        }
    }

    public function stream(string $question, ?string $connection = null): Generator
    {
        $this->reset();
        $this->currentQuestion = $question;

        // Build context
        $context = $this->contextBuilder->build($question, $connection);

        // Render system prompt
        $systemPrompt = $this->promptRenderer->renderSystem($context->toPromptString());

        // Build initial messages
        $messages = $this->messageBuilder->build($systemPrompt, $question);

        // Configure tools for the connection
        $tools = $this->prepareTools($connection, $question);

        // Run the agent loop with streaming
        $maxIterations = config('sql-agent.agent.max_iterations', 10);

        for ($i = 0; $i < $maxIterations; $i++) {
            $content = '';
            $toolCalls = [];

            foreach ($this->llm->stream($messages, $tools) as $chunk) {
                /** @var StreamChunk $chunk */
                if ($chunk->hasContent()) {
                    $content .= $chunk->content;
                    yield $chunk;
                }

                if ($chunk->isComplete()) {
                    $toolCalls = $chunk->toolCalls;

                    $this->iterations[] = [
                        'iteration' => $i + 1,
                        'response' => $content,
                        'tool_calls' => array_map(
                            fn (ToolCall $tc) => ['name' => $tc->name, 'arguments' => $tc->arguments],
                            $toolCalls
                        ),
                        'finish_reason' => $chunk->finishReason,
                    ];
                }
            }

            // If no tool calls, we're done
            if (empty($toolCalls)) {
                yield StreamChunk::complete('stop');

                return;
            }

            // Execute tool calls
            $messages = $this->messageBuilder->append(
                $messages,
                $this->messageBuilder->assistantWithToolCalls($content, $toolCalls)
            );

            foreach ($toolCalls as $toolCall) {
                // Yield a chunk indicating tool execution
                yield new StreamChunk(
                    content: "\n[Executing {$toolCall->name}...]\n",
                );

                $result = $this->executeTool($toolCall);
                $messages = $this->messageBuilder->append(
                    $messages,
                    $this->messageBuilder->toolResult($toolCall, $result)
                );
            }
        }

        // Max iterations reached
        yield StreamChunk::content("\n\nMaximum iterations reached.");
        yield StreamChunk::complete('max_iterations');
    }

    /**
     * Get the last SQL query executed.
     */
    public function getLastSql(): ?string
    {
        return $this->lastSql;
    }

    /**
     * Get the last query results.
     */
    public function getLastResults(): ?array
    {
        return $this->lastResults;
    }

    /**
     * Get all iterations from the last run.
     */
    public function getIterations(): array
    {
        return $this->iterations;
    }

    protected function reset(): void
    {
        $this->lastSql = null;
        $this->lastResults = null;
        $this->iterations = [];
        $this->currentQuestion = null;
    }

    /**
     * @return Tool[]
     */
    protected function prepareTools(?string $connection, ?string $question = null): array
    {
        $tools = $this->toolRegistry->all();

        // Configure connection and question for tools that need it
        foreach ($tools as $tool) {
            if ($tool instanceof RunSqlTool) {
                $tool->setConnection($connection);
                $tool->setQuestion($question);
            } elseif ($tool instanceof IntrospectSchemaTool) {
                $tool->setConnection($connection);
            }
        }

        return $tools;
    }

    protected function executeTool(ToolCall $toolCall): \Knobik\SqlAgent\Contracts\ToolResult
    {
        if (! $this->toolRegistry->has($toolCall->name)) {
            return \Knobik\SqlAgent\Contracts\ToolResult::failure(
                "Unknown tool: {$toolCall->name}"
            );
        }

        $tool = $this->toolRegistry->get($toolCall->name);
        $result = $tool->execute($toolCall->arguments);

        // Track SQL queries
        if ($toolCall->name === 'run_sql' && $result->success) {
            $this->lastSql = $toolCall->arguments['sql'] ?? null;
            $this->lastResults = $result->data['rows'] ?? null;
        }

        return $result;
    }

    protected function collectToolCalls(): array
    {
        $toolCalls = [];

        foreach ($this->iterations as $iteration) {
            foreach ($iteration['tool_calls'] ?? [] as $tc) {
                $toolCalls[] = $tc;
            }
        }

        return $toolCalls;
    }
}
