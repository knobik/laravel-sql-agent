# Contributing to Laravel SQL Agent

Thank you for considering contributing to Laravel SQL Agent! This document outlines the process and guidelines for contributing.

## Development Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- A database (MySQL, PostgreSQL, or SQLite for testing)

### Installation

1. Fork the repository on GitHub

2. Clone your fork locally:

```bash
git clone https://github.com/YOUR_USERNAME/laravel-sql-agent.git
cd laravel-sql-agent
```

3. Install dependencies:

```bash
composer install
```

4. Create a test environment file (optional, for integration tests):

```bash
cp .env.example .env.testing
```

## Running Tests

### Using Pest

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test file
./vendor/bin/pest tests/Unit/ExampleTest.php

# Run tests matching a pattern
./vendor/bin/pest --filter="agent"
```

### Test Organization

- `tests/Unit/` - Unit tests for individual classes
- `tests/Feature/` - Integration/feature tests
- `tests/` - Test helpers and base test case

## Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code formatting with the Laravel preset.

### Check Code Style

```bash
composer format-check
```

### Fix Code Style

```bash
composer format
```

### Editor Integration

Most editors can be configured to run Pint on save. For VS Code, consider using the "Laravel Pint" extension.

## Static Analysis

This project uses [PHPStan](https://phpstan.org/) for static analysis at level 6.

### Run Analysis

```bash
composer analyse
```

### PHPStan Configuration

The configuration is in `phpstan.neon`. If you need to ignore specific errors, add them to the `ignoreErrors` section with clear comments explaining why.

## Pull Request Guidelines

### Before Submitting

1. **Create an issue first** for significant changes to discuss the approach
2. **Fork and branch** from `main` for your changes
3. **Write tests** for new functionality or bug fixes
4. **Update documentation** if your changes affect the public API
5. **Run the full test suite** and ensure all tests pass
6. **Run code style checks** and fix any issues
7. **Run static analysis** and address any errors

### Branch Naming

Use descriptive branch names:

- `feature/add-custom-llm-driver`
- `fix/search-driver-null-handling`
- `docs/improve-readme`
- `refactor/simplify-agent-loop`

### Commit Messages

Write clear, concise commit messages:

- Use the imperative mood ("Add feature" not "Added feature")
- Keep the first line under 72 characters
- Reference issues when relevant (`Fixes #123`)

Good examples:
```
Add support for custom LLM drivers

Implement LlmDriver interface and factory pattern to allow
users to register custom LLM providers.

Fixes #45
```

```
Fix null pointer in DatabaseSearchDriver

Handle case where full-text index doesn't exist by falling
back to LIKE queries.
```

### Pull Request Template

When opening a PR, please include:

1. **Description** - What does this PR do?
2. **Motivation** - Why is this change needed?
3. **Testing** - How was this tested?
4. **Breaking Changes** - Does this break backward compatibility?

## Issue Reporting

### Bug Reports

When reporting bugs, please include:

1. **Description** - Clear description of the bug
2. **Steps to Reproduce** - Minimal steps to reproduce the issue
3. **Expected Behavior** - What you expected to happen
4. **Actual Behavior** - What actually happened
5. **Environment** - PHP version, Laravel version, database, LLM driver
6. **Logs/Errors** - Relevant error messages or stack traces

### Feature Requests

For feature requests, please include:

1. **Description** - Clear description of the feature
2. **Use Case** - Why is this feature needed?
3. **Proposed Solution** - How might this be implemented?
4. **Alternatives** - Other approaches you've considered

## Code Guidelines

### General Principles

- Follow PSR-12 coding standards
- Write self-documenting code with clear variable and method names
- Keep methods focused and small (single responsibility)
- Prefer composition over inheritance
- Write tests for public methods

### Type Hints

Always use strict types and type hints:

```php
<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Example;

class ExampleClass
{
    public function process(string $input, ?int $limit = null): array
    {
        // ...
    }
}
```

### Documentation

- Add PHPDoc blocks for public methods
- Document complex algorithms with inline comments
- Keep README and other docs up to date

```php
/**
 * Execute a SQL query and return the results.
 *
 * @param string $sql The SQL query to execute
 * @param array<string, mixed> $bindings Query parameter bindings
 * @return array<int, array<string, mixed>> Query results
 *
 * @throws \Knobik\SqlAgent\Exceptions\SqlExecutionException
 */
public function execute(string $sql, array $bindings = []): array
{
    // ...
}
```

### Testing

- Write unit tests for isolated logic
- Write feature tests for integration scenarios
- Use descriptive test names that explain the scenario
- Follow Arrange-Act-Assert pattern

```php
it('returns user count when asked about users', function () {
    // Arrange
    User::factory()->count(5)->create();

    // Act
    $response = SqlAgent::run('How many users are there?');

    // Assert
    expect($response->isSuccess())->toBeTrue();
    expect($response->results)->toHaveCount(1);
    expect($response->results[0]['count'])->toBe(5);
});
```

## Getting Help

- **Questions**: Open a discussion on GitHub
- **Bugs**: Open an issue with the bug template
- **Security**: See SECURITY.md for reporting vulnerabilities

## License

By contributing to Laravel SQL Agent, you agree that your contributions will be licensed under the Apache-2.0 License.
