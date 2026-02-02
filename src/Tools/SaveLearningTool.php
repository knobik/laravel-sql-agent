<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Tools;

use Knobik\SqlAgent\Enums\LearningCategory;
use Knobik\SqlAgent\Models\Learning;
use RuntimeException;

class SaveLearningTool extends BaseTool
{
    public function name(): string
    {
        return 'save_learning';
    }

    public function description(): string
    {
        return 'Save a new learning to the knowledge base. Use this when you discover something important about the database schema, business logic, or query patterns that would be useful for future queries.';
    }

    protected function schema(): array
    {
        $categories = array_map(
            fn (LearningCategory $cat) => $cat->value,
            LearningCategory::cases()
        );

        return $this->objectSchema([
            'title' => $this->stringProperty('A short, descriptive title for the learning (max 100 characters).'),
            'description' => $this->stringProperty('A detailed description of what was learned and why it matters.'),
            'category' => $this->stringProperty(
                'The category of this learning.',
                $categories
            ),
            'sql' => $this->stringProperty('Optional: The SQL query related to this learning.'),
        ], ['title', 'description', 'category']);
    }

    protected function handle(array $parameters): mixed
    {
        if (! config('sql-agent.learning.enabled', true)) {
            throw new RuntimeException('Learning feature is disabled.');
        }

        $title = trim($parameters['title'] ?? '');
        $description = trim($parameters['description'] ?? '');
        $categoryValue = $parameters['category'] ?? null;
        $sql = isset($parameters['sql']) ? trim($parameters['sql']) : null;

        if (empty($title)) {
            throw new RuntimeException('Title is required.');
        }

        if (strlen($title) > 100) {
            throw new RuntimeException('Title must be 100 characters or less.');
        }

        if (empty($description)) {
            throw new RuntimeException('Description is required.');
        }

        $category = LearningCategory::tryFrom($categoryValue);
        if ($category === null) {
            $validCategories = implode(', ', array_map(
                fn (LearningCategory $cat) => $cat->value,
                LearningCategory::cases()
            ));
            throw new RuntimeException("Invalid category. Valid categories are: {$validCategories}");
        }

        $learning = Learning::create([
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'sql' => $sql ?: null,
        ]);

        return [
            'success' => true,
            'message' => 'Learning saved successfully.',
            'learning_id' => $learning->id,
            'title' => $learning->title,
            'category' => $learning->category->value,
        ];
    }
}
