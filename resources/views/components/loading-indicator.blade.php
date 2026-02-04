@props([
    'text' => 'Thinking',
])

<div {{ $attributes->merge(['class' => 'flex items-start gap-4']) }}>
    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center shadow-sm animate-pulse">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    </div>
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-5 py-4 shadow-sm">
        <div class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
            <span>{{ $text }}</span>
            <span class="loading-dots">
                <span class="inline-block">.</span><span class="inline-block">.</span><span class="inline-block">.</span>
            </span>
        </div>
    </div>
</div>
