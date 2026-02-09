<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Support;

class UserResolver
{
    /**
     * Get the current user ID.
     * Returns null if user tracking is disabled or no user is authenticated.
     */
    public function id(): int|string|null
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $resolver = config('sql-agent.user.resolver');
        if ($resolver !== null && is_callable($resolver)) {
            return $resolver();
        }

        return auth()->id();
    }

    /**
     * Check if user tracking is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) config('sql-agent.user.enabled');
    }

    /**
     * Get the user model class.
     */
    public function getModelClass(): string
    {
        return config('sql-agent.user.model')
            ?? config('auth.providers.users.model', 'App\\Models\\User');
    }
}
