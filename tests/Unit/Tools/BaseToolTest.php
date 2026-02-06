<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Knobik\SqlAgent\Tools\BaseTool;

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

describe('BaseTool', function () {
    it('wraps exceptions in failure result', function () {
        // Create a tool that throws an exception
        $tool = new class extends BaseTool
        {
            public function name(): string
            {
                return 'test_tool';
            }

            public function description(): string
            {
                return 'Test tool';
            }

            protected function schema(): array
            {
                return $this->objectSchema([]);
            }

            protected function handle(array $parameters): mixed
            {
                throw new RuntimeException('Test error');
            }
        };

        $result = $tool->execute([]);

        expect($result->success)->toBeFalse();
        expect($result->error)->toBe('Test error');
    });

    it('provides helper methods for schema building', function () {
        $tool = new class extends BaseTool
        {
            public function name(): string
            {
                return 'test_tool';
            }

            public function description(): string
            {
                return 'Test tool';
            }

            protected function schema(): array
            {
                return $this->objectSchema([
                    'string_prop' => $this->stringProperty('A string', ['a', 'b']),
                    'bool_prop' => $this->booleanProperty('A bool', true),
                    'int_prop' => $this->integerProperty('An int', 1, 100),
                    'array_prop' => $this->arrayProperty('An array', ['type' => 'string']),
                ], ['string_prop']);
            }

            protected function handle(array $parameters): mixed
            {
                return true;
            }
        };

        $params = $tool->parameters();

        expect($params['properties']['string_prop']['type'])->toBe('string');
        expect($params['properties']['string_prop']['enum'])->toBe(['a', 'b']);
        expect($params['properties']['bool_prop']['type'])->toBe('boolean');
        expect($params['properties']['bool_prop']['default'])->toBeTrue();
        expect($params['properties']['int_prop']['type'])->toBe('integer');
        expect($params['properties']['int_prop']['minimum'])->toBe(1);
        expect($params['properties']['int_prop']['maximum'])->toBe(100);
        expect($params['properties']['array_prop']['type'])->toBe('array');
        expect($params['required'])->toBe(['string_prop']);
    });
});
