<div class="w-72 flex-shrink-0 bg-gray-100 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col h-full">
    {{-- Header --}}
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Conversations</h2>
            <button
                wire:click="newConversation"
                class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400"
                title="New conversation (Ctrl+N)"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>
        </div>

        {{-- Search --}}
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search conversations..."
                class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
    </div>

    {{-- Conversation List --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar">
        @if(empty($this->conversations))
            <div class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm">
                @if($search)
                    No conversations match "{{ $search }}"
                @else
                    No conversations yet
                @endif
            </div>
        @else
            <div class="p-2 space-y-1">
                @foreach($this->conversations as $conv)
                    <div
                        wire:key="conv-{{ $conv['id'] }}"
                        class="group relative rounded-lg {{ $selectedConversationId === $conv['id'] ? 'bg-primary-100 dark:bg-primary-900/30' : 'hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                    >
                        <button
                            wire:click="selectConversation({{ $conv['id'] }})"
                            class="w-full p-3 text-left"
                        >
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 {{ $selectedConversationId === $conv['id'] ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate {{ $selectedConversationId === $conv['id'] ? 'text-primary-900 dark:text-primary-100' : 'text-gray-900 dark:text-white' }}">
                                        {{ $conv['title'] ?? 'New Conversation' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ \Carbon\Carbon::parse($conv['updated_at'])->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </button>

                        {{-- Delete Button --}}
                        <button
                            wire:click="confirmDelete({{ $conv['id'] }})"
                            class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-lg opacity-0 group-hover:opacity-100 hover:bg-red-100 dark:hover:bg-red-900/30 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-opacity"
                            title="Delete conversation"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="$wire.cancelDelete()">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-sm w-full p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Conversation</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
                    </div>
                </div>

                <div class="flex gap-3 justify-end">
                    <button
                        wire:click="cancelDelete"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="deleteConversation"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
