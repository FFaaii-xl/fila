{{-- Reusable Data Table Component for Reports --}}

@props([
    'columns' => [], // Array of ['key' => string, 'label' => string, 'align' => 'left|center|right', 'class' => string]
    'data' => [],
    'totals' => [],
    'emptyMessage' => 'Tidak ada data untuk periode ini',
])

<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-slate-800">
            <tr>
                @foreach($columns as $col)
                    <th class="px-3 py-2 text-{{ $col['align'] ?? 'left' }} text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider
                        {{ $col['class'] ?? '' }}
                        {{ isset($col['sortable']) && $col['sortable'] ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-700' : '' }}">
                        {{ $col['label'] }}
                        @if(isset($col['sortable']) && $col['sortable'] && isset($sortColumn) && isset($sortDirection))
                            {!! $sortColumn === $col['key'] ? ($sortDirection === 'asc' ? '↑' : '↓') : '' !!}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($data as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                    @foreach($columns as $col)
                        <td class="px-3 py-2 whitespace-nowrap text-sm text-{{ $col['align'] ?? 'left' }} 
                            {{ $col['cell_class'] ?? 'text-gray-600 dark:text-gray-300' }}">
                            @isset($col['format'])
                                {!! $col['format']($row) !!}
                            @else
                                {{ data_get($row, $col['key'], '-') }}
                            @endisset
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(!empty($totals))
            <tfoot class="bg-gray-100 dark:bg-slate-800 font-semibold">
                <tr>
                    @foreach($columns as $col)
                        <td class="px-3 py-2 text-sm text-{{ $col['align'] ?? 'left' }} text-gray-700 dark:text-gray-300
                            {{ $col['cell_class'] ?? '' }}">
                            @if(isset($col['total']) && $col['total'])
                                {{ number_format($totals[$col['key']] ?? 0, 0, ',', '.') }}
                            @elseif(isset($col['total_label']))
                                {{ $col['total_label'] }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
</div>
