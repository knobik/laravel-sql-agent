<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Services;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Knobik\SqlAgent\Data\ColumnInfo;
use Knobik\SqlAgent\Data\RelationshipInfo;
use Knobik\SqlAgent\Data\TableSchema;

class SchemaIntrospector
{
    /**
     * Get the schema for all tables in the connection.
     *
     * @return Collection<int, TableSchema>
     */
    public function getAllTables(?string $connection = null): Collection
    {
        $connection = $connection ?? config('sql-agent.database.connection');
        $schemaManager = $this->getSchemaManager($connection);

        try {
            $tableNames = $schemaManager->listTableNames();
        } catch (Exception $e) {
            report($e);

            return collect();
        }

        return collect($tableNames)
            ->map(fn (string $tableName) => $this->introspectTable($tableName, $connection))
            ->filter();
    }

    /**
     * Introspect a single table.
     */
    public function introspectTable(string $tableName, ?string $connection = null): ?TableSchema
    {
        $connection = $connection ?? config('sql-agent.database.connection');
        $schemaManager = $this->getSchemaManager($connection);

        try {
            $table = $schemaManager->introspectTable($tableName);

            return $this->tableToSchema($table, $schemaManager);
        } catch (Exception $e) {
            report($e);

            return null;
        }
    }

    /**
     * Get relevant schema for a question by extracting potential table names.
     */
    public function getRelevantSchema(string $question, ?string $connection = null): ?string
    {
        $connection = $connection ?? config('sql-agent.database.connection');

        // Extract potential table names from the question
        $potentialTables = $this->extractPotentialTableNames($question, $connection);

        if (empty($potentialTables)) {
            return null;
        }

        $schemas = collect($potentialTables)
            ->map(fn (string $tableName) => $this->introspectTable($tableName, $connection))
            ->filter()
            ->map(fn (TableSchema $schema) => $schema->toPromptString());

        if ($schemas->isEmpty()) {
            return null;
        }

        return $schemas->implode("\n\n---\n\n");
    }

    /**
     * Convert a Doctrine Table to a TableSchema DTO.
     */
    protected function tableToSchema(Table $table, AbstractSchemaManager $schemaManager): TableSchema
    {
        $primaryKey = $table->getPrimaryKey();
        $primaryKeyColumns = $primaryKey ? $primaryKey->getColumns() : [];

        $columns = collect($table->getColumns())
            ->map(fn (Column $column) => new ColumnInfo(
                name: $column->getName(),
                type: $column->getType()->getName(),
                description: $column->getComment(),
                nullable: ! $column->getNotnull(),
                isPrimaryKey: in_array($column->getName(), $primaryKeyColumns),
                isForeignKey: $this->isForeignKey($column->getName(), $table),
                foreignTable: $this->getForeignTable($column->getName(), $table),
                foreignColumn: $this->getForeignColumn($column->getName(), $table),
                defaultValue: $this->getDefaultValue($column),
            ));

        $relationships = collect($table->getForeignKeys())
            ->map(fn (ForeignKeyConstraint $fk) => new RelationshipInfo(
                type: 'belongsTo',
                relatedTable: $fk->getForeignTableName(),
                foreignKey: $fk->getLocalColumns()[0] ?? '',
                localKey: $fk->getForeignColumns()[0] ?? 'id',
            ));

        return new TableSchema(
            tableName: $table->getName(),
            description: $table->getComment(),
            columns: $columns,
            relationships: $relationships,
        );
    }

    /**
     * Check if a column is a foreign key.
     */
    protected function isForeignKey(string $columnName, Table $table): bool
    {
        foreach ($table->getForeignKeys() as $fk) {
            if (in_array($columnName, $fk->getLocalColumns())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the foreign table for a column.
     */
    protected function getForeignTable(string $columnName, Table $table): ?string
    {
        foreach ($table->getForeignKeys() as $fk) {
            if (in_array($columnName, $fk->getLocalColumns())) {
                return $fk->getForeignTableName();
            }
        }

        return null;
    }

    /**
     * Get the foreign column for a column.
     */
    protected function getForeignColumn(string $columnName, Table $table): ?string
    {
        foreach ($table->getForeignKeys() as $fk) {
            if (in_array($columnName, $fk->getLocalColumns())) {
                return $fk->getForeignColumns()[0] ?? null;
            }
        }

        return null;
    }

    /**
     * Get the default value for a column.
     */
    protected function getDefaultValue(Column $column): ?string
    {
        $default = $column->getDefault();

        if ($default === null) {
            return null;
        }

        return (string) $default;
    }

    /**
     * Get the schema manager for a connection.
     */
    protected function getSchemaManager(?string $connection = null): AbstractSchemaManager
    {
        /** @var Connection $dbConnection */
        $dbConnection = DB::connection($connection);

        return $dbConnection->getDoctrineSchemaManager();
    }

    /**
     * Extract potential table names from a question.
     *
     * @return array<string>
     */
    protected function extractPotentialTableNames(string $question, ?string $connection = null): array
    {
        $connection = $connection ?? config('sql-agent.database.connection');
        $schemaManager = $this->getSchemaManager($connection);

        try {
            $allTables = $schemaManager->listTableNames();
        } catch (Exception $e) {
            return [];
        }

        $questionLower = strtolower($question);
        $potentialTables = [];

        foreach ($allTables as $tableName) {
            $tableNameLower = strtolower($tableName);

            // Direct match
            if (str_contains($questionLower, $tableNameLower)) {
                $potentialTables[] = $tableName;
                continue;
            }

            // Singular/plural match
            $singular = rtrim($tableNameLower, 's');
            if (str_contains($questionLower, $singular)) {
                $potentialTables[] = $tableName;
                continue;
            }

            // Common variations
            $variations = [
                str_replace('_', ' ', $tableNameLower),
                str_replace('_', '', $tableNameLower),
            ];

            foreach ($variations as $variation) {
                if (str_contains($questionLower, $variation)) {
                    $potentialTables[] = $tableName;
                    break;
                }
            }
        }

        return array_unique($potentialTables);
    }

    /**
     * Get table names from the database.
     *
     * @return array<string>
     */
    public function getTableNames(?string $connection = null): array
    {
        $connection = $connection ?? config('sql-agent.database.connection');
        $schemaManager = $this->getSchemaManager($connection);

        try {
            return $schemaManager->listTableNames();
        } catch (Exception $e) {
            report($e);

            return [];
        }
    }

    /**
     * Check if a table exists.
     */
    public function tableExists(string $tableName, ?string $connection = null): bool
    {
        $connection = $connection ?? config('sql-agent.database.connection');
        $schemaManager = $this->getSchemaManager($connection);

        try {
            return $schemaManager->tablesExist([$tableName]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get column names for a table.
     *
     * @return array<string>
     */
    public function getColumnNames(string $tableName, ?string $connection = null): array
    {
        $schema = $this->introspectTable($tableName, $connection);

        return $schema ? $schema->getColumnNames() : [];
    }

    /**
     * Format all tables as a prompt string.
     */
    public function format(?string $connection = null): string
    {
        $tables = $this->getAllTables($connection);

        if ($tables->isEmpty()) {
            return 'No tables found in the database.';
        }

        return $tables
            ->map(fn (TableSchema $table) => $table->toPromptString())
            ->implode("\n\n---\n\n");
    }
}
