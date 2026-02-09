<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Knobik\SqlAgent\Agent\ToolRegistry;
use Prism\Prism\Tool;

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

describe('Tool Registration Integration', function () {
    it('registers all tools via service provider', function () {
        $registry = app(ToolRegistry::class);
        $names = $registry->names();

        expect($names)->toContain('run_sql');
        expect($names)->toContain('introspect_schema');
        expect($names)->toContain('save_learning');
        expect($names)->toContain('save_validated_query');
        expect($names)->toContain('search_knowledge');
    });

    it('all registered tools implement Tool interface', function () {
        $registry = app(ToolRegistry::class);

        foreach ($registry->all() as $tool) {
            expect($tool)->toBeInstanceOf(Tool::class);
        }
    });

    it('all tools have valid names', function () {
        $registry = app(ToolRegistry::class);

        foreach ($registry->all() as $tool) {
            expect($tool->name())->toBeString();
            expect($tool->name())->not->toBeEmpty();
            expect(preg_match('/^[a-z_]+$/', $tool->name()))->toBe(1);
        }
    });

    it('all tools have descriptions', function () {
        $registry = app(ToolRegistry::class);

        foreach ($registry->all() as $tool) {
            expect($tool->description())->toBeString();
            expect($tool->description())->not->toBeEmpty();
        }
    });

    it('all tools have parameters', function () {
        $registry = app(ToolRegistry::class);

        foreach ($registry->all() as $tool) {
            expect($tool->hasParameters())->toBeTrue();
            expect($tool->parameters())->toBeArray();
            expect($tool->parameters())->not->toBeEmpty();
        }
    });
});
