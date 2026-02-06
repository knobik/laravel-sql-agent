<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Agent;

class FallbackResponseGenerator
{
    public function generate(?array $results): string
    {
        if ($results === null) {
            return 'The query was executed but returned no results.';
        }

        $rowCount = count($results);

        if ($rowCount === 0) {
            return 'The query was executed successfully but returned no results.';
        }

        // For single-value results (like COUNT queries), provide a direct answer
        if ($rowCount === 1 && count($results[0]) === 1) {
            $value = array_values($results[0])[0];
            $key = array_keys($results[0])[0];

            // Try to make a natural language response based on the column name
            $key = str_replace('_', ' ', $key);

            return "The result is **{$value}** ({$key}).";
        }

        // For single row with multiple columns, list them
        if ($rowCount === 1) {
            $row = $results[0];
            $parts = [];
            foreach ($row as $key => $value) {
                $key = str_replace('_', ' ', $key);
                $parts[] = "**{$key}**: {$value}";
            }

            return 'Here is the result: '.implode(', ', $parts);
        }

        return "The query returned **{$rowCount}** rows.";
    }
}
