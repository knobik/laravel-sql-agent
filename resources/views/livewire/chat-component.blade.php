<div class="flex-1 flex flex-col h-full overflow-hidden"
     x-data="{
        init() {
            // Scroll to bottom on new messages
            this.$watch('$wire.streamedContent', () => {
                this.$nextTick(() => {
                    const container = document.getElementById('messages-container');
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                });
            });
        }
     }"
     @copy-to-clipboard.window="navigator.clipboard.writeText($event.detail.text)"
>
    {{-- Header --}}
    <header class="flex-shrink-0 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $this->conversation?->title ?? 'New Conversation' }}
                </h1>
            </div>

            <div class="flex items-center gap-3">
                {{-- Connection Selector --}}
                <x-sql-agent::connection-selector
                    :connections="$this->connections"
                    :selected="$connection"
                    :disabled="$isLoading"
                />

                {{-- Dark Mode Toggle --}}
                <button
                    @click="darkMode = !darkMode"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400"
                    title="Toggle dark mode"
                >
                    <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                    <svg x-show="darkMode" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </button>

                {{-- Export Menu --}}
                @if($conversationId)
                    <div x-data="{ open: false }" class="relative">
                        <button
                            @click="open = !open"
                            @click.away="open = false"
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400"
                            title="Export"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-cloak
                            x-transition
                            class="absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 z-50"
                        >
                            <a
                                href="{{ route('sql-agent.export.json', $conversationId) }}"
                                class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                Export as JSON
                            </a>
                            <a
                                href="{{ route('sql-agent.export.csv', $conversationId) }}"
                                class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                Export as CSV
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </header>

    {{-- Messages Area --}}
    <div id="messages-container" class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-4">
        @if(empty($this->messages) && !$isLoading && empty($streamedContent))
            {{-- Empty State (hidden during loading) --}}
            <div wire:loading.remove wire:target="sendMessage" class="flex flex-col items-center justify-center h-full text-center">
                <div class="w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    Ask a question about your data
                </h2>
                <p class="text-gray-500 dark:text-gray-400 max-w-md">
                    I can help you query your database using natural language. Try asking something like:
                </p>
                <div class="mt-4 space-y-2">
                    <button
                        wire:click="$set('message', 'Show me the top 10 customers by total orders')"
                        class="block w-full px-4 py-2 text-sm text-left bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700"
                    >
                        "Show me the top 10 customers by total orders"
                    </button>
                    <button
                        wire:click="$set('message', 'What were the sales trends last month?')"
                        class="block w-full px-4 py-2 text-sm text-left bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700"
                    >
                        "What were the sales trends last month?"
                    </button>
                    <button
                        wire:click="$set('message', 'How many users signed up this week?')"
                        class="block w-full px-4 py-2 text-sm text-left bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700"
                    >
                        "How many users signed up this week?"
                    </button>
                </div>
            </div>
            {{-- Immediate loading indicator for empty state --}}
            <div wire:loading wire:target="sendMessage" class="flex justify-start">
                <x-sql-agent::loading-indicator />
            </div>
        @else
            {{-- Message History --}}
            @foreach($this->messages as $msg)
                <x-sql-agent::message
                    :role="$msg['role']"
                    :content="$msg['content']"
                    :sql="$msg['sql'] ?? null"
                    :results="$msg['results'] ?? null"
                />
            @endforeach

            {{-- Streaming Response --}}
            @if($isLoading && !empty($streamedContent))
                <x-sql-agent::message
                    role="assistant"
                    :content="$streamedContent"
                    :isStreaming="true"
                />
            @elseif($isLoading)
                <x-sql-agent::loading-indicator />
            @endif

            {{-- Immediate loading indicator (shows before server responds) --}}
            <div wire:loading wire:target="sendMessage">
                <x-sql-agent::loading-indicator />
            </div>
        @endif
    </div>

    {{-- Input Area --}}
    <div class="flex-shrink-0 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
        <form wire:submit="sendMessage" class="flex gap-3">
            <div class="flex-1 relative">
                <textarea
                    wire:model="message"
                    placeholder="Ask a question about your data..."
                    rows="1"
                    class="w-full px-4 py-3 pr-12 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none disabled:opacity-50"
                    @keydown.enter.prevent="if (!event.shiftKey) { $wire.sendMessage(); }"
                    wire:loading.attr="disabled"
                    wire:target="sendMessage"
                    {{ $isLoading ? 'disabled' : '' }}
                    x-data="{
                        resize() {
                            $el.style.height = 'auto';
                            $el.style.height = Math.min($el.scrollHeight, 200) + 'px';
                        }
                    }"
                    x-on:input="resize()"
                    x-init="resize()"
                ></textarea>

            </div>

            <button
                type="submit"
                data-send-button
                wire:loading.attr="disabled"
                wire:target="sendMessage"
                {{ $isLoading ? 'disabled' : '' }}
                class="px-4 py-3 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 dark:disabled:bg-primary-800 text-white rounded-xl font-medium transition-colors flex items-center gap-2 disabled:cursor-not-allowed"
            >
                {{-- Spinner shown during loading --}}
                <svg wire:loading wire:target="sendMessage" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{-- Send icon shown when not loading --}}
                <svg wire:loading.remove wire:target="sendMessage" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                <span class="hidden sm:inline">Send</span>
            </button>
        </form>

        <div class="mt-2 text-xs text-gray-400 dark:text-gray-500 text-center">
            Press <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-gray-400">Enter</kbd> to send,
            <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-600 dark:text-gray-400">Shift+Enter</kbd> for new line
        </div>
    </div>
</div>
