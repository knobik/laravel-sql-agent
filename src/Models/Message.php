<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Knobik\SqlAgent\Enums\MessageRole;

class Message extends Model
{
    use HasFactory;

    protected $table = 'sql_agent_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'sql',
        'results',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'role' => MessageRole::class,
            'results' => 'array',
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function scopeOfRole($query, MessageRole $role)
    {
        return $query->where('role', $role);
    }

    public function scopeFromUser($query)
    {
        return $query->ofRole(MessageRole::User);
    }

    public function scopeFromAssistant($query)
    {
        return $query->ofRole(MessageRole::Assistant);
    }

    public function scopeWithSql($query)
    {
        return $query->whereNotNull('sql');
    }

    public function isFromUser(): bool
    {
        return $this->role === MessageRole::User;
    }

    public function isFromAssistant(): bool
    {
        return $this->role === MessageRole::Assistant;
    }

    public function isSystem(): bool
    {
        return $this->role === MessageRole::System;
    }

    public function isTool(): bool
    {
        return $this->role === MessageRole::Tool;
    }

    public function hasSql(): bool
    {
        return ! empty($this->sql);
    }

    public function hasResults(): bool
    {
        return ! empty($this->results);
    }

    public function getResultCount(): int
    {
        return count($this->results ?? []);
    }

    public function getToolName(): ?string
    {
        return $this->metadata['tool_name'] ?? null;
    }

    public function getToolCallId(): ?string
    {
        return $this->metadata['tool_call_id'] ?? null;
    }

    public function getExecutionTime(): ?float
    {
        return $this->metadata['execution_time'] ?? null;
    }
}
