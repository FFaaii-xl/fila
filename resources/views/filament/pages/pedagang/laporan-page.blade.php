{{-- Pedagang Riwayat Penjualan Page --}}
<div>
    {{-- Header with sticky filter bar --}}
    <div class="sticky top-0 z-10 bg-white dark:bg-slate-900 border-b shadow-sm">
        <div class="px-4 py-4">
            <h1 class="fi-title text-2xl font-bold text-gray-900 dark:text-white">Riwayat Penjualan</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                @if($mode === 'tanggal')
                    Tanggal: {{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}
                @elseif($mode === 'bulanan')
                    Periode: {{ \Carbon\Carbon::createFromFormat('m', $month)->format('F') }} {{ $year }}
                @else
                    Range: {{ \Carbon\Carbon::parse($dateStart)->format('d M') }} - {{ \Carbon\Carbon::parse($dateEnd)->format('d M Y') }}
                @endif
            </p>
        </div>
        
        {{-- Filter Controls --}}
        <div class="px-4 pb-4">
            {{-- Mode Tabs - Horizontal Scrollable --}}
            <div class="flex gap-2 mb-3 overflow-x-auto snap-x snap-mandatory">
                @foreach($this->getModes() as $modeKey => $modeLabel)
                    <button wire:click="$set('mode', '{{ $modeKey }}')" 
                        class="px-3 py-1.5 text-sm rounded-lg whitespace-nowrap transition-colors snap-start
                        {{ $mode === $modeKey ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700' }}">
                        {{ $modeLabel }}
                    </button>
                @endforeach
            </div>
            
            {{-- Date Inputs based on mode --}}
            <div class="grid grid-cols-2 gap-3">
                @if($mode === 'tanggal')
                    <div class="col-span-2 md:col-span-1">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Pilih Tanggal</label>
                        <input type="date" wire:model.live="selectedDate" 
                            class="w-full px-3 py-2 text-sm border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white rounded-lg focus:ring-2 focus:ring-emerald-500">
                    </div>
                @elseif($mode === 'bulanan')
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Bulan</label>
                        <select wire:model.live="month" class="w-full px-3 py-2 text-sm border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white rounded-lg">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                    {{ \Carbon\Carbon::create(2024, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tahun</label>
                        <select wire:model.live="year" class="w-full px-3 py-2 text-sm border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white rounded-lg">
                            @foreach(range(now()->year - 2, now()->year) as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tanggal Mulai</label>
                        <input type="date" wire:model.live="dateStart" 
                            class="w-full px-3 py-2 text-sm border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white rounded-lg">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tanggal Selesai</label>
                        <input type="date" wire:model.live="dateEnd" 
                            class="w-full px-3 py-2 text-sm border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white rounded-lg">
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Summary Stats - Horizontal Scrollable Cards --}}
    <div class="p-4">
        <div class="flex overflow-x-auto snap-x snap-mandatory gap-3 pb-4 scrollbar-hide">
            <div class="snap-start min-w-[120px] bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
                <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">Titip</p>
                <p class="text-lg font-bold text-amber-900 dark:text-amber-300">{{ number_format($totals['titip'] ?? 0) }}</p>
            </div>
            <div class="snap-start min-w-[120px] bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3">
                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Laku</p>
                <p class="text-lg font-bold text-emerald-900 dark:text-emerald-300">{{ number_format($totals['laku'] ?? 0) }}</p>
            </div>
            <div class="snap-start min-w-[120px] bg-rose-50 dark:bg-rose-900/20 rounded-lg p-3">
                <p class="text-xs text-rose-600 dark:text-rose-400 font-medium">Retur</p>
                <p class="text-lg font-bold text-rose-900 dark:text-rose-300">{{ number_format($totals['retur'] ?? 0) }}</p>
            </div>
            <div class="snap-start min-w-[120px] bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3">
                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Sisa</p>
                <p class="text-lg font-bold text-purple-900 dark:text-purple-300">{{ number_format($totals['sisa'] ?? 0) }}</p>
            </div>
            <div class="snap-start min-w-[140px] bg-teal-50 dark:bg-teal-900/20 rounded-lg p-3">
                <p class="text-xs text-teal-600 dark:text-teal-400 font-medium">Omset</p>
                <p class="text-lg font-bold text-teal-900 dark:text-teal-300">Rp {{ number_format($totals['omset'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="snap-start min-w-[140px] bg-rose-50 dark:bg-rose-900/20 rounded-lg p-3">
                <p class="text-xs text-rose-600 dark:text-rose-400 font-medium">Laba</p>
                <p class="text-lg font-bold {{ ($totals['laba'] ?? 0) >= 0 ? 'text-rose-900 dark:text-rose-300' : 'text-rose-600 dark:text-rose-400' }}">
                    Rp {{ number_format($totals['laba'] ?? 0, 0, ',', '.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Data Table / List View --}}
    <div class="px-4 pb-6">
        @if(count($reportData) > 0)
            {{-- Mobile Card View --}}
            <div class="space-y-3 md:hidden">
                @foreach($reportData as $row)
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $row['produk_nama'] ?: '-' }}</h3>
                                @if($mode === 'bulanan')
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($row['tgl'])->format('d M Y') }}</p>
                                @endif
                            </div>
                            <span class="text-lg font-bold {{ $row['total_laba'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                Rp {{ number_format($row['total_laba'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-sm">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Titip</p>
                                <p class="font-medium text-amber-600 dark:text-amber-400">{{ $row['total_titip'] }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Laku</p>
                                <p class="font-medium text-emerald-600 dark:text-emerald-400">{{ $row['total_laku'] }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Retur</p>
                                <p class="font-medium text-rose-600 dark:text-rose-400">{{ $row['total_retur'] }}</p>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Omset</span>
                            <span class="font-medium text-teal-600 dark:text-teal-400">Rp {{ number_format($row['total_omset'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Desktop Table View --}}
            <div class="hidden md:block overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ $mode === 'bulanan' ? 'Tanggal' : 'Produk' }}
                            </th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Titip</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Laku</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">Retur</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">Sisa</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Omset</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Laba</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($reportData as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    @if($mode === 'bulanan')
                                        {{ \Carbon\Carbon::parse($row['tgl'])->format('d M') }}
                                    @else
                                        {{ $row['produk_nama'] ?: '-' }}
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-amber-600 dark:text-amber-400">
                                    {{ $row['total_titip'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-emerald-600 dark:text-emerald-400 font-medium">
                                    {{ $row['total_laku'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-rose-600 dark:text-rose-400 hidden lg:table-cell">
                                    {{ $row['total_retur'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-purple-600 dark:text-purple-400 hidden lg:table-cell">
                                    {{ $row['sisa'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-teal-600 dark:text-teal-400">
                                    Rp {{ number_format($row['total_omset'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-right font-semibold {{ $row['total_laba'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    Rp {{ number_format($row['total_laba'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-slate-800 font-semibold">
                        <tr>
                            <td class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300">TOTAL</td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300">{{ $totals['titip'] ?? 0 }}</td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300">{{ $totals['laku'] ?? 0 }}</td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300 hidden lg:table-cell">{{ $totals['retur'] ?? 0 }}</td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300 hidden lg:table-cell">{{ $totals['sisa'] ?? 0 }}</td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300">Rp {{ number_format($totals['omset'] ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300">Rp {{ number_format($totals['laba'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-4 text-lg font-medium">Tidak ada data penjualan untuk periode ini</p>
                <p class="text-sm mt-1">Coba ubah filter atau pilih tanggal lain</p>
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
@endpush
