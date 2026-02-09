---
title: Programmatic API
description: Use SqlAgent programmatically via the facade or dependency injection.
sidebar:
  order: 2
---

## Basic Usage

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

## Streaming Responses

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

## Custom Connection

Query a specific database connection by passing it as the second argument:

```php
$response = SqlAgent::run('How many orders today?', 'analytics');
```

## Conversation History

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

## Dependency Injection

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
