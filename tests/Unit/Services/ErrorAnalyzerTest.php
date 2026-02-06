<?php

use Knobik\SqlAgent\Enums\LearningCategory;
use Knobik\SqlAgent\Services\ErrorAnalyzer;

beforeEach(function () {
    $this->analyzer = new ErrorAnalyzer;
});

describe('categorize', function () {
    it('categorizes schema errors', function () {
        expect($this->analyzer->categorize("Unknown column 'foo'"))->toBe(LearningCategory::SchemaFix);
        expect($this->analyzer->categorize("Table 'users' not found"))->toBe(LearningCategory::SchemaFix);
        expect($this->analyzer->categorize('no such table: orders'))->toBe(LearningCategory::SchemaFix);
        expect($this->analyzer->categorize("relation 'products' does not exist"))->toBe(LearningCategory::SchemaFix);
        expect($this->analyzer->categorize('undefined column: name'))->toBe(LearningCategory::SchemaFix);
    });

    it('categorizes type errors', function () {
        expect($this->analyzer->categorize('type mismatch in expression'))->toBe(LearningCategory::TypeError);
        expect($this->analyzer->categorize('cannot convert value'))->toBe(LearningCategory::TypeError);
        expect($this->analyzer->categorize('invalid input syntax for integer'))->toBe(LearningCategory::TypeError);
        expect($this->analyzer->categorize('conversion failed'))->toBe(LearningCategory::TypeError);
    });

    it('categorizes query pattern errors', function () {
        expect($this->analyzer->categorize('syntax error near SELECT'))->toBe(LearningCategory::QueryPattern);
        expect($this->analyzer->categorize('unexpected token'))->toBe(LearningCategory::QueryPattern);
        expect($this->analyzer->categorize('parse error'))->toBe(LearningCategory::QueryPattern);
    });

    it('categorizes data quality errors', function () {
        expect($this->analyzer->categorize('data truncated'))->toBe(LearningCategory::DataQuality);
        expect($this->analyzer->categorize('out of range value'))->toBe(LearningCategory::DataQuality);
        expect($this->analyzer->categorize('duplicate key violation'))->toBe(LearningCategory::DataQuality);
        expect($this->analyzer->categorize('division by zero'))->toBe(LearningCategory::DataQuality);
    });

    it('defaults to business logic for unknown errors', function () {
        expect($this->analyzer->categorize('something went wrong'))->toBe(LearningCategory::BusinessLogic);
    });
});

describe('generateTitle', function () {
    it('strips SQLSTATE codes', function () {
        $title = $this->analyzer->generateTitle("SQLSTATE[42S02] Table 'users' doesn't exist");

        expect($title)->not->toContain('SQLSTATE');
        expect($title)->toContain("Table 'users' doesn't exist");
    });

    it('strips driver prefixes', function () {
        $title = $this->analyzer->generateTitle('[HY000] [1045] Access denied');

        expect($title)->not->toContain('[HY000]');
        expect($title)->not->toContain('[1045]');
        expect($title)->toContain('Access denied');
    });

    it('truncates long messages', function () {
        $longError = str_repeat('error ', 30);
        $title = $this->analyzer->generateTitle($longError);

        expect(mb_strlen($title))->toBeLessThanOrEqual(100);
    });

    it('returns SQL Error for empty message', function () {
        expect($this->analyzer->generateTitle(''))->toBe('SQL Error');
    });
});

describe('analyze', function () {
    it('returns structured analysis', function () {
        $result = $this->analyzer->analyze(
            'SELECT * FROM users WHERE active = 1',
            "Unknown column 'active'"
        );

        expect($result)->toHaveKeys(['category', 'title', 'description', 'tables']);
        expect($result['category'])->toBe(LearningCategory::SchemaFix);
        expect($result['tables'])->toContain('users');
    });
});

describe('extractTableNames', function () {
    it('extracts tables from SQL', function () {
        $tables = $this->analyzer->extractTableNames('SELECT u.name FROM users u JOIN orders o ON u.id = o.user_id');

        expect($tables)->toContain('users');
        expect($tables)->toContain('orders');
    });
});

describe('extractColumnName', function () {
    it('extracts column from error message', function () {
        expect($this->analyzer->extractColumnName("Unknown column 'email'"))->toBe('email');
        expect($this->analyzer->extractColumnName('Some other error'))->toBeNull();
    });
});

describe('extractTableNameFromError', function () {
    it('extracts table from error message', function () {
        expect($this->analyzer->extractTableNameFromError("Table 'mydb.users' doesn't exist"))->toBe('users');
        expect($this->analyzer->extractTableNameFromError('Some other error'))->toBeNull();
    });
});
