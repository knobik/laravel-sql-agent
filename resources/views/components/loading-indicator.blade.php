@props([
    'text' => 'Thinking',
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 text-gray-500 dark:text-gray-400']) }}>
    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center animate-pulse">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    </div>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3">
        <span class="loading-dots">
            {{ $text }}<span>.</span><span>.</span><span>.</span>
        </span>
    </div>
</div>
