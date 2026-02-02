<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Llm;

use Generator;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use Knobik\SqlAgent\Contracts\LlmDriver;
use Knobik\SqlAgent\Contracts\LlmResponse;
use Knobik\SqlAgent\Llm\Drivers\AnthropicDriver;
use Knobik\SqlAgent\Llm\Drivers\OllamaDriver;
use Knobik\SqlAgent\Llm\Drivers\OpenAiDriver;

/**
 * @mixin LlmDriver
 */
class LlmManager extends Manager implements LlmDriver
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('sql-agent.llm.default', 'openai');
    }

    public function createOpenaiDriver(): OpenAiDriver
    {
        $config = $this->config->get('sql-agent.llm.drivers.openai', []);

        if (empty($config['api_key'])) {
            throw new InvalidArgumentException(
                'OpenAI API key not configured. Set OPENAI_API_KEY in your .env file.'
            );
        }

        return new OpenAiDriver($config);
    }

    public function createAnthropicDriver(): AnthropicDriver
    {
        $config = $this->config->get('sql-agent.llm.drivers.anthropic', []);

        if (empty($config['api_key'])) {
            throw new InvalidArgumentException(
                'Anthropic API key not configured. Set ANTHROPIC_API_KEY in your .env file.'
            );
        }

        return new AnthropicDriver($config);
    }

    public function createOllamaDriver(): OllamaDriver
    {
        $config = $this->config->get('sql-agent.llm.drivers.ollama', []);

        return new OllamaDriver($config);
    }

    /**
     * Proxy chat call to the default driver.
     */
    public function chat(array $messages, array $tools = []): LlmResponse
    {
        return $this->driver()->chat($messages, $tools);
    }

    /**
     * Proxy stream call to the default driver.
     */
    public function stream(array $messages, array $tools = []): Generator
    {
        return $this->driver()->stream($messages, $tools);
    }

    /**
     * Proxy embed call to the default driver.
     */
    public function embed(string|array $text): array
    {
        return $this->driver()->embed($text);
    }

    /**
     * Proxy supportsToolCalling call to the default driver.
     */
    public function supportsToolCalling(): bool
    {
        return $this->driver()->supportsToolCalling();
    }
}
