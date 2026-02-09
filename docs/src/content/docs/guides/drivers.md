---
title: LLM & Search Drivers
description: Configure LLM providers (OpenAI, Anthropic, Ollama) and search drivers for knowledge retrieval.
---

SqlAgent uses a driver-based architecture for both LLM providers and knowledge search. You can switch drivers via environment variables without changing any code.

## LLM Drivers

Set the active LLM driver using the `SQL_AGENT_LLM_DRIVER` environment variable.

### OpenAI

The default driver. Supports GPT-4, GPT-4o, and other OpenAI models:

```ini
OPENAI_API_KEY=sk-your-api-key
SQL_AGENT_LLM_DRIVER=openai
SQL_AGENT_OPENAI_MODEL=gpt-4o
```

### Anthropic

Use Claude models from Anthropic:

```ini
ANTHROPIC_API_KEY=sk-ant-your-api-key
SQL_AGENT_LLM_DRIVER=anthropic
SQL_AGENT_ANTHROPIC_MODEL=claude-sonnet-4-20250514
```

### Ollama

Run models locally with [Ollama](https://ollama.com). No API key required:

```ini
SQL_AGENT_LLM_DRIVER=ollama
OLLAMA_BASE_URL=http://localhost:11434
SQL_AGENT_OLLAMA_MODEL=llama3.1
```

**Thinking Mode**

Some Ollama models support a reasoning/thinking mode that produces higher-quality results. Enable it via environment or config:

```ini
SQL_AGENT_OLLAMA_THINK=true
```

Accepted values are `true`, `false`, or a budget level string (`"low"`, `"medium"`, `"high"`) for models like GPT-OSS that support granular control.

When thinking mode is active, the LLM's internal reasoning is captured in streaming SSE events (`thinking` event type) and stored in debug metadata.

**Tool Support**

Not all Ollama models support tool/function calling. Use the `models_with_tool_support` config option to control which models are allowed to use tools:

```php
'ollama' => [
    // ...
    'models_with_tool_support' => null,        // null = all models (default)
    // 'models_with_tool_support' => [],        // empty = no models
    // 'models_with_tool_support' => ['llama3.1', 'qwen2.5'],  // specific models only
],
```

### Custom Drivers

To integrate a provider that isn't built in, implement the `LlmDriver` contract:

```php
<?php

namespace App\Llm;

use Generator;
use Knobik\SqlAgent\Contracts\LlmDriver;
use Knobik\SqlAgent\Contracts\LlmResponse;

class CustomLlmDriver implements LlmDriver
{
    public function chat(array $messages, array $tools = []): LlmResponse
    {
        // Your implementation
    }

    public function stream(array $messages, array $tools = []): Generator
    {
        // Your implementation
    }

    public function supportsToolCalling(): bool
    {
        return true;
    }
}
```

Then register the driver in a service provider:

```php
$this->app->bind('sql-agent.llm.custom', CustomLlmDriver::class);
```

## Search Drivers

Search drivers control how SqlAgent finds relevant knowledge (table metadata, business rules, query patterns) based on the user's question. Set the active driver using `SQL_AGENT_SEARCH_DRIVER`.

### Database

Uses your database's native full-text search capabilities. No external services required:

```ini
SQL_AGENT_SEARCH_DRIVER=database
```

The behavior varies by database engine:

| Database | Implementation | Notes |
|----------|---------------|-------|
| MySQL | `MATCH ... AGAINST` | Supports natural language and boolean mode |
| PostgreSQL | `to_tsvector` / `to_tsquery` | Configurable text search language |
| SQLite | `LIKE` queries | Less accurate, but functional for development |
| SQL Server | `CONTAINS` predicates | Requires a full-text catalog to be configured |

### Scout

Integrates with [Laravel Scout](https://laravel.com/docs/scout) for external search engines like Meilisearch or Algolia:

```ini
SQL_AGENT_SEARCH_DRIVER=scout
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=your-key
```

Requires the `laravel/scout` package:

```bash
composer require laravel/scout
```

### Hybrid

Combines Scout as the primary search engine with the database driver as a fallback. Useful when you want the quality of an external search engine with the reliability of a local fallback:

```ini
SQL_AGENT_SEARCH_DRIVER=hybrid
```

Configure the hybrid driver in `config/sql-agent.php`:

```php
'hybrid' => [
    'primary' => 'scout',
    'fallback' => 'database',
    'merge_results' => false, // Set true to combine results from both drivers
],
```
