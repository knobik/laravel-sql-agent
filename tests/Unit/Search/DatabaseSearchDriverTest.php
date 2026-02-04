<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Knobik\SqlAgent\Models\Learning;
use Knobik\SqlAgent\Models\QueryPattern;
use Knobik\SqlAgent\Search\Drivers\DatabaseSearchDriver;
use Knobik\SqlAgent\Search\SearchResult;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->driver = new DatabaseSearchDriver([]);
});

test('can search query patterns index', function () {
    QueryPattern::create([
        'name' => 'User Count Query',
        'question' => 'How many users are in the system?',
        'sql' => 'SELECT COUNT(*) FROM users',
        'summary' => 'Counts all users in the database',
    ]);

    QueryPattern::create([
        'name' => 'Order Total Query',
        'question' => 'What is the total order amount?',
        'sql' => 'SELECT SUM(amount) FROM orders',
        'summary' => 'Calculates total order revenue',
    ]);

    $results = $this->driver->search('users count', 'query_patterns', 5);

    expect($results)->toHaveCount(1);
    expect($results->first())->toBeInstanceOf(SearchResult::class);
    expect($results->first()->model->name)->toBe('User Count Query');
    expect($results->first()->index)->toBe('query_patterns');
});

test('can search learnings index', function () {
    Learning::create([
        'title' => 'Database Performance Tip',
        'description' => 'Always use indexes on frequently queried columns',
        'category' => 'business_logic',
    ]);

    Learning::create([
        'title' => 'SQL Syntax Guide',
        'description' => 'Use LEFT JOIN for optional relationships',
        'category' => 'query_pattern',
    ]);

    $results = $this->driver->search('performance indexes', 'learnings', 5);

    expect($results)->toHaveCount(1);
    expect($results->first()->model->title)->toBe('Database Performance Tip');
    expect($results->first()->index)->toBe('learnings');
});

test('search returns empty collection when no matches', function () {
    QueryPattern::create([
        'name' => 'User Count Query',
        'question' => 'How many users?',
        'sql' => 'SELECT COUNT(*) FROM users',
    ]);

    $results = $this->driver->search('completely unrelated xyz123', 'query_patterns', 5);

    expect($results)->toBeEmpty();
});

test('search respects limit parameter', function () {
    for ($i = 1; $i <= 10; $i++) {
        QueryPattern::create([
            'name' => "User Query {$i}",
            'question' => "How many users number {$i}?",
            'sql' => 'SELECT COUNT(*) FROM users',
            'summary' => 'Users count query',
        ]);
    }

    $results = $this->driver->search('users', 'query_patterns', 3);

    expect($results)->toHaveCount(3);
});

test('searchMultiple searches across multiple indexes', function () {
    QueryPattern::create([
        'name' => 'User Statistics',
        'question' => 'Get user statistics',
        'sql' => 'SELECT * FROM user_stats',
        'summary' => 'User stats query',
    ]);

    Learning::create([
        'title' => 'User Data Best Practices',
        'description' => 'Tips for handling user data efficiently',
        'category' => 'business_logic',
    ]);

    $results = $this->driver->searchMultiple('user statistics', ['query_patterns', 'learnings'], 5);

    expect($results->count())->toBeGreaterThanOrEqual(1);
});

test('throws exception for unknown index', function () {
    expect(fn () => $this->driver->search('test', 'unknown_index', 5))
        ->toThrow(RuntimeException::class, 'Unknown search index: unknown_index');
});

test('get index mapping returns default mapping', function () {
    $mapping = $this->driver->getIndexMapping();

    expect($mapping)->toHaveKey('query_patterns');
    expect($mapping)->toHaveKey('learnings');
    expect($mapping['query_patterns'])->toBe(QueryPattern::class);
    expect($mapping['learnings'])->toBe(Learning::class);
});

test('can configure custom index mapping', function () {
    $driver = new DatabaseSearchDriver([
        'index_mapping' => [
            'custom' => QueryPattern::class,
        ],
    ]);

    $mapping = $driver->getIndexMapping();

    expect($mapping)->toHaveKey('custom');
    expect($mapping['custom'])->toBe(QueryPattern::class);
});

test('search results include score', function () {
    QueryPattern::create([
        'name' => 'User Count Query',
        'question' => 'How many users are there?',
        'sql' => 'SELECT COUNT(*) FROM users',
        'summary' => 'Count users in database',
    ]);

    $results = $this->driver->search('users count', 'query_patterns', 5);

    expect($results->first()->score)->toBeGreaterThanOrEqual(0);
});

test('index method is no-op for database driver', function () {
    $pattern = QueryPattern::create([
        'name' => 'Test',
        'question' => 'Test question',
        'sql' => 'SELECT 1',
    ]);

    // Should not throw
    $this->driver->index($pattern);

    expect(true)->toBeTrue();
});

test('delete method is no-op for database driver', function () {
    $pattern = QueryPattern::create([
        'name' => 'Test',
        'question' => 'Test question',
        'sql' => 'SELECT 1',
    ]);

    // Should not throw
    $this->driver->delete($pattern);

    expect(true)->toBeTrue();
});
