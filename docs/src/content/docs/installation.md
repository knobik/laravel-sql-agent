---
title: Installation
description: Install and configure Laravel SQL Agent in your Laravel application.
---

## Install via Composer

```bash
composer require knobik/laravel-sql-agent
```

## Run the Install Command

```bash
php artisan sql-agent:install
```

This will:
1. Publish the configuration file
2. Publish and run migrations
3. Create the knowledge directory structure at `resources/sql-agent/knowledge/`

## Configure Your LLM Provider

Add your LLM API key to `.env`:

```ini
# For OpenAI (default)
OPENAI_API_KEY=sk-your-api-key

# Or for Anthropic
ANTHROPIC_API_KEY=sk-ant-your-api-key
SQL_AGENT_LLM_DRIVER=anthropic

# Or for Ollama (local)
SQL_AGENT_LLM_DRIVER=ollama
OLLAMA_BASE_URL=http://localhost:11434
```

## Quick Start

### 1. Create a knowledge file

Create `resources/sql-agent/knowledge/tables/users.json`:

```json
{
    "table": "users",
    "description": "Contains user account information",
    "columns": {
        "id": "Primary key, auto-incrementing integer",
        "name": "User's full name",
        "email": "User's email address (unique)",
        "created_at": "Account creation timestamp",
        "updated_at": "Last update timestamp"
    }
}
```

### 2. Load knowledge into the database

```bash
php artisan sql-agent:load-knowledge
```

### 3. Run your first query

```php
use Knobik\SqlAgent\Facades\SqlAgent;

$response = SqlAgent::run('How many users signed up this month?');

echo $response->answer;  // "There are 42 users who signed up this month."
echo $response->sql;     // "SELECT COUNT(*) as count FROM users WHERE created_at >= '2026-01-01'"
```
