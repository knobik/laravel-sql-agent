@props([
    'role' => 'user',
    'content' => '',
    'sql' => null,
    'results' => null,
    'isStreaming' => false,
])

@php
    $isUser = $role === 'user' || $role === \Knobik\SqlAgent\Enums\MessageRole::User;
    $isAssistant = $role === 'assistant' || $role === \Knobik\SqlAgent\Enums\MessageRole::Assistant;
@endphp

<div class="flex gap-4 {{ $isUser ? 'justify-end' : 'justify-start' }}">
    @if($isAssistant)
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>
    @endif

    <div class="max-w-[80%] {{ $isUser ? 'order-first' : '' }}">
        <div class="rounded-2xl px-4 py-3 {{ $isUser ? 'bg-primary-500 text-white' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700' }}">
            <div class="markdown-content {{ $isStreaming ? 'stream-cursor' : '' }}" x-data x-html="marked.parse(@js($content))"></div>
        </div>

        @if($isAssistant && ($sql || $results))
            <div x-data="{ showSql: false, showResults: false }">
                <div class="mt-2 flex gap-2">
                    @if($sql)
                        <button
                            @click="showSql = !showSql"
                            class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 flex items-center gap-1"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            <span x-text="showSql ? 'Hide final SQL' : 'Show final SQL'"></span>
                        </button>
                    @endif

                    @if($results && count($results) > 0)
                        <button
                            @click="showResults = !showResults"
                            class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 flex items-center gap-1"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span x-text="showResults ? 'Hide Results' : 'Show Results (' + {{ count($results) }} + ')'"></span>
                        </button>
                    @endif
                </div>

                @if($sql)
                    <div x-show="showSql" x-cloak x-transition class="mt-2">
                        <x-sql-agent::sql-preview :sql="$sql" />
                    </div>
                @endif

                @if($results && count($results) > 0)
                    <div x-show="showResults" x-cloak x-transition class="mt-2">
                        <x-sql-agent::results-table :results="$results" />
                    </div>
                @endif
            </div>
        @endif
    </div>

    @if($isUser)
        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-400 dark:bg-gray-600 flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
        </div>
    @endif
</div>
