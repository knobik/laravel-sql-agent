<?php

use Knobik\SqlAgent\Agent\FallbackResponseGenerator;

beforeEach(function () {
    $this->generator = new FallbackResponseGenerator;
});

it('returns null results message', function () {
    $result = $this->generator->generate(null);

    expect($result)->toBe('The query was executed but returned no results.');
});

it('returns empty results message', function () {
    $result = $this->generator->generate([]);

    expect($result)->toBe('The query was executed successfully but returned no results.');
});

it('returns single value result', function () {
    $result = $this->generator->generate([['total_count' => 42]]);

    expect($result)->toBe('The result is **42** (total count).');
});

it('returns single row with multiple columns', function () {
    $result = $this->generator->generate([
        ['name' => 'John', 'age' => 30],
    ]);

    expect($result)->toBe('Here is the result: **name**: John, **age**: 30');
});

it('returns row count for multiple rows', function () {
    $result = $this->generator->generate([
        ['id' => 1, 'name' => 'John'],
        ['id' => 2, 'name' => 'Jane'],
        ['id' => 3, 'name' => 'Bob'],
    ]);

    expect($result)->toBe('The query returned **3** rows.');
});

it('replaces underscores with spaces in column names', function () {
    $result = $this->generator->generate([['created_at' => '2024-01-01']]);

    expect($result)->toContain('created at');
});
