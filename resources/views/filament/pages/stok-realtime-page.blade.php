<x-filament-panels::page>
    @php
        $prevDate = date('Y-m-d', strtotime('-1 day', strtotime($tanggal)));
        $nextDate = date('Y-m-d', strtotime('+1 day', strtotime($tanggal)));
        $today = now()->toDateString();

        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $dayName = $days[date('w', strtotime($tanggal))];
        $formattedDate = date('d M Y', strtotime($tanggal));
    @endphp

    {{-- Header & Date Navigation --}}
    <x-filament::section>
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Monitoring sisa stok produk Anda di meja Pedagang
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="?date={{ $prevDate }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-white/5 dark:hover:bg-white/10 rounded-lg text-sm text-gray-700 dark:text-gray-300 transition-colors">
                    &larr; Prev
                </a>
                <div class="px-4 py-2 bg-primary-50 dark:bg-primary-500/10 border border-primary-200 dark:border-primary-500/20 rounded-lg">
                    <span class="text-primary-600 dark:text-primary-400 font-bold">{{ $dayName }}</span>
                    <span class="text-gray-600 dark:text-gray-300 ml-2">{{ $formattedDate }}</span>
                </div>
                <a href="?date={{ $nextDate }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-white/5 dark:hover:bg-white/10 rounded-lg text-sm text-gray-700 dark:text-gray-300 transition-colors {{ $tanggal >= $today ? 'opacity-50 pointer-events-none' : '' }}">
                    Next &rarr;
                </a>
                <a href="?date={{ $today }}" class="px-3 py-2 bg-success-50 dark:bg-success-500/10 hover:bg-success-100 dark:hover:bg-success-500/20 border border-success-200 dark:border-success-500/20 rounded-lg text-success-600 dark:text-success-400 text-sm font-medium transition-colors">
                    Hari Ini
                </a>
            </div>
        </div>
    </x-filament::section>

    {{-- Summary Metrics --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-2">
                <x-heroicon-o-users class="w-8 h-8 text-primary-500 mb-2 opacity-80" />
                <span class="text-3xl font-black font-mono text-gray-900 dark:text-white">{{ $total_pedagang }}</span>
                <span class="text-xs font-bold text-gray-500 uppercase mt-1">Total Pedagang</span>
            </div>
        </x-filament::section>
        
        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-2">
                <x-heroicon-o-cube class="w-8 h-8 text-warning-500 mb-2 opacity-80" />
                <span class="text-3xl font-black font-mono text-warning-600 dark:text-warning-400">{{ $total_sisa }}</span>
                <span class="text-xs font-bold text-gray-500 uppercase mt-1">Total Sisa Stok</span>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-2">
                <x-heroicon-o-shopping-bag class="w-8 h-8 text-success-500 mb-2 opacity-80" />
                <span class="text-3xl font-black font-mono text-success-600 dark:text-success-400">{{ $total_produk }}</span>
                <span class="text-xs font-bold text-gray-500 uppercase mt-1">Produk Aktif</span>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-2">
                <x-heroicon-o-chart-bar class="w-8 h-8 text-info-500 mb-2 opacity-80" />
                <span class="text-3xl font-black font-mono text-info-600 dark:text-info-400">{{ $total_laku }}</span>
                <span class="text-xs font-bold text-gray-500 uppercase mt-1">Total Terjual</span>
            </div>
        </x-filament::section>
    </div>

    {{-- Detail Stok Table --}}
    <div class="mt-6">
        @if($stoks->isEmpty())
        <x-filament::section padding="0">
            <div class="text-center py-12">
                <x-heroicon-o-cube class="w-16 h-16 mx-auto text-gray-400 opacity-50 mb-4" />
                <p class="text-lg font-bold text-gray-900 dark:text-white">Belum Ada Data Stok</p>
                <p class="text-sm mt-2 text-gray-500">Tidak ada stok untuk produk Anda pada tanggal ini</p>
            </div>
        </x-filament::section>
        @else
            @php
                $groupedStoks = $stoks->groupBy('pedagang_id');
            @endphp
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($groupedStoks as $pedagangId => $items)
                @php
                    $pedagangName = $items->first()->pedagang_nama;
                    $totalSisa = $items->sum('sisa_jual');
                    $totalLaku = $items->sum('laku');
                    $totalTitip = $items->sum('titip');
                    
                    $words = explode(' ', $pedagangName);
                    $initials = count($words) >= 2 ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1)) : strtoupper(substr($pedagangName, 0, 2));
                @endphp
                <x-filament::section padding="0">
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-500/20 flex items-center justify-center">
                                <span class="text-warning-600 dark:text-warning-400 font-bold text-sm">{{ $initials }}</span>
                            </div>
                            <div>
                                <span class="text-sm font-bold text-gray-900 dark:text-white block">{{ $pedagangName }}</span>
                                <p class="text-[10px] text-gray-500 uppercase tracking-wider mt-0.5 block">ID: {{ $pedagangId }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 md:gap-6">
                            <div class="text-center">
                                <span class="block text-[10px] text-gray-500 uppercase">Titip</span>
                                <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $totalTitip }}</span>
                            </div>
                            <div class="text-center">
                                <span class="block text-[10px] text-gray-500 uppercase">Laku</span>
                                <span class="font-mono font-bold text-success-600 dark:text-success-400">{{ $totalLaku }}</span>
                            </div>
                            <div class="text-center">
                                <span class="block text-[10px] text-gray-500 uppercase">Sisa</span>
                                <span class="font-mono font-bold text-warning-600 dark:text-warning-400">{{ $totalSisa }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="fi-ta-table w-full text-start divide-y divide-gray-200 dark:divide-white/5">
                            <thead>
                                <tr>
                                    <th class="fi-ta-header-cell px-4 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-left">Produk</th>
                                    <th class="fi-ta-header-cell px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-center">Titip</th>
                                    <th class="fi-ta-header-cell px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-center">Laku</th>
                                    <th class="fi-ta-header-cell px-2 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-center">Sisa</th>
                                    <th class="fi-ta-header-cell px-4 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider text-center">Update</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach($items as $item)
                                @php
                                    $sisaClass = $item->sisa_jual > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-success-600 dark:text-success-400';
                                    $updateTime = $item->updated_at ? date('H:i', strtotime($item->updated_at)) : '-';
                                @endphp
                                <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                    <td class="fi-ta-cell px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $item->produk_nama }}</td>
                                    <td class="fi-ta-cell px-2 py-2 text-center text-sm font-mono text-gray-700 dark:text-gray-300">{{ $item->titip }}</td>
                                    <td class="fi-ta-cell px-2 py-2 text-center text-sm font-mono text-success-600 dark:text-success-400">{{ $item->laku }}</td>
                                    <td class="fi-ta-cell px-2 py-2 text-center text-sm font-mono font-bold {{ $sisaClass }}">{{ $item->sisa_jual }}</td>
                                    <td class="fi-ta-cell px-4 py-2 text-center text-xs text-gray-500">{{ $updateTime }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
