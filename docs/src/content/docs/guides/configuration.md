---
title: Configuration
description: All SqlAgent configuration options — database, LLM, search, safety, and more.
---

All SqlAgent configuration lives in the `config/sql-agent.php` file. Each option is documented below with its purpose, accepted values, and default.

After installation, you can publish the configuration file using:

```bash
php artisan vendor:publish --tag=sql-agent-config
```

## Display Name

The `name` option defines the display name used in the web UI and log messages:

```php
'name' => 'SqlAgent',
```

## Database

SqlAgent uses two database connections: one for querying your application data, and one for storing its own internal tables (knowledge, learnings, conversations, etc.):

```php
'database' => [
    'connection' => env('SQL_AGENT_CONNECTION', config('database.default')),
    'storage_connection' => env('SQL_AGENT_STORAGE_CONNECTION', config('database.default')),
],
```

The `connection` option determines which database the agent will run queries against. The `storage_connection` option determines where SqlAgent's own tables are stored. By default, both use your application's default connection.

:::tip
If your application data lives on a separate database from your main application, set `SQL_AGENT_CONNECTION` accordingly. You may also want to store SqlAgent's tables on a different connection using `SQL_AGENT_STORAGE_CONNECTION`.
:::

## LLM

SqlAgent supports multiple LLM providers. Set the default driver and configure each provider's credentials and model settings:

```php
'llm' => [
    'default' => env('SQL_AGENT_LLM_DRIVER', 'openai'),

    'drivers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('SQL_AGENT_OPENAI_MODEL', 'gpt-4o'),
            'temperature' => 0.0,
            'max_tokens' => 4096,
        ],

        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('SQL_AGENT_ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
            'temperature' => 0.0,
            'max_tokens' => 4096,
        ],

        'ollama' => [
            'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'model' => env('SQL_AGENT_OLLAMA_MODEL', 'llama3.1'),
            'temperature' => 0.0,
            'think' => env('SQL_AGENT_OLLAMA_THINK', true),
            'models_with_tool_support' => null,
        ],
    ],
],
```

### OpenAI

The default driver. Requires an OpenAI API key:

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

Use locally-hosted models via Ollama. No API key is required:

```ini
SQL_AGENT_LLM_DRIVER=ollama
OLLAMA_BASE_URL=http://localhost:11434
SQL_AGENT_OLLAMA_MODEL=llama3.1
```

**Thinking Mode**

The `think` option enables reasoning mode for models that support it:

| Value | Behavior |
|-------|----------|
| `true` | Enable thinking mode (default) |
| `false` | Disable thinking mode |
| `"low"`, `"medium"`, `"high"` | Budget levels for models that support it (e.g., GPT-OSS) |

When thinking mode is active, the LLM's internal reasoning is captured in the streaming SSE events and stored in debug metadata.

**Tool Support**

Not all Ollama models support tool calling. The `models_with_tool_support` option controls which models may use tools:

| Value | Behavior |
|-------|----------|
| `null` | All models can use tools (default) |
| `[]` | No models can use tools |
| `['model1', 'model2']` | Only listed models can use tools |

## Search

Search drivers determine how SqlAgent finds relevant knowledge (table metadata, business rules, query patterns) based on the user's question:

```php
'search' => [
    'default' => env('SQL_AGENT_SEARCH_DRIVER', 'database'),

    'drivers' => [
        'database' => [
            'mysql' => ['mode' => 'NATURAL LANGUAGE MODE'],
            'pgsql' => ['language' => 'english'],
            'sqlsrv' => [],
        ],

        'scout' => [
            'driver' => env('SCOUT_DRIVER', 'meilisearch'),
        ],

        'hybrid' => [
            'primary' => 'scout',
            'fallback' => 'database',
            'merge_results' => false,
        ],
    ],
],
```

Three drivers are available:

- **`database`** — Uses native full-text search (`MATCH ... AGAINST` on MySQL, `tsvector` on PostgreSQL, `LIKE` on SQLite, `CONTAINS` on SQL Server). No external services required.
- **`scout`** — Integrates with [Laravel Scout](https://laravel.com/docs/scout) for external search engines like Meilisearch or Algolia. Requires the `laravel/scout` package.
- **`hybrid`** — Uses Scout as the primary driver with a database fallback. Set `merge_results` to `true` to combine results from both.

## Agent Behavior

Control how the agentic loop operates:

```php
'agent' => [
    'max_iterations' => env('SQL_AGENT_MAX_ITERATIONS', 10),
    'default_limit' => env('SQL_AGENT_DEFAULT_LIMIT', 100),
    'chat_history_length' => env('SQL_AGENT_CHAT_HISTORY', 10),
],
```

| Option | Description | Default |
|--------|-------------|---------|
| `max_iterations` | Maximum number of tool-calling rounds before the agent stops | `10` |
| `default_limit` | `LIMIT` applied to queries that don't specify one | `100` |
| `chat_history_length` | Number of previous messages included for conversational context | `10` |

## Learning

SqlAgent can automatically learn from SQL errors and improve over time:

```php
'learning' => [
    'enabled' => env('SQL_AGENT_LEARNING_ENABLED', true),
    'auto_save_errors' => env('SQL_AGENT_AUTO_SAVE_ERRORS', true),
    'prune_after_days' => env('SQL_AGENT_LEARNING_PRUNE_DAYS', 90),
],
```

| Option | Description | Default |
|--------|-------------|---------|
| `enabled` | Enable the self-learning feature | `true` |
| `auto_save_errors` | Automatically create learnings when SQL errors occur and the agent recovers | `true` |
| `prune_after_days` | Age threshold (in days) for the prune command | `90` |

The `prune_after_days` value is used by the `sql-agent:prune-learnings` Artisan command. This command is **not scheduled automatically** — you need to run it manually or register it in your scheduler:

```php
// routes/console.php
Schedule::command('sql-agent:prune-learnings')->daily();
```

## Knowledge

Configure where SqlAgent reads knowledge from at runtime:

```php
'knowledge' => [
    'path' => env('SQL_AGENT_KNOWLEDGE_PATH', resource_path('sql-agent/knowledge')),
    'source' => env('SQL_AGENT_KNOWLEDGE_SOURCE', 'database'),
],
```

The `path` option sets the directory containing your JSON knowledge files. This path is used both when loading knowledge via `sql-agent:load-knowledge` and when the `files` source reads directly from disk.

The `source` option controls how the agent loads knowledge at runtime:

- **`database`** (default, recommended) — Reads from the `sql_agent_table_metadata`, `sql_agent_business_rules`, and `sql_agent_query_patterns` tables. You must run `php artisan sql-agent:load-knowledge` to import your JSON files first. Supports full-text search over knowledge.
- **`files`** — Reads directly from JSON files on disk. No import step needed, but full-text search is not available.

## Web Interface

SqlAgent ships with a Livewire chat UI. Configure its routes and access:

```php
'ui' => [
    'enabled' => env('SQL_AGENT_UI_ENABLED', true),
    'route_prefix' => env('SQL_AGENT_ROUTE_PREFIX', 'sql-agent'),
    'middleware' => ['web', 'auth'],
],
```

| Option | Description | Default |
|--------|-------------|---------|
| `enabled` | Enable the web interface | `true` |
| `route_prefix` | URL prefix for the UI (e.g., `/sql-agent`) | `sql-agent` |
| `middleware` | Middleware applied to all UI routes | `['web', 'auth']` |

Set `SQL_AGENT_UI_ENABLED=false` to disable the web interface entirely. See the [Web Interface](/laravel-sql-agent/guides/web-interface/) guide for more details on customization.

## User Tracking

By default, user tracking is disabled. Enable it to scope conversations and learnings per user:

```php
'user' => [
    'enabled' => env('SQL_AGENT_USER_ENABLED', false),
    'model' => null,
    'resolver' => null,
],
```

When enabled, SqlAgent uses `auth()->id()` to resolve the current user. You can customize this for non-standard authentication setups:

**Custom auth guard:**

```php
'user' => [
    'enabled' => true,
    'model' => \App\Models\Admin::class,
    'resolver' => fn () => auth('admin')->id(),
],
```

**Multi-tenancy:**

```php
'user' => [
    'enabled' => true,
    'resolver' => fn () => tenant()->owner_id,
],
```

## SQL Safety

SqlAgent includes configurable guardrails to prevent destructive SQL operations:

```php
'sql' => [
    'allowed_statements' => ['SELECT', 'WITH'],
    'forbidden_keywords' => [
        'DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER',
        'CREATE', 'TRUNCATE', 'GRANT', 'REVOKE', 'EXEC', 'EXECUTE',
    ],
    'max_rows' => env('SQL_AGENT_MAX_ROWS', 1000),
],
```

| Option | Description | Default |
|--------|-------------|---------|
| `allowed_statements` | Only these SQL statement types may be executed | `['SELECT', 'WITH']` |
| `forbidden_keywords` | Queries containing these keywords are rejected | See above |
| `max_rows` | Maximum number of rows returned by any query | `1000` |

## Evaluation

Configure the evaluation framework for testing agent accuracy:

```php
'evaluation' => [
    'grader_model' => env('SQL_AGENT_GRADER_MODEL', 'gpt-4o-mini'),
    'pass_threshold' => env('SQL_AGENT_EVAL_PASS_THRESHOLD', 0.6),
    'timeout' => env('SQL_AGENT_EVAL_TIMEOUT', 60),
],
```

| Option | Description | Default |
|--------|-------------|---------|
| `grader_model` | LLM model used for semantic grading of test results | `gpt-4o-mini` |
| `pass_threshold` | Minimum score (0.0–1.0) to pass LLM grading | `0.6` |
| `timeout` | Maximum seconds allowed per test case | `60` |

See the [Evaluation & Self-Learning](/laravel-sql-agent/guides/evaluation/) guide for details on running evaluations.

## Debug

Enable debug mode to store detailed metadata alongside each assistant message:

```php
'debug' => [
    'enabled' => env('SQL_AGENT_DEBUG', false),
],
```

When enabled, each message's `metadata` column will include the full system prompt, tool schemas, iteration details, and timing data. This is useful for development but adds significant storage overhead (~50–60 KB per message). Disable in production.

See the [Web Interface — Debug Mode](/laravel-sql-agent/guides/web-interface/#debug-mode) guide for details on what gets stored and how to inspect it.
