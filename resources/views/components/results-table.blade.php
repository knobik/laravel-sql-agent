@props([
    'results' => [],
    'maxRows' => 100,
])

@php
    $displayResults = array_slice($results, 0, $maxRows);
    $hasMore = count($results) > $maxRows;
    $columns = !empty($displayResults) ? array_keys((array) $displayResults[0]) : [];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700']) }}>
    <div class="flex items-center justify-between px-3 py-2 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
            Results ({{ count($results) }} {{ Str::plural('row', count($results)) }})
        </span>
    </div>

    @if(empty($results))
        <div class="p-4 text-center text-gray-500 dark:text-gray-400">
            No results found
        </div>
    @else
        <div class="overflow-x-auto custom-scrollbar max-h-96">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                    <tr>
                        @foreach($columns as $column)
                            <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                {{ $column }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($displayResults as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            @foreach($columns as $column)
                                @php
                                    $value = is_object($row) ? ($row->$column ?? null) : ($row[$column] ?? null);
                                    $displayValue = is_array($value) || is_object($value) ? json_encode($value) : $value;
                                @endphp
                                <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100 whitespace-nowrap max-w-xs truncate" title="{{ $displayValue }}">
                                    @if(is_null($value))
                                        <span class="text-gray-400 dark:text-gray-500 italic">NULL</span>
                                    @elseif(is_bool($value))
                                        <span class="px-1.5 py-0.5 text-xs rounded {{ $value ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' }}">
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
            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 text-center text-xs text-gray-500 dark:text-gray-400">
                Showing {{ $maxRows }} of {{ count($results) }} rows
            </div>
        @endif
    @endif
</div>
