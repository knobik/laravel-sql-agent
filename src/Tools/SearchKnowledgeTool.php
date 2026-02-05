<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Tools;

use Knobik\SqlAgent\Search\SearchManager;
use Knobik\SqlAgent\Search\SearchResult;
use RuntimeException;

class SearchKnowledgeTool extends BaseTool
{
    public function __construct(
        protected SearchManager $searchManager,
    ) {}

    public function name(): string
    {
        return 'search_knowledge';
    }

    public function description(): string
    {
        return 'Search the knowledge base for relevant query patterns and learnings. Use this to find similar queries, understand business logic, or discover past learnings about the database.';
    }

    protected function schema(): array
    {
        return $this->objectSchema([
            'query' => $this->stringProperty('The search query to find relevant knowledge.'),
            'type' => $this->stringProperty(
                "Filter results: 'all' (default), 'patterns' (saved query patterns), or 'learnings' (discovered fixes/gotchas).",
                ['all', 'patterns', 'learnings']
            ),
            'limit' => $this->integerProperty('Maximum number of results to return.', 1, 20),
        ], ['query']);
    }

    protected function handle(array $parameters): mixed
    {
        $query = trim($parameters['query'] ?? '');
        $type = $parameters['type'] ?? 'all';
        $limit = min($parameters['limit'] ?? 5, 20);

        if (empty($query)) {
            throw new RuntimeException('Search query cannot be empty.');
        }

        // Default to 'all' if invalid type provided
        if (! in_array($type, ['all', 'patterns', 'learnings'])) {
            $type = 'all';
        }

        $results = [];

        if (in_array($type, ['all', 'patterns'])) {
            $patterns = $this->searchPatterns($query, $limit);
            $results['query_patterns'] = $patterns;
        }

        if (in_array($type, ['all', 'learnings'])) {
            $learnings = $this->searchLearnings($query, $limit);
            $results['learnings'] = $learnings;
        }

        $results['total_found'] = count($results['query_patterns'] ?? []) + count($results['learnings'] ?? []);

        return $results;
    }

    protected function searchPatterns(string $query, int $limit): array
    {
        $searchResults = $this->searchManager->search($query, 'query_patterns', $limit);

        return $searchResults->map(fn (SearchResult $result) => [
            'name' => $result->model->getAttribute('name'),
            'question' => $result->model->getAttribute('question'),
            'sql' => $result->model->getAttribute('sql'),
            'summary' => $result->model->getAttribute('summary'),
            'tables_used' => $result->model->getAttribute('tables_used'),
            'relevance_score' => $result->score,
        ])->toArray();
    }

    protected function searchLearnings(string $query, int $limit): array
    {
        if (! config('sql-agent.learning.enabled', true)) {
            return [];
        }

        $searchResults = $this->searchManager->search($query, 'learnings', $limit);

        return $searchResults->map(fn (SearchResult $result) => [
            'title' => $result->model->getAttribute('title'),
            'description' => $result->model->getAttribute('description'),
            'category' => $result->model->getAttribute('category')?->value,
            'sql' => $result->model->getAttribute('sql'),
            'relevance_score' => $result->score,
        ])->toArray();
    }
}
