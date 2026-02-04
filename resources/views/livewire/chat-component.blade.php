<div class="flex-1 flex flex-col h-full overflow-hidden bg-white dark:bg-gray-800"
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
    <header class="flex-shrink-0 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->conversation?->title ?? 'New Conversation' }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">SQL Agent</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                {{-- Connection Selector --}}
                <x-sql-agent::connection-selector
                    :connections="$this->connections"
                    :selected="$connection"
                    :disabled="$isLoading"
                />

                {{-- Dark Mode Toggle --}}
                <button
                    @click="darkMode = !darkMode"
                    class="p-2.5 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition-colors"
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
                            class="p-2.5 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition-colors"
                            title="Export"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 py-1 z-50"
                        >
                            <a
                                href="{{ route('sql-agent.export.json', $conversationId) }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Export as JSON
                            </a>
                            <a
                                href="{{ route('sql-agent.export.csv', $conversationId) }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Export as CSV
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </header>

    {{-- Messages Area --}}
    <div id="messages-container" class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6 bg-gray-50 dark:bg-gray-900">
        @if(empty($this->messages) && !$isLoading && empty($streamedContent))
            {{-- Empty State --}}
            <div wire:loading.remove wire:target="sendMessage" class="flex flex-col items-center justify-center h-full text-center px-4">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center mb-6 shadow-lg shadow-primary-500/25">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                    Ask a question about your data
                </h2>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mb-8">
                    I can help you query your database using natural language. Try asking something like:
                </p>
                <div class="w-full max-w-lg space-y-3">
                    <button
                        wire:click="$set('message', 'Show me the top 10 customers by total orders')"
                        class="group w-full p-4 text-left bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-700 transition-all shadow-sm hover:shadow"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center text-primary-500 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <span class="font-medium">"Show me the top 10 customers by total orders"</span>
                        </div>
                    </button>
                    <button
                        wire:click="$set('message', 'What were the sales trends last month?')"
                        class="group w-full p-4 text-left bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-700 transition-all shadow-sm hover:shadow"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center text-primary-500 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <span class="font-medium">"What were the sales trends last month?"</span>
                        </div>
                    </button>
                    <button
                        wire:click="$set('message', 'How many users signed up this week?')"
                        class="group w-full p-4 text-left bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-700 transition-all shadow-sm hover:shadow"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center text-primary-500 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                            <span class="font-medium">"How many users signed up this week?"</span>
                        </div>
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
                    :metadata="$msg['metadata'] ?? null"
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
    <div class="flex-shrink-0 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 sm:p-6">
        <form wire:submit="sendMessage" class="max-w-4xl mx-auto">
            <div class="flex gap-3 items-start">
                <div class="flex-1 relative">
                    <textarea
                        wire:model="message"
                        placeholder="Ask a question about your data..."
                        rows="1"
                        class="w-full h-12 px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none disabled:opacity-50 shadow-sm transition-shadow focus:shadow-md overflow-hidden"
                        @keydown.enter="if (!event.shiftKey) { event.preventDefault(); $wire.sendMessage(); }"
                        wire:loading.attr="disabled"
                        wire:target="sendMessage"
                        {{ $isLoading ? 'disabled' : '' }}
                        x-data="{
                            resize() {
                                $el.style.height = '48px';
                                $el.style.height = Math.min($el.scrollHeight, 200) + 'px';
                                $el.style.overflowY = $el.scrollHeight > 200 ? 'auto' : 'hidden';
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
                    class="h-12 px-5 bg-primary-500 hover:bg-primary-600 disabled:bg-gray-300 dark:disabled:bg-gray-700 text-white rounded-xl font-semibold transition-all flex items-center justify-center gap-2 disabled:cursor-not-allowed shadow-sm hover:shadow-md disabled:shadow-none"
                >
                    {{-- Spinner shown during loading --}}
                    <svg wire:loading wire:target="sendMessage" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{-- Send icon shown when not loading --}}
                    <svg wire:loading.remove wire:target="sendMessage" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                    </svg>
                    <span class="hidden sm:inline">Send</span>
                </button>
            </div>

            <div class="mt-3 flex items-center justify-center gap-4 text-xs text-gray-400 dark:text-gray-500">
                <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-500 dark:text-gray-400 font-mono text-[10px]">Enter</kbd>
                    <span>to send</span>
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-500 dark:text-gray-400 font-mono text-[10px]">Shift</kbd>
                    <span>+</span>
                    <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-500 dark:text-gray-400 font-mono text-[10px]">Enter</kbd>
                    <span>for new line</span>
                </span>
            </div>
        </form>
    </div>
</div>
