<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Tools;

use Knobik\SqlAgent\Models\Learning;
use Knobik\SqlAgent\Models\QueryPattern;
use RuntimeException;

class SearchKnowledgeTool extends BaseTool
{
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
                'Optional: Filter by knowledge type.',
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
        $patterns = QueryPattern::search($query)
            ->limit($limit)
            ->get();

        return $patterns->map(fn (QueryPattern $pattern) => [
            'name' => $pattern->name,
            'question' => $pattern->question,
            'sql' => $pattern->sql,
            'summary' => $pattern->summary,
            'tables_used' => $pattern->tables_used,
        ])->toArray();
    }

    protected function searchLearnings(string $query, int $limit): array
    {
        if (! config('sql-agent.learning.enabled', true)) {
            return [];
        }

        $learnings = Learning::search($query)
            ->limit($limit)
            ->get();

        return $learnings->map(fn (Learning $learning) => [
            'title' => $learning->title,
            'description' => $learning->description,
            'category' => $learning->category?->value,
            'sql' => $learning->sql,
        ])->toArray();
    }
}
