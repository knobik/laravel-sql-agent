# Evaluation & Self-Learning

- [Introduction](#introduction)
- [Evaluation System](#evaluation-system)
    - [Creating Test Cases](#creating-test-cases)
    - [Running Evaluations](#running-evaluations)
    - [Evaluation Modes](#evaluation-modes)
- [Self-Learning](#self-learning)
    - [How It Works](#how-it-works)
    - [Learning Categories](#learning-categories)
    - [Managing Learnings](#managing-learnings)
    - [Disabling Self-Learning](#disabling-self-learning)

## Introduction

SqlAgent includes an evaluation framework to measure accuracy against known test cases, and a self-learning system that automatically improves over time by recording error recoveries.

## Evaluation System

The evaluation system helps you measure and improve your agent's accuracy by running it against a suite of test cases with known expected outcomes.

### Creating Test Cases

Test cases are stored in the `sql_agent_test_cases` table. You can seed them using the built-in seeder (`--seed` flag) or create your own:

```php
use Knobik\SqlAgent\Models\TestCase;

TestCase::create([
    'name' => 'Count active users',
    'category' => 'basic',
    'question' => 'How many active users are there?',
    'expected_values' => ['count' => 42],
    'golden_sql' => 'SELECT COUNT(*) as count FROM users WHERE status = "active"',
    'golden_result' => [['count' => 42]],
]);
```

| Field | Description |
|-------|-------------|
| `name` | A descriptive name for the test case |
| `category` | Grouping category (e.g., `basic`, `aggregation`, `complex`) |
| `question` | The natural language question to ask the agent |
| `expected_values` | Key-value pairs to match against results (supports dot notation) |
| `golden_sql` | The known-good SQL query for comparison |
| `golden_result` | The expected full result set |

### Running Evaluations

```bash
# Run all test cases
php artisan sql-agent:eval

# Run with LLM grading
php artisan sql-agent:eval --llm-grader

# Run a specific category
php artisan sql-agent:eval --category=aggregation

# Generate an HTML report
php artisan sql-agent:eval --html=storage/eval-report.html

# Seed built-in test cases first
php artisan sql-agent:eval --seed
```

### Evaluation Modes

Three evaluation modes are available:

| Mode | Description |
|------|-------------|
| **String Matching** (default) | Checks if expected values appear in the response |
| **LLM Grading** (`--llm-grader`) | Uses an LLM to semantically evaluate whether the response is correct |
| **Golden SQL** (`--golden-sql`) | Runs the golden SQL and compares its results against the agent's results |

## Self-Learning

SqlAgent can automatically learn from its mistakes and improve over time without any manual intervention.

### How It Works

1. The agent executes a SQL query and it fails
2. The agent analyzes the error, adjusts, and retries
3. If the recovery succeeds, a "learning" record is saved with the error context and fix
4. On future queries, relevant learnings are included in the agent's context
5. The agent avoids making the same mistake again

### Learning Categories

Learnings are automatically categorized:

| Category | Description |
|----------|-------------|
| `type_error` | Data type mismatches or casting issues |
| `schema_fix` | Incorrect schema assumptions (wrong table or column names) |
| `query_pattern` | Learned patterns for constructing queries |
| `data_quality` | Observations about data quality or anomalies |
| `business_logic` | Learned business rules or domain knowledge |

### Managing Learnings

Export and import learnings to share them across environments or back them up:

```bash
# Export all learnings to JSON
php artisan sql-agent:export-learnings

# Export a specific category
php artisan sql-agent:export-learnings --category=schema_fix

# Import learnings from a file
php artisan sql-agent:import-learnings learnings.json

# Prune learnings older than 90 days
php artisan sql-agent:prune-learnings --days=90

# Remove only duplicates
php artisan sql-agent:prune-learnings --duplicates

# Preview what would be removed
php artisan sql-agent:prune-learnings --dry-run
```

### Disabling Self-Learning

To disable the self-learning feature entirely:

```env
SQL_AGENT_LEARNING_ENABLED=false
```

To keep manual learning (via the `SaveLearningTool`) but disable automatic error-based learning:

```env
SQL_AGENT_AUTO_SAVE_ERRORS=false
```
