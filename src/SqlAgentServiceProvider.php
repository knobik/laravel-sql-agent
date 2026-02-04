<?php

declare(strict_types=1);

namespace Knobik\SqlAgent;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Knobik\SqlAgent\Agent\MessageBuilder;
use Knobik\SqlAgent\Agent\PromptRenderer;
use Knobik\SqlAgent\Agent\SqlAgent;
use Knobik\SqlAgent\Agent\ToolRegistry;
use Knobik\SqlAgent\Contracts\Agent;
use Knobik\SqlAgent\Contracts\LlmDriver;
use Knobik\SqlAgent\Events\SqlErrorOccurred;
use Knobik\SqlAgent\Listeners\AutoLearnFromError;
use Knobik\SqlAgent\Llm\LlmManager;
use Knobik\SqlAgent\Services\BusinessRulesLoader;
use Knobik\SqlAgent\Services\ContextBuilder;
use Knobik\SqlAgent\Services\ErrorAnalyzer;
use Knobik\SqlAgent\Services\KnowledgeLoader;
use Knobik\SqlAgent\Services\LearningMachine;
use Knobik\SqlAgent\Services\QueryPatternSearch;
use Knobik\SqlAgent\Services\SchemaIntrospector;
use Knobik\SqlAgent\Services\SemanticModelLoader;
use Knobik\SqlAgent\Tools\IntrospectSchemaTool;
use Knobik\SqlAgent\Tools\RunSqlTool;
use Knobik\SqlAgent\Tools\SaveLearningTool;
use Knobik\SqlAgent\Tools\SearchKnowledgeTool;

class SqlAgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sql-agent.php', 'sql-agent');

        // Register services as singletons
        $this->app->singleton(SemanticModelLoader::class);
        $this->app->singleton(BusinessRulesLoader::class);
        $this->app->singleton(QueryPatternSearch::class);
        $this->app->singleton(SchemaIntrospector::class);
        $this->app->singleton(KnowledgeLoader::class);
        $this->app->singleton(ErrorAnalyzer::class);
        $this->app->singleton(LearningMachine::class, function ($app) {
            return new LearningMachine(
                $app->make(ErrorAnalyzer::class),
            );
        });

        // ContextBuilder depends on other services
        $this->app->singleton(ContextBuilder::class, function ($app) {
            return new ContextBuilder(
                $app->make(SemanticModelLoader::class),
                $app->make(BusinessRulesLoader::class),
                $app->make(QueryPatternSearch::class),
                $app->make(SchemaIntrospector::class),
            );
        });

        // LLM Manager
        $this->app->singleton(LlmManager::class, function ($app) {
            return new LlmManager($app);
        });

        // Bind LlmDriver interface to manager's default driver
        $this->app->bind(LlmDriver::class, function ($app) {
            return $app->make(LlmManager::class)->driver();
        });

        // Tool Registry with default tools
        $this->app->singleton(ToolRegistry::class, function ($app) {
            $registry = new ToolRegistry();

            $registry->registerMany([
                new RunSqlTool(),
                new IntrospectSchemaTool($app->make(SchemaIntrospector::class)),
                new SaveLearningTool(),
                new SearchKnowledgeTool(),
            ]);

            return $registry;
        });

        // Agent support classes
        $this->app->singleton(PromptRenderer::class);
        $this->app->singleton(MessageBuilder::class);

        // SQL Agent
        $this->app->singleton(SqlAgent::class, function ($app) {
            return new SqlAgent(
                $app->make(LlmDriver::class),
                $app->make(ToolRegistry::class),
                $app->make(ContextBuilder::class),
                $app->make(PromptRenderer::class),
                $app->make(MessageBuilder::class),
            );
        });

        // Bind Agent interface to SqlAgent
        $this->app->bind(Agent::class, SqlAgent::class);
    }

    public function boot(): void
    {
        // Register event listeners
        Event::listen(SqlErrorOccurred::class, AutoLearnFromError::class);

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Views (includes prompts)
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'sql-agent');
        $this->loadViewsFrom(__DIR__ . '/../resources/prompts', 'sql-agent::prompts');

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\InstallCommand::class,
                Console\Commands\LoadKnowledgeCommand::class,
                Console\Commands\RunEvalsCommand::class,
                Console\Commands\ExportLearningsCommand::class,
                Console\Commands\ImportLearningsCommand::class,
                Console\Commands\PruneLearningsCommand::class,
            ]);

            // Publishables
            $this->publishes([
                __DIR__ . '/../config/sql-agent.php' => config_path('sql-agent.php'),
            ], 'sql-agent-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'sql-agent-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/sql-agent'),
            ], 'sql-agent-views');

            $this->publishes([
                __DIR__ . '/../resources/sql-agent/knowledge' => resource_path('sql-agent/knowledge'),
            ], 'sql-agent-knowledge');

            $this->publishes([
                __DIR__ . '/../resources/prompts' => resource_path('views/vendor/sql-agent/prompts'),
            ], 'sql-agent-prompts');
        }
    }
}
