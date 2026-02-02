<?php

namespace Knobik\SqlAgent\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'sql-agent:install
                            {--force : Overwrite existing files}';

    protected $description = 'Install the SqlAgent package';

    public function handle(): int
    {
        $this->info('Installing SqlAgent...');

        // Publish config
        $this->call('vendor:publish', [
            '--tag' => 'sql-agent-config',
            '--force' => $this->option('force'),
        ]);

        // Publish migrations
        $this->call('vendor:publish', [
            '--tag' => 'sql-agent-migrations',
            '--force' => $this->option('force'),
        ]);

        // Ask to run migrations
        if ($this->confirm('Run migrations now?', true)) {
            $this->call('migrate');
        }

        // Create knowledge directory
        $knowledgePath = resource_path('sql-agent/knowledge');
        if (! is_dir($knowledgePath)) {
            mkdir($knowledgePath, 0755, true);
            mkdir($knowledgePath.'/tables', 0755, true);
            mkdir($knowledgePath.'/queries', 0755, true);
            mkdir($knowledgePath.'/business', 0755, true);
            $this->info("Created knowledge directory at: {$knowledgePath}");
        }

        $this->info('SqlAgent installed successfully!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('  1. Set your LLM API key in .env (OPENAI_API_KEY or ANTHROPIC_API_KEY)');
        $this->line('  2. Configure config/sql-agent.php');
        $this->line('  3. Add table metadata to resources/sql-agent/knowledge/tables/');
        $this->line('  4. Run: php artisan sql-agent:load-knowledge');

        return self::SUCCESS;
    }
}
