<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Knobik\SqlAgent\Contracts\ToolResult;

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

describe('ToolResult', function () {
    it('creates success result', function () {
        $result = ToolResult::success(['key' => 'value']);

        expect($result->success)->toBeTrue();
        expect($result->data)->toBe(['key' => 'value']);
        expect($result->error)->toBeNull();
    });

    it('creates failure result', function () {
        $result = ToolResult::failure('Something went wrong');

        expect($result->success)->toBeFalse();
        expect($result->data)->toBeNull();
        expect($result->error)->toBe('Something went wrong');
    });
});
