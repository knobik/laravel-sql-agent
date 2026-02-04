@props([
    'results' => [],
    'maxRows' => 100,
])

@php
    $displayResults = array_slice($results, 0, $maxRows);
    $hasMore = count($results) > $maxRows;
    $columns = !empty($displayResults) ? array_keys((array) $displayResults[0]) : [];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm']) }}>
    <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">
                Results
            </span>
            <span class="text-xs text-gray-400 dark:text-gray-500">
                ({{ count($results) }} {{ Str::plural('row', count($results)) }})
            </span>
        </div>
    </div>

    @if(empty($results))
        <div class="p-8 text-center">
            <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">No results found</p>
        </div>
    @else
        <div class="overflow-x-auto custom-scrollbar max-h-96">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                    <tr>
                        @foreach($columns as $column)
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap border-b border-gray-200 dark:border-gray-700">
                                {{ $column }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($displayResults as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            @foreach($columns as $column)
                                @php
                                    $value = is_object($row) ? ($row->$column ?? null) : ($row[$column] ?? null);
                                    $displayValue = is_array($value) || is_object($value) ? json_encode($value) : $value;
                                @endphp
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap max-w-xs truncate" title="{{ $displayValue }}">
                                    @if(is_null($value))
                                        <span class="text-gray-400 dark:text-gray-500 italic text-xs">NULL</span>
                                    @elseif(is_bool($value))
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $value ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                                            {{ $value ? 'true' : 'false' }}
                                        </span>
                                    @else
                                        {{ Str::limit((string) $displayValue, 50) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($hasMore)
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Showing <span class="font-semibold">{{ $maxRows }}</span> of <span class="font-semibold">{{ count($results) }}</span> rows
                </p>
            </div>
        @endif
    @endif
</div>
