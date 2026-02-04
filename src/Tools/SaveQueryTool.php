<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Tools;

use Knobik\SqlAgent\Models\QueryPattern;
use RuntimeException;

class SaveQueryTool extends BaseTool
{
    public function name(): string
    {
        return 'save_validated_query';
    }

    public function description(): string
    {
        return 'Save a validated query pattern to the knowledge base. Use this when you have successfully executed a SQL query that correctly answers a user question. This helps future queries by providing proven patterns.';
    }

    protected function schema(): array
    {
        return $this->objectSchema([
            'name' => $this->stringProperty('A short, descriptive name for the query pattern (max 100 characters).'),
            'question' => $this->stringProperty('The natural language question this query answers.'),
            'sql' => $this->stringProperty('The validated SQL query that correctly answers the question.'),
            'summary' => $this->stringProperty('A brief summary of what the query does and what data it returns.'),
            'tables_used' => $this->arrayProperty(
                'List of table names used in the query.',
                ['type' => 'string']
            ),
            'data_quality_notes' => $this->stringProperty('Optional: Notes about data quality issues, edge cases, or important considerations for this query.'),
        ], ['name', 'question', 'sql', 'summary', 'tables_used']);
    }

    protected function handle(array $parameters): mixed
    {
        $name = trim($parameters['name'] ?? '');
        $question = trim($parameters['question'] ?? '');
        $sql = trim($parameters['sql'] ?? '');
        $summary = trim($parameters['summary'] ?? '');
        $tablesUsed = $parameters['tables_used'] ?? [];
        $dataQualityNotes = isset($parameters['data_quality_notes']) ? trim($parameters['data_quality_notes']) : null;

        // Validation
        if (empty($name)) {
            throw new RuntimeException('Name is required.');
        }

        if (strlen($name) > 100) {
            throw new RuntimeException('Name must be 100 characters or less.');
        }

        if (empty($question)) {
            throw new RuntimeException('Question is required.');
        }

        if (empty($sql)) {
            throw new RuntimeException('SQL is required.');
        }

        if (empty($summary)) {
            throw new RuntimeException('Summary is required.');
        }

        if (empty($tablesUsed) || ! is_array($tablesUsed)) {
            throw new RuntimeException('Tables used must be a non-empty array.');
        }

        // Validate SQL starts with SELECT or WITH
        $sqlUpper = strtoupper(trim($sql));
        if (! str_starts_with($sqlUpper, 'SELECT') && ! str_starts_with($sqlUpper, 'WITH')) {
            throw new RuntimeException('SQL must be a SELECT or WITH statement.');
        }

        // Normalize tables_used to array of strings
        $tablesUsed = array_values(array_filter(array_map(function ($table) {
            return is_string($table) ? trim($table) : null;
        }, $tablesUsed)));

        if (empty($tablesUsed)) {
            throw new RuntimeException('Tables used must contain at least one valid table name.');
        }

        // Check for duplicate by question similarity
        $existing = QueryPattern::search($question)->first();
        if ($existing && strtolower($existing->question) === strtolower($question)) {
            throw new RuntimeException("A query pattern with a similar question already exists: '{$existing->name}'");
        }

        $queryPattern = QueryPattern::create([
            'name' => $name,
            'question' => $question,
            'sql' => $sql,
            'summary' => $summary,
            'tables_used' => $tablesUsed,
            'data_quality_notes' => $dataQualityNotes ?: null,
        ]);

        return [
            'success' => true,
            'message' => 'Query pattern saved successfully.',
            'pattern_id' => $queryPattern->id,
            'name' => $queryPattern->name,
            'tables_used' => $queryPattern->tables_used,
        ];
    }
}
