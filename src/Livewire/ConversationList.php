<?php

declare(strict_types=1);

namespace Knobik\SqlAgent\Livewire;

use Illuminate\Support\Facades\Auth;
use Knobik\SqlAgent\Models\Conversation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationList extends Component
{
    public string $search = '';

    public ?int $selectedConversationId = null;

    public bool $showDeleteConfirm = false;

    public ?int $deleteConversationId = null;

    public function mount(?int $selectedConversationId = null): void
    {
        $this->selectedConversationId = $selectedConversationId;
    }

    #[Computed]
    public function conversations(): array
    {
        $query = Conversation::forUser(Auth::id())
            ->orderByDesc('updated_at');

        if (! empty($this->search)) {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        return $query->limit(50)->get()->toArray();
    }

    public function selectConversation(int $conversationId): void
    {
        $this->selectedConversationId = $conversationId;
        $this->dispatch('load-conversation', conversationId: $conversationId);
    }

    public function newConversation(): void
    {
        $this->selectedConversationId = null;
        $this->dispatch('new-conversation');
    }

    public function confirmDelete(int $conversationId): void
    {
        $this->deleteConversationId = $conversationId;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->deleteConversationId = null;
        $this->showDeleteConfirm = false;
    }

    public function deleteConversation(): void
    {
        if (! $this->deleteConversationId) {
            return;
        }

        $conversation = Conversation::find($this->deleteConversationId);

        if (! $conversation || $conversation->user_id !== Auth::id()) {
            $this->cancelDelete();

            return;
        }

        // Delete associated messages first
        $conversation->messages()->delete();
        $conversation->delete();

        // If we deleted the selected conversation, create a new one
        if ($this->selectedConversationId === $this->deleteConversationId) {
            $this->newConversation();
        }

        $this->cancelDelete();
    }

    #[On('conversation-updated')]
    public function refreshList(): void
    {
        // This will trigger a re-render which updates the computed property
        unset($this->conversations);
    }

    public function render()
    {
        return view('sql-agent::livewire.conversation-list');
    }
}
