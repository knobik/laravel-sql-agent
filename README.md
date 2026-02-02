# Laravel SQL Agent

Self-learning text-to-SQL agent for Laravel.

## Installation

```bash
composer require knobik/laravel-sql-agent
```

Run the install command:

```bash
php artisan sql-agent:install
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=sql-agent-config
```

Set your LLM API key in `.env`:

```env
OPENAI_API_KEY=your-api-key
# or
ANTHROPIC_API_KEY=your-api-key
```

## Usage

### Adding Knowledge

Create JSON files in `resources/sql-agent/knowledge/tables/` to describe your database schema:

```json
{
    "table": "users",
    "description": "Contains user account information",
    "columns": {
        "id": "Primary key",
        "name": "User's full name",
        "email": "User's email address",
        "created_at": "Account creation timestamp"
    }
}
```

Load knowledge into the database:

```bash
php artisan sql-agent:load-knowledge
```

### Querying

```php
use Knobik\SqlAgent\Facades\SqlAgent;

$response = SqlAgent::run('How many users signed up last month?');

echo $response->answer;
echo $response->sql;
```

## License

MIT
