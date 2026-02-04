<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add full-text indexes for PostgreSQL and SQL Server.
 * MySQL indexes are already created in the original table migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driverName = Schema::connection($this->getConnection())->getConnection()->getDriverName();

        match ($driverName) {
            'pgsql' => $this->createPostgresIndexes(),
            'sqlsrv' => $this->createSqlServerIndexes(),
            default => null, // MySQL handled in original migrations, SQLite uses LIKE
        };
    }

    public function down(): void
    {
        $driverName = Schema::connection($this->getConnection())->getConnection()->getDriverName();

        match ($driverName) {
            'pgsql' => $this->dropPostgresIndexes(),
            'sqlsrv' => $this->dropSqlServerIndexes(),
            default => null,
        };
    }

    /**
     * Create PostgreSQL GIN indexes for full-text search.
     */
    protected function createPostgresIndexes(): void
    {
        $connection = $this->getConnection();

        // Query Patterns - create GIN index on tsvector
        DB::connection($connection)->statement("
            CREATE INDEX IF NOT EXISTS sql_agent_query_patterns_fulltext_idx
            ON sql_agent_query_patterns
            USING GIN (to_tsvector('english', coalesce(name, '') || ' ' || coalesce(question, '') || ' ' || coalesce(summary, '')))
        ");

        // Learnings - create GIN index on tsvector
        DB::connection($connection)->statement("
            CREATE INDEX IF NOT EXISTS sql_agent_learnings_fulltext_idx
            ON sql_agent_learnings
            USING GIN (to_tsvector('english', coalesce(title, '') || ' ' || coalesce(description, '')))
        ");
    }

    /**
     * Drop PostgreSQL GIN indexes.
     */
    protected function dropPostgresIndexes(): void
    {
        $connection = $this->getConnection();

        DB::connection($connection)->statement('DROP INDEX IF EXISTS sql_agent_query_patterns_fulltext_idx');
        DB::connection($connection)->statement('DROP INDEX IF EXISTS sql_agent_learnings_fulltext_idx');
    }

    /**
     * Create SQL Server full-text catalog and indexes.
     */
    protected function createSqlServerIndexes(): void
    {
        $connection = $this->getConnection();

        // Create full-text catalog if not exists
        DB::connection($connection)->statement("
            IF NOT EXISTS (SELECT 1 FROM sys.fulltext_catalogs WHERE name = 'sql_agent_catalog')
            BEGIN
                CREATE FULLTEXT CATALOG sql_agent_catalog AS DEFAULT
            END
        ");

        // Create full-text index on query_patterns
        // SQL Server requires a unique index for full-text
        DB::connection($connection)->statement("
            IF NOT EXISTS (SELECT 1 FROM sys.fulltext_indexes WHERE object_id = OBJECT_ID('sql_agent_query_patterns'))
            BEGIN
                CREATE FULLTEXT INDEX ON sql_agent_query_patterns(name, question, summary)
                KEY INDEX PK_sql_agent_query_patterns
                ON sql_agent_catalog
                WITH CHANGE_TRACKING AUTO
            END
        ");

        // Create full-text index on learnings
        DB::connection($connection)->statement("
            IF NOT EXISTS (SELECT 1 FROM sys.fulltext_indexes WHERE object_id = OBJECT_ID('sql_agent_learnings'))
            BEGIN
                CREATE FULLTEXT INDEX ON sql_agent_learnings(title, description)
                KEY INDEX PK_sql_agent_learnings
                ON sql_agent_catalog
                WITH CHANGE_TRACKING AUTO
            END
        ");
    }

    /**
     * Drop SQL Server full-text indexes and catalog.
     */
    protected function dropSqlServerIndexes(): void
    {
        $connection = $this->getConnection();

        // Drop full-text indexes
        DB::connection($connection)->statement("
            IF EXISTS (SELECT 1 FROM sys.fulltext_indexes WHERE object_id = OBJECT_ID('sql_agent_query_patterns'))
            BEGIN
                DROP FULLTEXT INDEX ON sql_agent_query_patterns
            END
        ");

        DB::connection($connection)->statement("
            IF EXISTS (SELECT 1 FROM sys.fulltext_indexes WHERE object_id = OBJECT_ID('sql_agent_learnings'))
            BEGIN
                DROP FULLTEXT INDEX ON sql_agent_learnings
            END
        ");

        // Drop catalog
        DB::connection($connection)->statement("
            IF EXISTS (SELECT 1 FROM sys.fulltext_catalogs WHERE name = 'sql_agent_catalog')
            BEGIN
                DROP FULLTEXT CATALOG sql_agent_catalog
            END
        ");
    }

    public function getConnection(): ?string
    {
        return config('sql-agent.database.storage_connection');
    }
};
