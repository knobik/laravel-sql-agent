<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Knobik\SqlAgent\Models\QueryPattern;
use Knobik\SqlAgent\Tools\SaveQueryTool;

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

describe('SaveQueryTool', function () {
    it('saves a query pattern', function () {
        $tool = new SaveQueryTool;

        $result = $tool->execute([
            'name' => 'User Count',
            'question' => 'How many users are there?',
            'sql' => 'SELECT COUNT(*) as count FROM users',
            'summary' => 'Counts total users in the system',
            'tables_used' => ['users'],
        ]);

        expect($result->success)->toBeTrue();
        expect($result->data['success'])->toBeTrue();
        expect($result->data['pattern_id'])->toBeInt();
        expect($result->data['name'])->toBe('User Count');

        $pattern = QueryPattern::find($result->data['pattern_id']);
        expect($pattern->question)->toBe('How many users are there?');
        expect($pattern->tables_used)->toBe(['users']);
    });

    it('saves with data quality notes', function () {
        $tool = new SaveQueryTool;

        $result = $tool->execute([
            'name' => 'Active Users',
            'question' => 'How many active users?',
            'sql' => 'SELECT COUNT(*) FROM users WHERE active = 1',
            'summary' => 'Counts active users',
            'tables_used' => ['users'],
            'data_quality_notes' => 'Some users may have NULL active status',
        ]);

        expect($result->success)->toBeTrue();

        $pattern = QueryPattern::find($result->data['pattern_id']);
        expect($pattern->data_quality_notes)->toBe('Some users may have NULL active status');
    });

    it('requires name', function () {
        $tool = new SaveQueryTool;

        $result = $tool->execute([
            'question' => 'How many users?',
            'sql' => 'SELECT COUNT(*) FROM users',
            'summary' => 'Counts users',
            'tables_used' => ['users'],
        ]);

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('Name is required');
    });

    it('requires question', function () {
        $tool = new SaveQueryTool;

        $result = $tool->execute([
            'name' => 'User Count',
            'sql' => 'SELECT COUNT(*) FROM users',
            'summary' => 'Counts users',
            'tables_used' => ['users'],
        ]);

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('Question is required');
    });

    it('requires sql', function () {
        $tool = new SaveQueryTool;

        $result = $tool->execute([
            'name' => 'User Count',
            'question' => 'How many users?',
            'summary' => 'Counts users',
            'tables_used' => ['users'],
        ]);

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('SQL is required');
    });

    it('requires summary', function () {
        $tool = new SaveQueryTool;

        $result = $tool->execute([
            'name' => 'User Count',
            'question' => 'How many users?',
            'sql' => 'SELECT COUNT(*) FROM users',
            'tables_used' => ['users'],
        ]);

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('Summary is required');
    });

    it('requires tables_used', function () {
        $tool = new SaveQueryTool;

        $result = $tool->execute([
            'name' => 'User Count',
            'question' => 'How many users?',
            'sql' => 'SELECT COUNT(*) FROM users',
            'summary' => 'Counts users',
        ]);

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('Tables used');
    });

    it('rejects empty tables_used array', function () {
        $tool = new SaveQueryTool;

        $result = $tool->execute([
            'name' => 'User Count',
            'question' => 'How many users?',
            'sql' => 'SELECT COUNT(*) FROM users',
            'summary' => 'Counts users',
            'tables_used' => [],
        ]);

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('Tables used');
    });

    it('validates name length', function () {
        $tool = new SaveQueryTool;

        $result = $tool->execute([
            'name' => str_repeat('a', 101),
            'question' => 'How many users?',
            'sql' => 'SELECT COUNT(*) FROM users',
            'summary' => 'Counts users',
            'tables_used' => ['users'],
        ]);

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('100 characters');
    });

    it('only allows SELECT or WITH statements', function () {
        $tool = new SaveQueryTool;

        $result = $tool->execute([
            'name' => 'Bad Query',
            'question' => 'Delete all users?',
            'sql' => 'DELETE FROM users',
            'summary' => 'Deletes users',
            'tables_used' => ['users'],
        ]);

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('SELECT or WITH');
    });

    it('accepts WITH statements', function () {
        $tool = new SaveQueryTool;

        $result = $tool->execute([
            'name' => 'Complex Query',
            'question' => 'Get users with orders?',
            'sql' => 'WITH user_orders AS (SELECT * FROM orders) SELECT * FROM users JOIN user_orders ON users.id = user_orders.user_id',
            'summary' => 'Gets users with orders',
            'tables_used' => ['users', 'orders'],
        ]);

        expect($result->success)->toBeTrue();
    });

    it('has correct name', function () {
        $tool = new SaveQueryTool;

        expect($tool->name())->toBe('save_validated_query');
    });

    it('has correct parameters schema', function () {
        $tool = new SaveQueryTool;
        $params = $tool->parameters();

        expect($params['type'])->toBe('object');
        expect($params['properties'])->toHaveKey('name');
        expect($params['properties'])->toHaveKey('question');
        expect($params['properties'])->toHaveKey('sql');
        expect($params['properties'])->toHaveKey('summary');
        expect($params['properties'])->toHaveKey('tables_used');
        expect($params['properties'])->toHaveKey('data_quality_notes');
        expect($params['required'])->toContain('name');
        expect($params['required'])->toContain('question');
        expect($params['required'])->toContain('sql');
        expect($params['required'])->toContain('summary');
        expect($params['required'])->toContain('tables_used');
    });
});
