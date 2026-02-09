<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Tools;

use Knobik\SqlAgent\Search\SearchManager;
use Knobik\SqlAgent\Search\SearchResult;
use Prism\Prism\Tool;
use RuntimeException;

class SearchKnowledgeTool extends Tool
{
    public function __construct(
        protected SearchManager $searchManager,
    ) {
        $this
            ->as('search_knowledge')
            ->for('Search the knowledge base for relevant query patterns and learnings. Use this to find similar queries, understand business logic, or discover past learnings about the database.')
            ->withStringParameter('query', 'The search query to find relevant knowledge.')
            ->withEnumParameter('type', "Filter results: 'all' (default), 'patterns' (saved query patterns), or 'learnings' (discovered fixes/gotchas).", ['all', 'patterns', 'learnings'], required: false)
            ->withNumberParameter('limit', 'Maximum number of results to return.', required: false)
            ->using($this);
    }

    public function __invoke(string $query, string $type = 'all', int $limit = 5): string
    {
        $query = trim($query);

        if (empty($query)) {
            throw new RuntimeException('Search query cannot be empty.');
        }

        if (! in_array($type, ['all', 'patterns', 'learnings'])) {
            $type = 'all';
        }

        $limit = min($limit, 20);
        $results = [];

        if (in_array($type, ['all', 'patterns'])) {
            $results['query_patterns'] = $this->searchPatterns($query, $limit);
        }

        if (in_array($type, ['all', 'learnings'])) {
            $results['learnings'] = $this->searchLearnings($query, $limit);
        }

        $results['total_found'] = count($results['query_patterns'] ?? []) + count($results['learnings'] ?? []);

        return json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
        if (! config('sql-agent.learning.enabled')) {
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
