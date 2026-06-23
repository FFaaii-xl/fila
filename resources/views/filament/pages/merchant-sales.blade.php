{{-- Merchant Sales Report Page for Filament v5 --}}
<div>
    {{-- Header with sticky filter bar --}}
    <div class="sticky top-0 z-10 bg-white dark:bg-slate-900 border-b shadow-sm">
    <div class="px-4 py-4">
        <h1 class="fi-title text-2xl font-bold text-gray-900 dark:text-white">{{ $this->getHeading() }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            @if($mode === 'tanggal')
                Tanggal: {{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}
            @elseif($mode === 'nama')
                Periode: {{ \Carbon\Carbon::createFromFormat('m', $month)->format('F') }} {{ $year }}
            @elseif($mode === 'tahunan')
                Tahun: {{ $year }}
            @else
                Range: {{ \Carbon\Carbon::parse($dateStart)->format('d M') }} - {{ \Carbon\Carbon::parse($dateEnd)->format('d M Y') }}
            @endif
        </p>
    </div>
    
    {{-- Filter Controls --}}
    <div class="px-4 pb-4">
        {{-- Mode Tabs --}}
        <div class="flex gap-2 mb-3 overflow-x-auto">
            <button wire:click="$set('mode', 'tanggal')" 
                class="px-3 py-1.5 text-sm rounded-lg whitespace-nowrap transition-colors
                {{ $mode === 'tanggal' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Harian
            </button>
            <button wire:click="$set('mode', 'nama')" 
                class="px-3 py-1.5 text-sm rounded-lg whitespace-nowrap transition-colors
                {{ $mode === 'nama' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Bulanan
            </button>
            <button wire:click="$set('mode', 'tahunan')" 
                class="px-3 py-1.5 text-sm rounded-lg whitespace-nowrap transition-colors
                {{ $mode === 'tahunan' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Tahunan
            </button>
            <button wire:click="$set('mode', 'range')" 
                class="px-3 py-1.5 text-sm rounded-lg whitespace-nowrap transition-colors
                {{ $mode === 'range' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Range
            </button>
        </div>
        
        {{-- Date Inputs based on mode --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @if($mode === 'tanggal')
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Pilih Tanggal</label>
                    <input type="date" wire:model.live="selectedDate" 
                        class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                </div>
            @elseif($mode === 'nama')
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Bulan</label>
                    <select wire:model.live="month" class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                {{ \Carbon\Carbon::create(2024, $m, 1)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tahun</label>
                    <select wire:model.live="year" class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
                        @foreach(range(now()->year - 2, now()->year) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif($mode === 'tahunan')
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tahun</label>
                    <select wire:model.live="year" class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
                        @foreach(range(now()->year - 5, now()->year) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Mulai</label>
                    <input type="date" wire:model.live="dateStart" 
                        class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Selesai</label>
                    <input type="date" wire:model.live="dateEnd" 
                        class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
                </div>
            @endif
            
            {{-- Pedagang Filter (Admin/Pengurus only) --}}
            @if(!$isPedagangUser && count($pedagangList) > 0)
                <div class="col-span-2 md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Filter Pedagang</label>
                    <select wire:model.live="pedagangId" class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
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

{{-- Summary Cards --}}
<div class="p-4">
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
            <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Titip</p>
            <p class="text-lg font-bold text-blue-900 dark:text-blue-300">{{ number_format($totals['titip'] ?? 0) }}</p>
        </div>
        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3">
            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Laku</p>
            <p class="text-lg font-bold text-emerald-900 dark:text-emerald-300">{{ number_format($totals['laku'] ?? 0) }}</p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3">
            <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Modal</p>
            <p class="text-lg font-bold text-purple-900 dark:text-purple-300">Rp {{ number_format($totals['modal'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
            <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">KAS</p>
            <p class="text-lg font-bold text-amber-900 dark:text-amber-300">Rp {{ number_format($totals['kas'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-teal-50 dark:bg-teal-900/20 rounded-lg p-3">
            <p class="text-xs text-teal-600 dark:text-teal-400 font-medium">Setoran</p>
            <p class="text-lg font-bold text-teal-900 dark:text-teal-300">Rp {{ number_format($totals['setoran'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-rose-50 dark:bg-rose-900/20 rounded-lg p-3">
            <p class="text-xs text-rose-600 dark:text-rose-400 font-medium">Laba</p>
            <p class="text-lg font-bold text-rose-900 dark:text-rose-300">Rp {{ number_format($totals['laba'] ?? 0, 0, ',', '.') }}</p>
        </div>
    </div>
</div>

{{-- Not Reported Warning --}}
@if($notReported && count($notReported) > 0)
    <div class="mx-4 mb-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg">
        <p class="font-medium text-amber-800 dark:text-amber-200 mb-2">
            ⚠️ Pedagang belum melapor ({{ count($notReported) }})
        </p>
        <div class="flex flex-wrap gap-2">
            @foreach($notReported as $nama)
                <span class="px-2 py-1 text-xs bg-amber-100 dark:bg-amber-800 text-amber-700 dark:text-amber-300 rounded">
                    {{ $nama }}
                </span>
            @endforeach
        </div>
    </div>
@endif

{{-- Data Table --}}
<div class="px-4 pb-6">
    @if(count($reportData) > 0)
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-800">
                    <tr>
                        @if($mode === 'tanggal' && !$isPedagangProductMode)
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                                wire:click="sortBy('nama')">
                                Pedagang {!! $sort === 'nama' ? ($direction === 'asc' ? '↑' : '↓') : '' !!}
                            </th>
                        @elseif($mode === 'nama')
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        @elseif($mode === 'tahunan')
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bulan</th>
                        @else
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                @if($rangeType === 'tahun') Tahun @elseif($rangeType === 'bulan') Bulan @else Tanggal @endif
                            </th>
                        @endif
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Titip</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Laku</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Modal</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">KAS</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Tab</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Setoran</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Omset</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Laba</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($reportData as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                            @if($mode === 'tanggal' && !$isPedagangProductMode)
                                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $row->nama ?? '-' }}
                                </td>
                            @elseif($mode === 'nama')
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ \Carbon\Carbon::parse($row->tgl)->format('d M') }}
                                </td>
                            @elseif($mode === 'tahunan')
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ \Carbon\Carbon::createFromFormat('m', $row->bln)->format('F') }}
                                </td>
                            @else
                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    @if($rangeType === 'tahun')
                                        {{ $row->thn }}
                                    @elseif($rangeType === 'bulan')
                                        {{ \Carbon\Carbon::createFromFormat('m', $row->bln)->format('M Y') }}
                                    @else
                                        {{ \Carbon\Carbon::parse($row->tgl)->format('d M') }}
                                    @endif
                                </td>
                            @endif
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300">
                                {{ $row->total_produk ?? 0 }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300">
                                {{ $row->total_titip ?? 0 }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-emerald-600 dark:text-emerald-400 font-medium">
                                {{ $row->total_laku ?? 0 }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-300 hidden md:table-cell">
                                Rp {{ number_format($row->total_modal_final ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-amber-600 dark:text-amber-400 hidden lg:table-cell">
                                Rp {{ number_format($row->total_kas ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-teal-600 dark:text-teal-400 hidden lg:table-cell">
                                Rp {{ number_format($row->total_tab_final ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">
                                Rp {{ number_format($row->total_setoran ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-blue-600 dark:text-blue-400 hidden md:table-cell">
                                Rp {{ number_format($row->total_omset ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-sm text-right font-semibold {{ ($row->total_laba ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                Rp {{ number_format($row->total_laba ?? 0, 0, ',', '.') }}
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
                        <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300 hidden md:table-cell">Rp {{ number_format($totals['modal'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300 hidden lg:table-cell">Rp {{ number_format($totals['kas'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300 hidden lg:table-cell">Rp {{ number_format($totals['tab'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300">Rp {{ number_format($totals['setoran'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-sm text-right text-gray-700 dark:text-gray-300 hidden md:table-cell">Rp {{ number_format($totals['omset'] ?? 0, 0, ',', '.') }}</td>
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
