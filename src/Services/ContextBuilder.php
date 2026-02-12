<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Services;

use Illuminate\Support\Collection;
use Knobik\SqlAgent\Data\Context;
use Knobik\SqlAgent\Data\QueryPatternData;
use Knobik\SqlAgent\Search\SearchManager;
use Knobik\SqlAgent\Search\SearchResult;

class ContextBuilder
{
    public function __construct(
        protected SemanticModelLoader $semanticLoader,
        protected BusinessRulesLoader $rulesLoader,
        protected SearchManager $searchManager,
        protected SchemaIntrospector $introspector,
        protected ConnectionRegistry $connectionRegistry,
    ) {}

    /**
     * Build the complete context for a question.
     */
    public function build(string $question): Context
    {
        $semanticSections = [];
        $schemaSections = [];

        foreach ($this->connectionRegistry->all() as $name => $config) {
            $laravelConnection = $config->connection;

            $semantic = $this->semanticLoader->format($laravelConnection, $name);
            if ($semantic && $semantic !== 'No table metadata available.') {
                $semanticSections[] = "## Connection: {$name} ({$config->label})\n{$config->description}\n\n{$semantic}";
            }

            $schema = $this->introspector->getRelevantSchema($question, $laravelConnection, $name);
            if ($schema) {
                $schemaSections[] = "## Connection: {$name} ({$config->label})\n\n{$schema}";
            }
        }

        return new Context(
            semanticModel: implode("\n\n---\n\n", $semanticSections) ?: 'No table metadata available.',
            businessRules: $this->rulesLoader->format(),
            queryPatterns: $this->searchQueryPatterns($question),
            learnings: $this->searchLearnings($question),
            runtimeSchema: implode("\n\n---\n\n", $schemaSections) ?: null,
            customKnowledge: $this->searchCustomIndexes($question),
        );
    }

    /**
     * Build context with custom options.
     */
    public function buildWithOptions(
        string $question,
        bool $includeSemanticModel = true,
        bool $includeBusinessRules = true,
        bool $includeQueryPatterns = true,
        bool $includeLearnings = true,
        bool $includeRuntimeSchema = true,
        int $queryPatternLimit = 3,
        int $learningLimit = 5,
    ): Context {
        return new Context(
            semanticModel: $includeSemanticModel ? $this->buildSemanticModel() : '',
            businessRules: $includeBusinessRules ? $this->rulesLoader->format() : '',
            queryPatterns: $includeQueryPatterns ? $this->searchQueryPatterns($question, $queryPatternLimit) : collect(),
            learnings: $includeLearnings ? $this->searchLearnings($question, $learningLimit) : collect(),
            runtimeSchema: $includeRuntimeSchema ? $this->buildRuntimeSchema($question) : null,
            customKnowledge: $includeQueryPatterns ? $this->searchCustomIndexes($question) : collect(),
        );
    }

    /**
     * Build minimal context (just schema, no search).
     */
    public function buildMinimal(): Context
    {
        return new Context(
            semanticModel: $this->buildSemanticModel(),
            businessRules: $this->rulesLoader->format(),
            queryPatterns: collect(),
            learnings: collect(),
            runtimeSchema: null,
        );
    }

    /**
     * Build context with runtime introspection only.
     */
    public function buildRuntimeOnly(string $question): Context
    {
        return new Context(
            semanticModel: '',
            businessRules: '',
            queryPatterns: collect(),
            learnings: collect(),
            runtimeSchema: $this->buildRuntimeSchema($question),
        );
    }

    /**
     * Search for query patterns via SearchManager.
     *
     * @return Collection<int, QueryPatternData>
     */
    protected function searchQueryPatterns(string $question, int $limit = 3): Collection
    {
        return $this->searchManager->search($question, 'query_patterns', $limit)
            ->map(fn (SearchResult $result) => new QueryPatternData(
                name: $result->model->getAttribute('name'),
                question: $result->model->getAttribute('question'),
                sql: $result->model->getAttribute('sql'),
                summary: $result->model->getAttribute('summary'),
                tablesUsed: $result->model->getAttribute('tables_used') ?? [],
                dataQualityNotes: $result->model->getAttribute('data_quality_notes'),
            ));
    }

    /**
     * Search for relevant learnings via SearchManager.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function searchLearnings(string $question, int $limit = 5): Collection
    {
        if (! config('sql-agent.learning.enabled')) {
            return collect();
        }

        return $this->searchManager->search($question, 'learnings', $limit) // @phpstan-ignore return.type
            ->map(fn (SearchResult $result) => [
                'title' => $result->model->getAttribute('title'),
                'description' => $result->model->getAttribute('description'),
                'category' => $result->model->getAttribute('category')?->value,
                'sql' => $result->model->getAttribute('sql'),
            ]);
    }

    /**
     * Search custom indexes for additional knowledge.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function searchCustomIndexes(string $question, int $limit = 5): Collection
    {
        $customIndexes = $this->searchManager->getCustomIndexes();

        if (empty($customIndexes)) {
            return collect();
        }

        return $this->searchManager->searchMultiple($question, $customIndexes, $limit)
            ->map(function (SearchResult $result) {
                /** @var \Illuminate\Database\Eloquent\Model&\Knobik\SqlAgent\Contracts\Searchable $model */
                $model = $result->model;

                return $model->toSearchableArray();
            });
    }

    /**
     * Build semantic model across all configured connections.
     */
    protected function buildSemanticModel(): string
    {
        $sections = [];

        foreach ($this->connectionRegistry->all() as $name => $config) {
            $semantic = $this->semanticLoader->format($config->connection, $name);
            if ($semantic && $semantic !== 'No table metadata available.') {
                $sections[] = "## Connection: {$name} ({$config->label})\n{$config->description}\n\n{$semantic}";
            }
        }

        return implode("\n\n---\n\n", $sections) ?: 'No table metadata available.';
    }

    /**
     * Build runtime schema across all configured connections.
     */
    protected function buildRuntimeSchema(string $question): ?string
    {
        $sections = [];

        foreach ($this->connectionRegistry->all() as $name => $config) {
            $schema = $this->introspector->getRelevantSchema($question, $config->connection, $name);
            if ($schema) {
                $sections[] = "## Connection: {$name} ({$config->label})\n\n{$schema}";
            }
        }

        return implode("\n\n---\n\n", $sections) ?: null;
    }

    /**
     * Get the semantic model loader.
     */
    public function getSemanticLoader(): SemanticModelLoader
    {
        return $this->semanticLoader;
    }

    /**
     * Get the business rules loader.
     */
    public function getRulesLoader(): BusinessRulesLoader
    {
        return $this->rulesLoader;
    }

    /**
     * Get the schema introspector.
     */
    public function getIntrospector(): SchemaIntrospector
    {
        return $this->introspector;
    }
}
