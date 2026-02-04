@props([
    'sql' => '',
])

<div {{ $attributes->merge(['class' => 'rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm']) }}>
    <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
            </svg>
            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">SQL Query</span>
        </div>
        <button
            x-data="{ copied: false }"
            @click="navigator.clipboard.writeText(@js($sql)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
            class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors font-medium"
            title="Copy SQL"
        >
            <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <svg x-show="copied" x-cloak class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span x-text="copied ? 'Copied!' : 'Copy'"></span>
        </button>
    </div>
    <div class="p-4 overflow-x-auto custom-scrollbar bg-gray-50 dark:bg-gray-900">
        <pre class="text-sm leading-relaxed"><code class="language-sql" x-init="hljs.highlightElement($el)">{{ $sql }}</code></pre>
    </div>
</div>
