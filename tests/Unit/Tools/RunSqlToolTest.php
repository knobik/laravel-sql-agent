<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Knobik\SqlAgent\Contracts\ToolResult;
use Knobik\SqlAgent\Tools\RunSqlTool;

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

describe('RunSqlTool', function () {
    it('executes valid SELECT queries', function () {
        $tool = new RunSqlTool;

        $result = $tool->execute(['sql' => 'SELECT * FROM test_users']);

        expect($result->success)->toBeTrue();
        expect($result->data['rows'])->toHaveCount(2);
        expect($result->data['row_count'])->toBe(2);
    });

    it('executes WITH statements', function () {
        $tool = new RunSqlTool;

        $result = $tool->execute([
            'sql' => 'WITH user_names AS (SELECT name FROM test_users) SELECT * FROM user_names',
        ]);

        expect($result->success)->toBeTrue();
        expect($result->data['rows'])->toHaveCount(2);
    });

    it('rejects empty SQL', function () {
        $tool = new RunSqlTool;

        $result = $tool->execute(['sql' => '']);

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('empty');
    });

    it('rejects INSERT statements', function () {
        $tool = new RunSqlTool;

        $result = $tool->execute(['sql' => "INSERT INTO test_users (name) VALUES ('Test')"]);

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('Only');
    });

    it('rejects DROP statements', function () {
        $tool = new RunSqlTool;

        $result = $tool->execute(['sql' => 'DROP TABLE test_users']);

        expect($result->success)->toBeFalse();
        expect($result->error)->toContain('Only');
    });

    it('rejects SELECT with DELETE keyword', function () {
        $tool = new RunSqlTool;

        $result = $tool->execute(['sql' => 'SELECT * FROM test_users; DELETE FROM test_users']);

        expect($result->success)->toBeFalse();
    });

    it('rejects UPDATE statements', function () {
        $tool = new RunSqlTool;

        $result = $tool->execute(['sql' => "UPDATE test_users SET name = 'Test'"]);

        expect($result->success)->toBeFalse();
    });

    it('returns ToolResult', function () {
        $tool = new RunSqlTool;

        $result = $tool->execute(['sql' => 'SELECT * FROM test_users']);

        expect($result)->toBeInstanceOf(ToolResult::class);
    });

    it('has correct name and description', function () {
        $tool = new RunSqlTool;

        expect($tool->name())->toBe('run_sql');
        expect($tool->description())->toContain('Execute');
    });

    it('has correct parameters schema', function () {
        $tool = new RunSqlTool;
        $params = $tool->parameters();

        expect($params['type'])->toBe('object');
        expect($params['properties'])->toHaveKey('sql');
        expect($params['required'])->toContain('sql');
    });

    it('can set connection', function () {
        $tool = new RunSqlTool;

        $result = $tool->setConnection('testing');

        expect($result)->toBe($tool);
    });

    it('can set and get question', function () {
        $tool = new RunSqlTool;

        $tool->setQuestion('How many users?');

        expect($tool->getQuestion())->toBe('How many users?');
    });
});
