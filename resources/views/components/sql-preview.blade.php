@props([
    'sql' => '',
])

<div {{ $attributes->merge(['class' => 'rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900']) }}>
    <div class="flex items-center justify-between px-3 py-2 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">SQL Query</span>
        <button
            x-data
            @click="navigator.clipboard.writeText(@js($sql)).then(() => $dispatch('notify', { message: 'Copied to clipboard!' }))"
            class="text-xs px-2 py-1 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 flex items-center gap-1"
            title="Copy SQL"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            Copy
        </button>
    </div>
    <div class="p-3 overflow-x-auto custom-scrollbar">
        <pre class="text-sm"><code class="language-sql" x-init="hljs.highlightElement($el)">{{ $sql }}</code></pre>
    </div>
</div>
