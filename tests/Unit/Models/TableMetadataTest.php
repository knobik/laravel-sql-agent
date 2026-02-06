<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Knobik\SqlAgent\Models\TableMetadata;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate');
});

describe('TableMetadata', function () {
    it('can be created', function () {
        $table = TableMetadata::create([
            'connection' => 'default',
            'table_name' => 'users',
            'description' => 'User accounts table',
            'columns' => [
                ['name' => 'id', 'type' => 'bigint', 'description' => 'Primary key'],
                ['name' => 'name', 'type' => 'varchar', 'description' => 'User name'],
            ],
            'relationships' => [
                ['type' => 'hasMany', 'related_table' => 'posts', 'foreign_key' => 'user_id'],
            ],
            'data_quality_notes' => ['Email is always lowercase'],
        ]);

        expect($table->id)->toBeInt();
        expect($table->table_name)->toBe('users');
        expect($table->columns)->toBeArray();
        expect($table->columns)->toHaveCount(2);
        expect($table->relationships)->toBeArray();
        expect($table->data_quality_notes)->toBeArray();
    });

    it('can get column names', function () {
        $table = TableMetadata::create([
            'table_name' => 'users',
            'columns' => [
                ['name' => 'id', 'type' => 'bigint'],
                ['name' => 'name', 'type' => 'varchar'],
                ['name' => 'email', 'type' => 'varchar'],
            ],
        ]);

        expect($table->getColumnNames())->toBe(['id', 'name', 'email']);
    });

    it('can get specific column', function () {
        $table = TableMetadata::create([
            'table_name' => 'users',
            'columns' => [
                ['name' => 'id', 'type' => 'bigint', 'description' => 'Primary key'],
                ['name' => 'name', 'type' => 'varchar', 'description' => 'User name'],
            ],
        ]);

        $column = $table->getColumn('name');
        expect($column)->toBeArray();
        expect($column['type'])->toBe('varchar');
        expect($column['description'])->toBe('User name');
    });

    it('scopes by connection', function () {
        TableMetadata::create(['connection' => 'mysql', 'table_name' => 'users']);
        TableMetadata::create(['connection' => 'pgsql', 'table_name' => 'users']);

        expect(TableMetadata::forConnection('mysql')->count())->toBe(1);
        expect(TableMetadata::forConnection('pgsql')->count())->toBe(1);
    });
});
