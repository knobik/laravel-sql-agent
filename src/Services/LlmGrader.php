<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Services;

use Knobik\SqlAgent\Data\GradeResult;
use Prism\Prism\Facades\Prism;

class LlmGrader
{
    protected const GRADER_SYSTEM_PROMPT = <<<'PROMPT'
You are an evaluation grader for a data agent. Your job is to determine if the agent's
response correctly answers the user's question.

You will be given:
1. The user's question
2. The agent's response
3. The expected answer (from a golden SQL query or expected values)

Evaluate based on:
- Factual correctness: Does the response contain the correct data?
- Completeness: Does it answer the question asked?
- No hallucinations: The response should not include made-up data.

Be lenient about:
- Extra context or insights (the agent may provide more than asked)
- Different phrasing or formatting
- Minor variations in names (e.g., "Lewis Hamilton" vs "Hamilton")

Respond in this exact format:
SCORE: [0.0-1.0]
PASSED: [true/false]
REASONING: [brief explanation]
PROMPT;

    public function grade(
        string $question,
        string $response,
        array $expectedStrings,
        ?array $goldenResult = null,
    ): GradeResult {
        $expectedContext = 'Expected values to appear: '.implode(', ', $expectedStrings);
        if ($goldenResult !== null) {
            $expectedContext .= "\n\nGolden SQL result:\n".$this->formatResult($goldenResult);
        }

        $userMessage = <<<MSG
Question: {$question}

Agent Response:
{$response}

Expected Answer:
{$expectedContext}

Grade this response.
MSG;

        $passThreshold = config('sql-agent.evaluation.pass_threshold');

        $prismResponse = Prism::text()
            ->using(
                config('sql-agent.evaluation.grader_provider'),
                config('sql-agent.evaluation.grader_model'),
            )
            ->withSystemPrompt(self::GRADER_SYSTEM_PROMPT)
            ->withPrompt($userMessage)
            ->asText();

        return GradeResult::fromLlmResponse($prismResponse->text, $passThreshold);
    }

    /**
     * Compare expected vs actual query results.
     *
     * @return array{matches: bool, explanation: string}
     */
    public function compareResults(
        array $expected,
        array $actual,
        ?array $keyColumns = null,
    ): array {
        if (empty($expected) && empty($actual)) {
            return ['matches' => true, 'explanation' => 'Both results are empty'];
        }

        if (empty($expected)) {
            return ['matches' => false, 'explanation' => 'Expected results are empty but actual has data'];
        }

        if (empty($actual)) {
            return ['matches' => false, 'explanation' => 'Actual results are empty but expected has data'];
        }

        $normalizeRow = function (array $row): array {
            $normalized = [];
            foreach ($row as $key => $value) {
                $normalized[strtolower(trim((string) $key))] = trim((string) $value);
            }

            return $normalized;
        };

        $expectedNormalized = array_map($normalizeRow, $expected);
        $actualNormalized = array_map($normalizeRow, $actual);

        if ($keyColumns !== null) {
            $keyCols = array_map(fn ($k) => strtolower(trim($k)), $keyColumns);
            $filterColumns = function (array $rows) use ($keyCols): array {
                return array_map(function ($row) use ($keyCols) {
                    return array_filter(
                        $row,
                        fn ($k) => in_array($k, $keyCols, true),
                        ARRAY_FILTER_USE_KEY
                    );
                }, $rows);
            };
            $expectedNormalized = $filterColumns($expectedNormalized);
            $actualNormalized = $filterColumns($actualNormalized);
        }

        $expectedFirst = $expectedNormalized[0] ?? [];
        $actualFirst = $actualNormalized[0] ?? [];

        if (count($expectedNormalized) === 1) {
            foreach ($expectedFirst as $key => $expectedVal) {
                if (isset($actualFirst[$key])) {
                    $actualVal = $actualFirst[$key];
                    if (strtolower($expectedVal) !== strtolower($actualVal)) {
                        return [
                            'matches' => false,
                            'explanation' => "Mismatch in '{$key}': expected '{$expectedVal}', got '{$actualVal}'",
                        ];
                    }
                } else {
                    $found = false;
                    foreach ($actualNormalized as $row) {
                        foreach ($row as $v) {
                            if (str_contains(strtolower($v), strtolower($expectedVal))) {
                                $found = true;
                                break 2;
                            }
                        }
                    }
                    if (! $found) {
                        return [
                            'matches' => false,
                            'explanation' => "Expected value '{$expectedVal}' not found in actual results",
                        ];
                    }
                }
            }

            return ['matches' => true, 'explanation' => 'Key values match'];
        }

        $expectedValues = [];
        foreach ($expectedNormalized as $row) {
            foreach ($row as $v) {
                $expectedValues[strtolower($v)] = true;
            }
        }

        $actualValues = [];
        foreach ($actualNormalized as $row) {
            foreach ($row as $v) {
                $actualValues[strtolower($v)] = true;
            }
        }

        $missing = array_diff(array_keys($expectedValues), array_keys($actualValues));
        if (! empty($missing)) {
            return [
                'matches' => false,
                'explanation' => 'Missing expected values: '.implode(', ', $missing),
            ];
        }

        return ['matches' => true, 'explanation' => 'All expected values found in actual results'];
    }

    protected function formatResult(array $result): string
    {
        if (empty($result)) {
            return '(empty result)';
        }

        $firstRow = $result[0] ?? [];
        $headers = array_keys($firstRow);
        $lines = [implode(' | ', $headers)];
        $lines[] = str_repeat('-', strlen($lines[0]));

        $displayRows = array_slice($result, 0, 10);
        foreach ($displayRows as $row) {
            $values = array_map(fn ($h) => (string) ($row[$h] ?? ''), $headers);
            $lines[] = implode(' | ', $values);
        }

        if (count($result) > 10) {
            $lines[] = '... and '.(count($result) - 10).' more rows';
        }

        return implode("\n", $lines);
    }
}
