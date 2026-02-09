---
title: Events
description: Event hooks dispatched by SqlAgent for custom logging, notifications, and side effects.
sidebar:
  order: 3
---

SqlAgent dispatches events at key points during execution. You can listen to these events to add custom logging, notifications, or other side effects.

## Available Events

### `SqlErrorOccurred`

Dispatched when a SQL query executed by the agent fails. The event contains the failed SQL, the error message, the original question, and the database connection:

```php
use Knobik\SqlAgent\Events\SqlErrorOccurred;

class SqlErrorListener
{
    public function handle(SqlErrorOccurred $event): void
    {
        Log::warning('SQL Agent error', [
            'sql' => $event->sql,
            'error' => $event->error,
            'question' => $event->question,
            'connection' => $event->connection,
        ]);
    }
}
```

:::note
SqlAgent automatically registers an `AutoLearnFromError` listener for this event when `learning.auto_save_errors` is enabled. You do not need to register it yourself.
:::

### `LearningCreated`

Dispatched when a new learning record is created, either automatically from an error recovery or manually via the `SaveLearningTool`:

```php
use Knobik\SqlAgent\Events\LearningCreated;

class LearningListener
{
    public function handle(LearningCreated $event): void
    {
        Notification::send($admins, new NewLearningNotification($event->learning));
    }
}
```

## Registering Listeners

Register your listeners in your application's `EventServiceProvider` or use Laravel's event discovery:

```php
protected $listen = [
    \Knobik\SqlAgent\Events\SqlErrorOccurred::class => [
        \App\Listeners\SqlErrorListener::class,
    ],
    \Knobik\SqlAgent\Events\LearningCreated::class => [
        \App\Listeners\LearningListener::class,
    ],
];
```
