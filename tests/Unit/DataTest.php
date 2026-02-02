<?php

use Knobik\SqlAgent\Data\BusinessRuleData;
use Knobik\SqlAgent\Data\ColumnInfo;
use Knobik\SqlAgent\Data\Context;
use Knobik\SqlAgent\Data\QueryPatternData;
use Knobik\SqlAgent\Data\RelationshipInfo;
use Knobik\SqlAgent\Data\TableSchema;
use Knobik\SqlAgent\Enums\BusinessRuleType;

describe('ColumnInfo', function () {
    it('can be created', function () {
        $column = new ColumnInfo(
            name: 'id',
            type: 'bigint',
            description: 'Primary key',
            nullable: false,
            isPrimaryKey: true,
        );

        expect($column->name)->toBe('id');
        expect($column->type)->toBe('bigint');
        expect($column->isPrimaryKey)->toBeTrue();
        expect($column->nullable)->toBeFalse();
    });

    it('generates prompt string', function () {
        $column = new ColumnInfo(
            name: 'id',
            type: 'bigint',
            description: 'Primary key',
            isPrimaryKey: true,
            nullable: false,
        );

        $prompt = $column->toPromptString();

        expect($prompt)->toContain('id');
        expect($prompt)->toContain('bigint');
        expect($prompt)->toContain('[PK]');
        expect($prompt)->toContain('NOT NULL');
    });

    it('includes foreign key info in prompt', function () {
        $column = new ColumnInfo(
            name: 'user_id',
            type: 'bigint',
            isForeignKey: true,
            foreignTable: 'users',
            foreignColumn: 'id',
        );

        $prompt = $column->toPromptString();

        expect($prompt)->toContain('[FK -> users.id]');
    });
});

describe('RelationshipInfo', function () {
    it('can be created', function () {
        $rel = new RelationshipInfo(
            type: 'hasMany',
            relatedTable: 'posts',
            foreignKey: 'user_id',
        );

        expect($rel->type)->toBe('hasMany');
        expect($rel->relatedTable)->toBe('posts');
        expect($rel->isHasMany())->toBeTrue();
    });

    it('generates prompt string for hasMany', function () {
        $rel = new RelationshipInfo(
            type: 'hasMany',
            relatedTable: 'posts',
            foreignKey: 'user_id',
        );

        expect($rel->toPromptString())->toContain('hasMany posts');
    });

    it('generates prompt string for belongsTo', function () {
        $rel = new RelationshipInfo(
            type: 'belongsTo',
            relatedTable: 'users',
            foreignKey: 'user_id',
        );

        expect($rel->toPromptString())->toContain('belongsTo users');
    });
});

describe('TableSchema', function () {
    it('can be created', function () {
        $schema = new TableSchema(
            tableName: 'users',
            description: 'User accounts',
            columns: collect([
                new ColumnInfo(name: 'id', type: 'bigint'),
                new ColumnInfo(name: 'name', type: 'varchar'),
            ]),
            relationships: collect([
                new RelationshipInfo(type: 'hasMany', relatedTable: 'posts', foreignKey: 'user_id'),
            ]),
        );

        expect($schema->tableName)->toBe('users');
        expect($schema->columns)->toHaveCount(2);
        expect($schema->relationships)->toHaveCount(1);
    });

    it('generates prompt string', function () {
        $schema = new TableSchema(
            tableName: 'users',
            description: 'User accounts',
            columns: collect([
                new ColumnInfo(name: 'id', type: 'bigint', isPrimaryKey: true),
            ]),
            dataQualityNotes: ['Email is lowercase'],
        );

        $prompt = $schema->toPromptString();

        expect($prompt)->toContain('## Table: users');
        expect($prompt)->toContain('User accounts');
        expect($prompt)->toContain('### Columns:');
        expect($prompt)->toContain('### Data Quality Notes:');
        expect($prompt)->toContain('Email is lowercase');
    });

    it('can get column names', function () {
        $schema = new TableSchema(
            tableName: 'users',
            columns: collect([
                new ColumnInfo(name: 'id', type: 'bigint'),
                new ColumnInfo(name: 'name', type: 'varchar'),
            ]),
        );

        expect($schema->getColumnNames())->toBe(['id', 'name']);
    });

    it('can check if column exists', function () {
        $schema = new TableSchema(
            tableName: 'users',
            columns: collect([
                new ColumnInfo(name: 'id', type: 'bigint'),
            ]),
        );

        expect($schema->hasColumn('id'))->toBeTrue();
        expect($schema->hasColumn('email'))->toBeFalse();
    });
});

describe('BusinessRuleData', function () {
    it('can be created', function () {
        $rule = new BusinessRuleData(
            name: 'Active User',
            description: 'Logged in within 30 days',
            type: BusinessRuleType::Metric,
            calculation: 'WHERE last_login > NOW() - INTERVAL 30 DAY',
        );

        expect($rule->name)->toBe('Active User');
        expect($rule->isMetric())->toBeTrue();
    });

    it('generates prompt string for metric', function () {
        $rule = new BusinessRuleData(
            name: 'Active User',
            description: 'Logged in within 30 days',
            type: BusinessRuleType::Metric,
            table: 'users',
            calculation: 'WHERE last_login > NOW()',
        );

        $prompt = $rule->toPromptString();

        expect($prompt)->toContain('Active User');
        expect($prompt)->toContain('Table: users');
        expect($prompt)->toContain('Calculation:');
    });

    it('generates prompt string for gotcha', function () {
        $rule = new BusinessRuleData(
            name: 'Soft deletes',
            description: 'Users are soft deleted',
            type: BusinessRuleType::Gotcha,
            tablesAffected: ['users'],
            solution: 'Add WHERE deleted_at IS NULL',
        );

        $prompt = $rule->toPromptString();

        expect($prompt)->toContain('Soft deletes');
        expect($prompt)->toContain('Affected tables:');
        expect($prompt)->toContain('Solution:');
    });
});

describe('QueryPatternData', function () {
    it('can be created', function () {
        $pattern = new QueryPatternData(
            name: 'active_users',
            question: 'How many active users?',
            sql: 'SELECT COUNT(*) FROM users',
            summary: 'Count active users',
            tablesUsed: ['users'],
        );

        expect($pattern->name)->toBe('active_users');
        expect($pattern->tablesUsed)->toBe(['users']);
    });

    it('generates prompt string', function () {
        $pattern = new QueryPatternData(
            name: 'active_users',
            question: 'How many active users?',
            sql: 'SELECT COUNT(*) FROM users',
            summary: 'Count active users',
            tablesUsed: ['users'],
        );

        $prompt = $pattern->toPromptString();

        expect($prompt)->toContain('### active_users');
        expect($prompt)->toContain('**Question:**');
        expect($prompt)->toContain('```sql');
        expect($prompt)->toContain('Tables used: users');
    });

    it('can check if uses table', function () {
        $pattern = new QueryPatternData(
            name: 'test',
            question: 'Test',
            sql: 'SELECT',
            tablesUsed: ['users', 'posts'],
        );

        expect($pattern->usesTable('users'))->toBeTrue();
        expect($pattern->usesTable('comments'))->toBeFalse();
    });
});

describe('Context', function () {
    it('can be created', function () {
        $context = new Context(
            semanticModel: 'Table info',
            businessRules: 'Rules info',
            queryPatterns: collect(),
            learnings: collect(),
            runtimeSchema: null,
        );

        expect($context->semanticModel)->toBe('Table info');
        expect($context->businessRules)->toBe('Rules info');
    });

    it('generates prompt string', function () {
        $pattern = new QueryPatternData(
            name: 'test',
            question: 'Test?',
            sql: 'SELECT 1',
        );

        $context = new Context(
            semanticModel: 'Schema info here',
            businessRules: 'Rules here',
            queryPatterns: collect([$pattern]),
            learnings: collect([['title' => 'Learning 1', 'description' => 'Desc 1']]),
            runtimeSchema: 'Runtime schema',
        );

        $prompt = $context->toPromptString();

        expect($prompt)->toContain('# DATABASE SCHEMA');
        expect($prompt)->toContain('Schema info here');
        expect($prompt)->toContain('# BUSINESS RULES');
        expect($prompt)->toContain('# SIMILAR QUERY EXAMPLES');
        expect($prompt)->toContain('# RELEVANT LEARNINGS');
        expect($prompt)->toContain('# RUNTIME SCHEMA INSPECTION');
    });

    it('detects empty context', function () {
        $emptyContext = new Context(
            semanticModel: '',
            businessRules: '',
            queryPatterns: collect(),
            learnings: collect(),
            runtimeSchema: null,
        );

        expect($emptyContext->isEmpty())->toBeTrue();

        $nonEmptyContext = new Context(
            semanticModel: 'Some schema',
            businessRules: '',
            queryPatterns: collect(),
            learnings: collect(),
            runtimeSchema: null,
        );

        expect($nonEmptyContext->isEmpty())->toBeFalse();
    });

    it('counts patterns and learnings', function () {
        $context = new Context(
            semanticModel: '',
            businessRules: '',
            queryPatterns: collect([
                new QueryPatternData(name: 'p1', question: 'q1', sql: 's1'),
                new QueryPatternData(name: 'p2', question: 'q2', sql: 's2'),
            ]),
            learnings: collect([
                ['title' => 'L1', 'description' => 'D1'],
            ]),
            runtimeSchema: null,
        );

        expect($context->getQueryPatternCount())->toBe(2);
        expect($context->getLearningCount())->toBe(1);
    });
});
