<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Tools;

use Illuminate\Support\Facades\DB;
use Knobik\SqlAgent\Events\SqlErrorOccurred;
use RuntimeException;
use Throwable;

class RunSqlTool extends BaseTool
{
    protected ?string $connection = null;

    protected ?string $question = null;

    public function name(): string
    {
        return 'run_sql';
    }

    public function description(): string
    {
        return 'Execute a SQL query against the database. Only SELECT and WITH statements are allowed. Returns query results as JSON.';
    }

    protected function schema(): array
    {
        return $this->objectSchema([
            'sql' => $this->stringProperty('The SQL query to execute. Must be a SELECT or WITH statement.'),
        ], ['sql']);
    }

    /**
     * Set the database connection to use.
     */
    public function setConnection(?string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the original question for error learning context.
     */
    public function setQuestion(?string $question): self
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get the current question.
     */
    public function getQuestion(): ?string
    {
        return $this->question;
    }

    protected function handle(array $parameters): mixed
    {
        // Accept either 'sql' or 'query' parameter (some models use 'query')
        $sql = trim($parameters['sql'] ?? $parameters['query'] ?? '');

        if (empty($sql)) {
            throw new RuntimeException('SQL query cannot be empty.');
        }

        $this->validateSql($sql);

        $connection = $this->connection ?? config('sql-agent.database.connection');
        $maxRows = config('sql-agent.sql.max_rows', 1000);

        try {
            // Execute the query
            $results = DB::connection($connection)->select($sql);
        } catch (Throwable $e) {
            // Dispatch error event for auto-learning
            if ($this->question !== null) {
                SqlErrorOccurred::dispatch(
                    $sql,
                    $e->getMessage(),
                    $this->question,
                    $connection,
                );
            }

            throw $e;
        }

        // Convert to arrays
        $rows = array_map(fn ($row) => (array) $row, $results);

        // Limit results
        $totalRows = count($rows);
        $rows = array_slice($rows, 0, $maxRows);

        return [
            'rows' => $rows,
            'row_count' => count($rows),
            'total_rows' => $totalRows,
            'truncated' => $totalRows > $maxRows,
        ];
    }

    protected function validateSql(string $sql): void
    {
        $sqlUpper = strtoupper(trim($sql));

        // Check for allowed statements
        $allowedStatements = config('sql-agent.sql.allowed_statements', ['SELECT', 'WITH']);
        $startsWithAllowed = false;

        foreach ($allowedStatements as $statement) {
            if (str_starts_with($sqlUpper, $statement)) {
                $startsWithAllowed = true;
                break;
            }
        }

        if (! $startsWithAllowed) {
            throw new RuntimeException(
                'Only '.implode(' and ', $allowedStatements).' statements are allowed.'
            );
        }

        // Check for forbidden keywords
        $forbiddenKeywords = config('sql-agent.sql.forbidden_keywords', [
            'DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE',
            'TRUNCATE', 'GRANT', 'REVOKE', 'EXEC', 'EXECUTE',
        ]);

        // Use word boundaries to avoid false positives
        foreach ($forbiddenKeywords as $keyword) {
            $pattern = '/\b'.preg_quote($keyword, '/').'\b/i';
            if (preg_match($pattern, $sql)) {
                throw new RuntimeException(
                    "Forbidden SQL keyword detected: {$keyword}. This query cannot be executed."
                );
            }
        }

        // Check for multiple statements (prevent SQL injection via semicolons)
        $withoutStrings = preg_replace("/'[^']*'/", '', $sql);
        $withoutStrings = preg_replace('/"[^"]*"/', '', $withoutStrings);

        if (substr_count($withoutStrings, ';') > 1) {
            throw new RuntimeException('Multiple SQL statements are not allowed.');
        }
    }
}
