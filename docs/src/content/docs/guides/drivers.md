---
title: LLM & Search Drivers
description: Configure LLM providers via Prism PHP and search drivers for knowledge retrieval.
sidebar:
  order: 3
---

SqlAgent uses [Prism PHP](https://prismphp.com) for LLM integration and a driver-based architecture for knowledge search. You can switch providers and drivers via environment variables without changing any code.

## LLM Providers (via Prism PHP)

SqlAgent delegates all LLM communication to Prism PHP, which provides a unified interface for many providers. Set the active provider and model using environment variables:

```ini
SQL_AGENT_LLM_PROVIDER=openai
SQL_AGENT_LLM_MODEL=gpt-4o
```

Provider credentials (API keys, base URLs) are configured in Prism's own config file at `config/prism.php`. Publish it with:

```bash
php artisan vendor:publish --tag=prism-config
```

### Available Providers

Prism supports a wide range of providers out of the box. Here are some common options:

| Provider | `SQL_AGENT_LLM_PROVIDER` | Example Model |
|----------|--------------------------|---------------|
| OpenAI | `openai` | `gpt-4o`, `gpt-4o-mini` |
| Anthropic | `anthropic` | `claude-sonnet-4-20250514` |
| Ollama | `ollama` | `llama3.1`, `qwen2.5` |
| Google Gemini | `gemini` | `gemini-2.0-flash` |
| Mistral | `mistral` | `mistral-large-latest` |
| xAI | `xai` | `grok-2` |

See the [Prism documentation](https://prismphp.com) for the full list of supported providers and their configuration.

### Provider-Specific Options

Use the `provider_options` config array to pass provider-specific options. For example, to enable thinking/reasoning mode on Ollama models:

```php
// config/sql-agent.php
'llm' => [
    'provider' => 'ollama',
    'model' => 'qwen2.5',
    'provider_options' => ['thinking' => true],
],
```

These options are passed directly to Prism's `withProviderOptions()` method.

### Adding a Custom Provider

Since SqlAgent uses Prism for all LLM communication, adding a new provider means adding it to Prism. See the [Prism documentation](https://prismphp.com) for instructions on registering custom providers.

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
