<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurgeCommand extends Command
{
    protected $signature = 'sql-agent:purge
                            {--conversations : Only purge conversations and messages}
                            {--learnings : Only purge learnings}
                            {--knowledge : Only purge knowledge (query patterns, table metadata, business rules)}
                            {--all : Purge everything (default if no options specified)}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Purge SQL Agent data from the database';

    protected array $tables = [
        'conversations' => ['sql_agent_messages', 'sql_agent_conversations'],
        'learnings' => ['sql_agent_learnings'],
        'knowledge' => ['sql_agent_query_patterns', 'sql_agent_table_metadata', 'sql_agent_business_rules'],
        'evaluations' => ['sql_agent_test_cases'],
    ];

    public function handle(): int
    {
        $connection = config('sql-agent.database.storage_connection');
        $tablesToPurge = $this->getTablesToPurge();

        // Filter to only existing tables
        $tablesToPurge = array_filter($tablesToPurge, function ($table) use ($connection) {
            return Schema::connection($connection)->hasTable($table);
        });

        if (empty($tablesToPurge)) {
            $this->warn('No tables found to purge.');

            return self::SUCCESS;
        }

        $this->info('The following tables will be truncated:');
        foreach ($tablesToPurge as $table) {
            $this->line("  - {$table}");
        }

        if (! $this->option('force') && ! $this->confirm('Are you sure you want to purge this data? This cannot be undone.')) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        // Disable foreign key checks for truncation
        $this->disableForeignKeyChecks($connection);

        try {
            foreach ($tablesToPurge as $table) {
                DB::connection($connection)->table($table)->truncate();
                $this->info("Truncated: {$table}");
            }
        } finally {
            $this->enableForeignKeyChecks($connection);
        }

        $this->purgeEmbeddings($tablesToPurge);

        $this->newLine();
        $this->info('Purge completed successfully.');

        return self::SUCCESS;
    }

    protected function getTablesToPurge(): array
    {
        $tables = [];

        $conversations = $this->option('conversations');
        $learnings = $this->option('learnings');
        $knowledge = $this->option('knowledge');
        $all = $this->option('all');

        // If no specific options, default to all
        if (! $conversations && ! $learnings && ! $knowledge && ! $all) {
            $all = true;
        }

        if ($all || $conversations) {
            $tables = array_merge($tables, $this->tables['conversations']);
        }

        if ($all || $learnings) {
            $tables = array_merge($tables, $this->tables['learnings']);
        }

        if ($all || $knowledge) {
            $tables = array_merge($tables, $this->tables['knowledge']);
        }

        if ($all) {
            $tables = array_merge($tables, $this->tables['evaluations']);
        }

        return array_unique($tables);
    }

    /**
     * Purge embeddings from the pgvector connection when relevant tables are being purged.
     *
     * @param  array<string>  $purgedTables
     */
    protected function purgeEmbeddings(array $purgedTables): void
    {
        $embeddingsConnection = config('sql-agent.embeddings.connection');

        if (! $embeddingsConnection) {
            return;
        }

        $embeddingsTable = 'sql_agent_embeddings';

        if (! Schema::connection($embeddingsConnection)->hasTable($embeddingsTable)) {
            return;
        }

        // Determine which embeddable tables were purged
        $embeddableTables = array_intersect($purgedTables, array_merge(
            $this->tables['learnings'],
            $this->tables['knowledge'],
        ));

        if (empty($embeddableTables)) {
            return;
        }

        DB::connection($embeddingsConnection)->table($embeddingsTable)->truncate();
        $this->info("Truncated: {$embeddingsTable} (on {$embeddingsConnection} connection)");
    }

    protected function disableForeignKeyChecks(?string $connection): void
    {
        $driver = DB::connection($connection)->getDriverName();

        match ($driver) {
            'mysql' => DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=0'),
            'sqlite' => DB::connection($connection)->statement('PRAGMA foreign_keys = OFF'),
            'pgsql' => null, // PostgreSQL handles this per-transaction
            'sqlsrv' => null, // SQL Server requires different approach per table
            default => null,
        };
    }

    protected function enableForeignKeyChecks(?string $connection): void
    {
        $driver = DB::connection($connection)->getDriverName();

        match ($driver) {
            'mysql' => DB::connection($connection)->statement('SET FOREIGN_KEY_CHECKS=1'),
            'sqlite' => DB::connection($connection)->statement('PRAGMA foreign_keys = ON'),
            'pgsql' => null,
            'sqlsrv' => null,
            default => null,
        };
    }
}
