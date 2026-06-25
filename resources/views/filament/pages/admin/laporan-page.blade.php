{{-- Admin/Pengurus Laporan & Analisis Page --}}
<div>
    {{-- Header with sticky filter bar --}}
    <div class="sticky top-0 z-10 bg-white dark:bg-slate-900 border-b shadow-sm">
        <div class="px-4 py-4">
            <h1 class="fi-title text-2xl font-bold text-gray-900 dark:text-white">Laporan & Analisis</h1>
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
            {{-- Mode Tabs --}}
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
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
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
                
                {{-- Pedagang Filter --}}
                @if(count($pedagangList) > 0)
                    <div class="col-span-2 md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Filter Pedagang</label>
                        <select wire:model.live="pedagangId" class="w-full px-3 py-2 text-sm border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white rounded-lg">
                            <option value="">Semua Pedagang</option>
                            @foreach($pedagangList as $pdk)
                                <option value="{{ $pdk->id }}">{{ $pdk->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Summary Stats - Horizontal Scrollable --}}
    <div class="p-4">
        <div class="flex overflow-x-auto snap-x snap-mandatory gap-3 pb-4 scrollbar-hide">
            <div class="snap-start min-w-[140px] bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Titip</p>
                <p class="text-lg font-bold text-blue-900 dark:text-blue-300">{{ number_format($totals['titip'] ?? 0) }}</p>
            </div>
            <div class="snap-start min-w-[140px] bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3">
                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Laku</p>
                <p class="text-lg font-bold text-emerald-900 dark:text-emerald-300">{{ number_format($totals['laku'] ?? 0) }}</p>
            </div>
            <div class="snap-start min-w-[140px] bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3">
                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Modal</p>
                <p class="text-lg font-bold text-purple-900 dark:text-purple-300">Rp {{ number_format($totals['modal'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="snap-start min-w-[140px] bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Omset</p>
                <p class="text-lg font-bold text-blue-900 dark:text-blue-300">Rp {{ number_format($totals['omset'] ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="snap-start min-w-[140px] bg-rose-50 dark:bg-rose-900/20 rounded-lg p-3">
                <p class="text-xs text-rose-600 dark:text-rose-400 font-medium">Laba</p>
                <p class="text-lg font-bold {{ ($totals['laba'] ?? 0) >= 0 ? 'text-rose-900 dark:text-rose-300' : 'text-rose-600 dark:text-rose-400' }}">
                    Rp {{ number_format($totals['laba'] ?? 0, 0, ',', '.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="px-4 pb-6">
        @if(count($reportData) > 0)
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ $mode === 'bulanan' ? 'Tanggal' : 'Pedagang' }}
                            </th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produk</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Titip</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Laku</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">% Laku</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden lg:table-cell">Modal</th>
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
                                        {{ $row['nama'] ?: '-' }}
                                    @endif
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300">
                                    {{ $row['total_produk'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300">
                                    {{ $row['total_titip'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-emerald-600 dark:text-emerald-400 font-medium">
                                    {{ $row['total_laku'] }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300 hidden md:table-cell">
                                    {{ $row['persen_laku'] }}%
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300 hidden lg:table-cell">
                                    Rp {{ number_format($row['total_modal'], 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-blue-600 dark:text-blue-400">
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
                            <td class="px-3 py-2 text-sm text-center text-gray-700 dark:text-gray-300">{{ $totals['produk'] ?? 0 }}</td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300">{{ $totals['titip'] ?? 0 }}</td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300">{{ $totals['laku'] ?? 0 }}</td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300 hidden md:table-cell">
                                {{ ($totals['titip'] ?? 0) > 0 ? round(($totals['laku'] / $totals['titip']) * 100, 1) : 0 }}%
                            </td>
                            <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300 hidden lg:table-cell">Rp {{ number_format($totals['modal'] ?? 0, 0, ',', '.') }}</td>
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
                <p class="mt-4 text-lg font-medium">Tidak ada data untuk periode ini</p>
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
