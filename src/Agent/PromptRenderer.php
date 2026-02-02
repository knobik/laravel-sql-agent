<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Agent;

use Illuminate\Support\Facades\View;

class PromptRenderer
{
    /**
     * Render a prompt template with the given data.
     */
    public function render(string $template, array $data = []): string
    {
        $viewName = "sql-agent::prompts.{$template}";

        if (! View::exists($viewName)) {
            // Try without prompts prefix as fallback
            $viewName = "sql-agent::{$template}";
        }

        return View::make($viewName, $data)->render();
    }

    /**
     * Render the system prompt with context.
     */
    public function renderSystem(string $context, array $extra = []): string
    {
        return $this->render('system', array_merge([
            'context' => $context,
        ], $extra));
    }

    /**
     * Check if a template exists.
     */
    public function exists(string $template): bool
    {
        return View::exists("sql-agent::prompts.{$template}")
            || View::exists("sql-agent::{$template}");
    }
}
