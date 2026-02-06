<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Knobik\SqlAgent\Models\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate');
});

describe('TestCase', function () {
    it('can be created', function () {
        $testCase = TestCase::create([
            'name' => 'user_count_test',
            'question' => 'How many users?',
            'expected_values' => ['count' => 10],
            'golden_sql' => 'SELECT COUNT(*) as count FROM users',
        ]);

        expect($testCase->name)->toBe('user_count_test');
        expect($testCase->hasGoldenSql())->toBeTrue();
        expect($testCase->hasExpectedValues())->toBeTrue();
    });

    it('can match expected values', function () {
        $testCase = TestCase::create([
            'name' => 'test',
            'question' => 'Test?',
            'expected_values' => ['count' => 10, 'name' => 'John'],
        ]);

        expect($testCase->matchesExpectedValues(['count' => 10, 'name' => 'John']))->toBeTrue();
        expect($testCase->matchesExpectedValues(['count' => 5, 'name' => 'John']))->toBeFalse();
    });
});
