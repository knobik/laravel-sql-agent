<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Knobik\SqlAgent\Enums\LearningCategory;
use Knobik\SqlAgent\Models\Learning;
use Knobik\SqlAgent\Tools\SaveLearningTool;
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

describe('SaveLearningTool', function () {
    it('extends Prism Tool', function () {
        $tool = new SaveLearningTool;

        expect($tool)->toBeInstanceOf(Tool::class);
    });

    it('saves a learning', function () {
        $tool = new SaveLearningTool;

        $result = json_decode($tool(
            title: 'Test Learning',
            description: 'This is a test learning about the database.',
            category: LearningCategory::SchemaFix->value,
        ), true);

        expect($result['success'])->toBeTrue();
        expect($result['learning_id'])->toBeInt();

        $learning = Learning::find($result['learning_id']);
        expect($learning->title)->toBe('Test Learning');
    });

    it('saves a learning with SQL', function () {
        $tool = new SaveLearningTool;

        $result = json_decode($tool(
            title: 'SQL Pattern',
            description: 'How to count users correctly.',
            category: LearningCategory::QueryPattern->value,
            sql: 'SELECT COUNT(*) FROM users WHERE active = 1',
        ), true);

        expect($result['success'])->toBeTrue();

        $learning = Learning::find($result['learning_id']);
        expect($learning->sql)->toBe('SELECT COUNT(*) FROM users WHERE active = 1');
    });

    it('requires title', function () {
        $tool = new SaveLearningTool;

        expect(fn () => $tool(
            title: '',
            description: 'Test description',
            category: LearningCategory::SchemaFix->value,
        ))->toThrow(RuntimeException::class, 'Title is required');
    });

    it('requires description', function () {
        $tool = new SaveLearningTool;

        expect(fn () => $tool(
            title: 'Test',
            description: '',
            category: LearningCategory::SchemaFix->value,
        ))->toThrow(RuntimeException::class, 'Description is required');
    });

    it('requires valid category', function () {
        $tool = new SaveLearningTool;

        expect(fn () => $tool(
            title: 'Test',
            description: 'Test description',
            category: 'invalid_category',
        ))->toThrow(RuntimeException::class, 'Invalid category');
    });

    it('validates title length', function () {
        $tool = new SaveLearningTool;

        expect(fn () => $tool(
            title: str_repeat('a', 101),
            description: 'Test description',
            category: LearningCategory::SchemaFix->value,
        ))->toThrow(RuntimeException::class, '100 characters');
    });

    it('has correct name', function () {
        $tool = new SaveLearningTool;

        expect($tool->name())->toBe('save_learning');
    });

    it('respects disabled learning config', function () {
        config(['sql-agent.learning.enabled' => false]);

        $tool = new SaveLearningTool;

        expect(fn () => $tool(
            title: 'Test',
            description: 'Test description',
            category: LearningCategory::SchemaFix->value,
        ))->toThrow(RuntimeException::class, 'disabled');

        config(['sql-agent.learning.enabled' => true]);
    });
});
