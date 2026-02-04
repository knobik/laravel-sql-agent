<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Database\Seeders;

use Illuminate\Database\Seeder;
use Knobik\SqlAgent\Models\TestCase;

class TestCaseSeeder extends Seeder
{
    /**
     * Seed the test cases from the Python evaluation system.
     * Total: 18 test cases across 5 categories.
     */
    public function run(): void
    {
        $testCases = $this->getTestCases();

        foreach ($testCases as $testCase) {
            TestCase::updateOrCreate(
                ['name' => $testCase['name']],
                $testCase
            );
        }
    }

    /**
     * Get all test cases.
     * Ported from dash/dash/evals/test_cases.py
     *
     * @return array<array{category: string, name: string, question: string, expected_values: array, golden_sql: string|null, golden_result: array|null}>
     */
    protected function getTestCases(): array
    {
        return [
            // ===================
            // BASIC (4 cases)
            // ===================
            [
                'category' => 'basic',
                'name' => 'race_winner_2019',
                'question' => 'Who won the most races in 2019?',
                'expected_values' => ['Hamilton', '11'],
                'golden_sql' => <<<'SQL'
                    SELECT name, COUNT(*) as wins
                    FROM race_wins
                    WHERE EXTRACT(YEAR FROM TO_DATE(date, 'DD Mon YYYY')) = 2019
                    GROUP BY name
                    ORDER BY wins DESC
                    LIMIT 1
                    SQL,
                'golden_result' => null,
            ],
            [
                'category' => 'basic',
                'name' => 'constructors_2020',
                'question' => 'Which team won the 2020 constructors championship?',
                'expected_values' => ['Mercedes'],
                'golden_sql' => <<<'SQL'
                    SELECT team
                    FROM constructors_championship
                    WHERE year = 2020 AND position = 1
                    SQL,
                'golden_result' => null,
            ],
            [
                'category' => 'basic',
                'name' => 'drivers_champion_2020',
                'question' => 'Who won the 2020 drivers championship?',
                'expected_values' => ['Hamilton'],
                'golden_sql' => <<<'SQL'
                    SELECT name
                    FROM drivers_championship
                    WHERE year = 2020 AND position = '1'
                    SQL,
                'golden_result' => null,
            ],
            [
                'category' => 'basic',
                'name' => 'race_count_2019',
                'question' => 'How many races were there in 2019?',
                'expected_values' => ['21'],
                'golden_sql' => <<<'SQL'
                    SELECT COUNT(DISTINCT venue) as race_count
                    FROM race_wins
                    WHERE EXTRACT(YEAR FROM TO_DATE(date, 'DD Mon YYYY')) = 2019
                    SQL,
                'golden_result' => null,
            ],

            // ===================
            // AGGREGATION (5 cases)
            // ===================
            [
                'category' => 'aggregation',
                'name' => 'most_championships_driver',
                'question' => 'Which driver has won the most world championships?',
                'expected_values' => ['Schumacher', '7'],
                'golden_sql' => <<<'SQL'
                    SELECT name, COUNT(*) as titles
                    FROM drivers_championship
                    WHERE position = '1'
                    GROUP BY name
                    ORDER BY titles DESC
                    LIMIT 1
                    SQL,
                'golden_result' => null,
            ],
            [
                'category' => 'aggregation',
                'name' => 'most_championships_constructor',
                'question' => 'Which constructor has won the most championships?',
                'expected_values' => ['Ferrari'],
                'golden_sql' => <<<'SQL'
                    SELECT team, COUNT(*) as titles
                    FROM constructors_championship
                    WHERE position = 1
                    GROUP BY team
                    ORDER BY titles DESC
                    LIMIT 1
                    SQL,
                'golden_result' => null,
            ],
            [
                'category' => 'aggregation',
                'name' => 'fastest_laps_monaco',
                'question' => 'Who has the most fastest laps at Monaco?',
                'expected_values' => ['Schumacher'],
                'golden_sql' => <<<'SQL'
                    SELECT name, COUNT(*) as fastest_laps
                    FROM fastest_laps
                    WHERE venue = 'Monaco'
                    GROUP BY name
                    ORDER BY fastest_laps DESC
                    LIMIT 1
                    SQL,
                'golden_result' => null,
            ],
            [
                'category' => 'aggregation',
                'name' => 'hamilton_wins',
                'question' => 'How many race wins does Lewis Hamilton have in total?',
                'expected_values' => ['Hamilton'],
                'golden_sql' => <<<'SQL'
                    SELECT COUNT(*) as wins
                    FROM race_wins
                    WHERE name = 'Lewis Hamilton'
                    SQL,
                'golden_result' => null,
            ],
            [
                'category' => 'aggregation',
                'name' => 'most_wins_team',
                'question' => 'Which team has the most race wins all time?',
                'expected_values' => ['Ferrari'],
                'golden_sql' => <<<'SQL'
                    SELECT team, COUNT(*) as wins
                    FROM race_wins
                    GROUP BY team
                    ORDER BY wins DESC
                    LIMIT 1
                    SQL,
                'golden_result' => null,
            ],

            // ===================
            // DATA QUALITY (4 cases)
            // Tests type handling: position as TEXT, date parsing
            // ===================
            [
                'category' => 'data_quality',
                'name' => 'second_place_2019',
                'question' => 'Who finished second in the 2019 drivers championship?',
                'expected_values' => ['Bottas'],
                'golden_sql' => <<<'SQL'
                    SELECT name
                    FROM drivers_championship
                    WHERE year = 2019 AND position = '2'
                    SQL,
                'golden_result' => null,
            ],
            [
                'category' => 'data_quality',
                'name' => 'third_place_constructors_2020',
                'question' => 'Which team came third in the 2020 constructors championship?',
                'expected_values' => ['McLaren'],
                'golden_sql' => <<<'SQL'
                    SELECT team
                    FROM constructors_championship
                    WHERE year = 2020 AND position = 3
                    SQL,
                'golden_result' => null,
            ],
            [
                'category' => 'data_quality',
                'name' => 'ferrari_wins_2019',
                'question' => 'How many races did Ferrari win in 2019?',
                'expected_values' => ['3'],
                'golden_sql' => <<<'SQL'
                    SELECT COUNT(*) as wins
                    FROM race_wins
                    WHERE team = 'Ferrari'
                      AND EXTRACT(YEAR FROM TO_DATE(date, 'DD Mon YYYY')) = 2019
                    SQL,
                'golden_result' => null,
            ],
            [
                'category' => 'data_quality',
                'name' => 'retirements_2020',
                'question' => 'How many retirements were there in 2020?',
                'expected_values' => ['Ret'],
                'golden_sql' => null, // No golden SQL - checking that agent handles non-numeric positions
                'golden_result' => null,
            ],

            // ===================
            // COMPLEX (3 cases)
            // JOINs, multiple conditions
            // ===================
            [
                'category' => 'complex',
                'name' => 'ferrari_vs_mercedes',
                'question' => 'Compare Ferrari vs Mercedes championship points from 2015-2020',
                'expected_values' => ['Ferrari', 'Mercedes'],
                'golden_sql' => null, // Complex comparison - just check strings are present
                'golden_result' => null,
            ],
            [
                'category' => 'complex',
                'name' => 'most_podiums_2019',
                'question' => 'Who had the most podium finishes in 2019?',
                'expected_values' => ['Hamilton'],
                'golden_sql' => <<<'SQL'
                    SELECT name, COUNT(*) as podiums
                    FROM race_results
                    WHERE position IN ('1', '2', '3')
                      AND year = 2019
                    GROUP BY name
                    ORDER BY podiums DESC
                    LIMIT 1
                    SQL,
                'golden_result' => null,
            ],
            [
                'category' => 'complex',
                'name' => 'most_ferrari_wins',
                'question' => 'Which driver won the most races for Ferrari?',
                'expected_values' => ['Schumacher'],
                'golden_sql' => <<<'SQL'
                    SELECT name, COUNT(*) as wins
                    FROM race_wins
                    WHERE team = 'Ferrari'
                    GROUP BY name
                    ORDER BY wins DESC
                    LIMIT 1
                    SQL,
                'golden_result' => null,
            ],

            // ===================
            // EDGE CASE (2 cases)
            // Empty results, boundary conditions
            // ===================
            [
                'category' => 'edge_case',
                'name' => 'constructors_1950',
                'question' => 'Who won the constructors championship in 1950?',
                'expected_values' => ['no', '1958'],
                // Should mention constructors championship didn't exist until 1958
                'golden_sql' => null,
                'golden_result' => null,
            ],
            [
                'category' => 'edge_case',
                'name' => 'exactly_five_championships',
                'question' => 'Which driver has exactly 5 world championships?',
                'expected_values' => ['Fangio'],
                'golden_sql' => <<<'SQL'
                    SELECT name
                    FROM (
                        SELECT name, COUNT(*) as titles
                        FROM drivers_championship
                        WHERE position = '1'
                        GROUP BY name
                    ) t
                    WHERE titles = 5
                    SQL,
                'golden_result' => null,
            ],
        ];
    }
}
