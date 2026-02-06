<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Knobik\SqlAgent\Enums\LearningCategory;
use Knobik\SqlAgent\Models\Learning;
use Knobik\SqlAgent\Services\LearningImportExport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate');
    $this->importExport = app(LearningImportExport::class);
});

describe('export', function () {
    it('exports all learnings', function () {
        Learning::create([
            'title' => 'Test Learning',
            'description' => 'A test description',
            'category' => LearningCategory::SchemaFix,
            'sql' => 'SELECT 1',
            'metadata' => [],
        ]);

        $result = $this->importExport->export();

        expect($result)->toHaveCount(1);
        expect($result[0]['title'])->toBe('Test Learning');
        expect($result[0]['category'])->toBe('schema_fix');
        expect($result[0])->toHaveKeys(['title', 'description', 'category', 'sql', 'metadata', 'created_at']);
    });

    it('filters by category', function () {
        Learning::create([
            'title' => 'Schema Fix',
            'description' => 'desc',
            'category' => LearningCategory::SchemaFix,
        ]);
        Learning::create([
            'title' => 'Query Pattern',
            'description' => 'desc',
            'category' => LearningCategory::QueryPattern,
        ]);

        $result = $this->importExport->export(LearningCategory::SchemaFix);

        expect($result)->toHaveCount(1);
        expect($result[0]['title'])->toBe('Schema Fix');
    });

    it('returns empty array when no learnings exist', function () {
        $result = $this->importExport->export();

        expect($result)->toBeEmpty();
    });
});

describe('import', function () {
    it('imports learnings from array', function () {
        $data = [
            [
                'title' => 'Imported Learning',
                'description' => 'Imported description',
                'category' => 'schema_fix',
                'sql' => 'SELECT 1',
                'metadata' => [],
            ],
        ];

        $count = $this->importExport->import($data);

        expect($count)->toBe(1);
        expect(Learning::count())->toBe(1);
        expect(Learning::first()->metadata)->toHaveKey('imported_at');
    });

    it('skips duplicates by default', function () {
        Learning::create([
            'title' => 'Existing',
            'description' => 'Already exists',
            'category' => LearningCategory::SchemaFix,
        ]);

        $data = [
            [
                'title' => 'Existing',
                'description' => 'Different desc',
                'category' => 'schema_fix',
            ],
            [
                'title' => 'New One',
                'description' => 'Brand new',
                'category' => 'query_pattern',
            ],
        ];

        $count = $this->importExport->import($data, skipDuplicates: true);

        expect($count)->toBe(1);
        expect(Learning::count())->toBe(2);
    });

    it('imports all when skipDuplicates is false', function () {
        Learning::create([
            'title' => 'Existing',
            'description' => 'Already exists',
            'category' => LearningCategory::SchemaFix,
        ]);

        $data = [
            [
                'title' => 'Existing',
                'description' => 'Duplicate allowed',
                'category' => 'schema_fix',
            ],
        ];

        $count = $this->importExport->import($data, skipDuplicates: false);

        expect($count)->toBe(1);
        expect(Learning::count())->toBe(2);
    });
});

describe('isDuplicate', function () {
    it('detects title duplicates', function () {
        Learning::create([
            'title' => 'My Learning',
            'description' => 'desc',
            'category' => LearningCategory::SchemaFix,
        ]);

        expect($this->importExport->isDuplicate(['title' => 'My Learning']))->toBeTrue();
        expect($this->importExport->isDuplicate(['title' => 'Other Title']))->toBeFalse();
    });
});
