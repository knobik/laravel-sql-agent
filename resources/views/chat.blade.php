<x-sql-agent::layouts.app>
    {{-- Sidebar --}}
    <livewire:sql-agent-conversation-list :selectedConversationId="$conversationId ?? null" />

    {{-- Main Chat Area --}}
    <livewire:sql-agent-chat :conversationId="$conversationId ?? null" />
</x-sql-agent::layouts.app>
