# Usage

- [Artisan Commands](#artisan-commands)
    - [sql-agent:install](#install)
    - [sql-agent:load-knowledge](#load-knowledge)
    - [sql-agent:eval](#eval)
    - [sql-agent:export-learnings](#export-learnings)
    - [sql-agent:import-learnings](#import-learnings)
    - [sql-agent:prune-learnings](#prune-learnings)
    - [sql-agent:purge](#purge)
- [Programmatic Usage](#programmatic-usage)
    - [Basic Usage](#basic-usage)
    - [Streaming Responses](#streaming-responses)
    - [Custom Connection](#custom-connection)
    - [Conversation History](#conversation-history)
    - [Dependency Injection](#dependency-injection)

## Artisan Commands

### `sql-agent:install`

Run the initial setup: publishes the configuration file, runs migrations, and creates the knowledge directory structure.

```bash
php artisan sql-agent:install
php artisan sql-agent:install --force  # Overwrite existing files
```

### `sql-agent:load-knowledge`

Import knowledge files from disk into the database. Required when using the default `database` knowledge source.

```bash
php artisan sql-agent:load-knowledge
```

| Option | Description |
|--------|-------------|
| `--recreate` | Drop and recreate all knowledge before loading |
| `--tables` | Load only table metadata |
| `--rules` | Load only business rules |
| `--queries` | Load only query patterns |
| `--path=<path>` | Load from a custom directory instead of the configured path |

### `sql-agent:eval`

Run evaluation tests to measure the agent's accuracy against known test cases.

```bash
php artisan sql-agent:eval
```

| Option | Description |
|--------|-------------|
| `--category=<cat>` | Filter by category (`basic`, `aggregation`, `complex`, etc.) |
| `--llm-grader` | Use an LLM to semantically grade responses |
| `--golden-sql` | Compare results against golden SQL output |
| `--connection=<conn>` | Use a specific database connection |
| `--detailed` | Show detailed output for failed tests |
| `--json` | Output results as JSON |
| `--html=<path>` | Generate an HTML report at the given path |
| `--seed` | Seed test cases before running |

### `sql-agent:export-learnings`

Export learnings to a JSON file for backup or sharing across environments.

```bash
php artisan sql-agent:export-learnings
php artisan sql-agent:export-learnings output.json
php artisan sql-agent:export-learnings --category=type_error
```

Available categories: `type_error`, `schema_fix`, `query_pattern`, `data_quality`, `business_logic`.

### `sql-agent:import-learnings`

Import learnings from a previously exported JSON file.

```bash
php artisan sql-agent:import-learnings learnings.json
php artisan sql-agent:import-learnings learnings.json --force  # Include duplicates
```

### `sql-agent:prune-learnings`

Remove old or duplicate learnings to keep the knowledge base clean.

```bash
php artisan sql-agent:prune-learnings
```

| Option | Description |
|--------|-------------|
| `--days=90` | Remove learnings older than N days (default: config value) |
| `--duplicates` | Only remove duplicate learnings |
| `--include-used` | Also remove learnings that have been referenced |
| `--dry-run` | Preview what would be removed without deleting |

> [!TIP]
> This command is not scheduled automatically. Add it to your scheduler for hands-off maintenance. See [Configuration — Learning](/docs/configuration.md#learning).

### `sql-agent:purge`

Purge SqlAgent data from the database by truncating the selected tables.

```bash
php artisan sql-agent:purge
```

| Option | Description |
|--------|-------------|
| `--conversations` | Only purge conversations and messages |
| `--learnings` | Only purge learnings |
| `--knowledge` | Only purge knowledge (table metadata, business rules, query patterns) |
| `--all` | Purge everything (default when no options specified) |
| `--force` | Skip the confirmation prompt |

When `--all` is used (or no options are specified), evaluation test cases are also purged.

## Programmatic Usage

### Basic Usage

Use the `SqlAgent` facade to ask questions and receive structured responses:

```php
use Knobik\SqlAgent\Facades\SqlAgent;

$response = SqlAgent::run('How many users registered last week?');

$response->answer;      // "There are 42 users who registered last week."
$response->sql;         // "SELECT COUNT(*) as count FROM users WHERE ..."
$response->results;     // [['count' => 42]]
$response->toolCalls;   // All tool calls made during execution
$response->iterations;  // Detailed iteration data
$response->error;       // Error message if failed, null otherwise

$response->isSuccess();  // true if no error occurred
$response->hasResults(); // true if results is not empty
```

### Streaming Responses

For real-time output, use the `stream` method which returns a generator of chunks:

```php
use Knobik\SqlAgent\Facades\SqlAgent;

foreach (SqlAgent::stream('Show me the top 5 customers') as $chunk) {
    echo $chunk->content;

    if ($chunk->isComplete()) {
        // Stream finished
    }
}
```

The `stream` method accepts the same parameters as `run`, plus conversation history:

```php
SqlAgent::stream(
    string $question,
    ?string $connection = null,
    array $history = [],
): Generator
```

### Custom Connection

Query a specific database connection by passing it as the second argument:

```php
$response = SqlAgent::run('How many orders today?', 'analytics');
```

### Conversation History

For multi-turn conversations, pass previous messages as history:

```php
$history = [
    ['role' => 'user', 'content' => 'Show me all products'],
    ['role' => 'assistant', 'content' => 'Here are the products...'],
];

foreach (SqlAgent::stream('Now filter by price > 100', null, $history) as $chunk) {
    echo $chunk->content;
}
```

### Dependency Injection

You may also resolve the agent via dependency injection using the `Agent` contract:

```php
use Knobik\SqlAgent\Contracts\Agent;

class ReportController extends Controller
{
    public function __construct(
        private Agent $agent,
    ) {}

    public function generate(Request $request)
    {
        $response = $this->agent->run($request->input('question'));

        return [
            'answer' => $response->answer,
            'sql' => $response->sql,
            'data' => $response->results,
        ];
    }
}
```
