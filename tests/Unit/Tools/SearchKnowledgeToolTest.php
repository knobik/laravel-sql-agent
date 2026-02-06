<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Knobik\SqlAgent\Enums\LearningCategory;
use Knobik\SqlAgent\Models\Learning;
use Knobik\SqlAgent\Models\QueryPattern;
use Knobik\SqlAgent\Tools\SearchKnowledgeTool;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate');

    // Create a test table for SQL execution tests
    DB::statement('CREATE TABLE IF NOT EXISTS test_users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');
    DB::table('test_users')->insert([
        ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
    ]);
});

afterEach(function () {
    DB::statement('DROP TABLE IF EXISTS test_users');
});

describe('SearchKnowledgeTool', function () {
    beforeEach(function () {
        // Create some test data
        QueryPattern::create([
            'name' => 'user_count',
            'question' => 'How many users are there?',
            'sql' => 'SELECT COUNT(*) FROM users',
            'summary' => 'Counts all users',
            'tables_used' => ['users'],
        ]);

        QueryPattern::create([
            'name' => 'order_total',
            'question' => 'What is the total order amount?',
            'sql' => 'SELECT SUM(total) FROM orders',
            'summary' => 'Sums all order totals',
            'tables_used' => ['orders'],
        ]);

        Learning::create([
            'title' => 'User table soft deletes',
            'description' => 'The users table uses soft deletes, check deleted_at column.',
            'category' => LearningCategory::BusinessLogic,
        ]);
    });

    it('searches query patterns', function () {
        $tool = app(SearchKnowledgeTool::class);

        $result = $tool->execute([
            'query' => 'users',
            'type' => 'patterns',
        ]);

        expect($result->success)->toBeTrue();
        expect($result->data['query_patterns'])->toHaveCount(1);
        expect($result->data['query_patterns'][0]['name'])->toBe('user_count');
    });

    it('searches learnings', function () {
        $tool = app(SearchKnowledgeTool::class);

        $result = $tool->execute([
            'query' => 'soft deletes',
            'type' => 'learnings',
        ]);

        expect($result->success)->toBeTrue();
        expect($result->data['learnings'])->toHaveCount(1);
        expect($result->data['learnings'][0]['title'])->toBe('User table soft deletes');
    });

    it('searches all by default', function () {
        $tool = app(SearchKnowledgeTool::class);

        $result = $tool->execute([
            'query' => 'user',
        ]);

        expect($result->success)->toBeTrue();
        expect($result->data)->toHaveKey('query_patterns');
        expect($result->data)->toHaveKey('learnings');
        expect($result->data['total_found'])->toBeGreaterThan(0);
    });

    it('requires query', function () {
        $tool = app(SearchKnowledgeTool::class);

        $result = $tool->execute([]);

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('empty');
    });

    it('respects limit parameter', function () {
        // Add more patterns
        for ($i = 1; $i <= 10; $i++) {
            QueryPattern::create([
                'name' => "pattern_{$i}",
                'question' => "Question about users {$i}",
                'sql' => 'SELECT * FROM users',
                'summary' => "Pattern {$i}",
                'tables_used' => ['users'],
            ]);
        }

        $tool = app(SearchKnowledgeTool::class);

        $result = $tool->execute([
            'query' => 'users',
            'type' => 'patterns',
            'limit' => 3,
        ]);

        expect($result->success)->toBeTrue();
        expect($result->data['query_patterns'])->toHaveCount(3);
    });

    it('has correct name', function () {
        $tool = app(SearchKnowledgeTool::class);

        expect($tool->name())->toBe('search_knowledge');
    });

    it('enforces max limit of 20', function () {
        $tool = app(SearchKnowledgeTool::class);
        $params = $tool->parameters();

        expect($params['properties']['limit']['maximum'])->toBe(20);
    });
});
