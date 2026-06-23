<div class="fi-page">
    {{-- Header with sticky filter bar --}}
    <div class="sticky top-0 z-10 bg-white dark:bg-slate-900 border-b shadow-sm">
        <div class="px-4 py-4">
            <h1 class="fi-title text-2xl font-bold text-gray-900 dark:text-white">{{ $this->getHeading() }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                @if($mode === 'tanggal')
                    Tanggal: {{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}
                @elseif($mode === 'nama')
                    Periode: {{ \Carbon\Carbon::createFromFormat('m', $selectedMonth)->format('F') }} {{ $selectedYear }}
                @elseif($mode === 'tahunan')
                    Tahun: {{ $selectedYear }}
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
                        <select wire:model.live="selectedMonth" class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                    {{ \Carbon\Carbon::create(2024, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tahun</label>
                        <select wire:model.live="selectedYear" class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
                            @foreach(range(now()->year - 2, now()->year) as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif($mode === 'tahunan')
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tahun</label>
                        <select wire:model.live="selectedYear" class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
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
                
                {{-- Produsen Filter (Admin/Pengurus only) --}}
                @if(!$isProdusenOnlyMode && count($produsenList) > 0)
                    <div class="col-span-2 md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Filter Produsen</label>
                        <select wire:model.live="selectedProdusen" class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
                            <option value="">Semua Produsen</option>
                            @foreach($produsenList as $psn)
                                <option value="{{ $psn->id }}">{{ $psn->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="px-4 py-4">
        <div class="flex overflow-x-auto snap-x snap-mandatory gap-3 pb-2 scrollbar-hide">
            <div class="snap-start shrink-0 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 min-w-[140px]">
                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Total Titip</p>
                <p class="text-xl font-bold text-blue-900 dark:text-blue-300">{{ number_format($totals['titip'] ?? 0) }}</p>
            </div>
            <div class="snap-start shrink-0 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 min-w-[140px]">
                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Total Laku</p>
                <p class="text-xl font-bold text-emerald-900 dark:text-emerald-300">{{ number_format($totals['laku'] ?? 0) }}</p>
            </div>
            <div class="snap-start shrink-0 bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 min-w-[140px]">
                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Total Omset</p>
                <p class="text-xl font-bold text-purple-900 dark:text-purple-300">Rp {{ number_format($totals['omset'] ?? 0, 0, ',', '.') }}</p>
            </div>
            @if($totals['titip'] > 0)
            <div class="snap-start shrink-0 bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 min-w-[140px]">
                <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">% Terjual</p>
                <p class="text-xl font-bold text-amber-900 dark:text-amber-300">{{ round(($totals['laku'] / $totals['titip']) * 100, 1) }}%</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Grouped Data Cards --}}
    <div class="px-4 pb-6 space-y-4">
        @forelse($groupedData as $key => $group)
            <div x-data="{ expanded: true }" class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                {{-- Card Header --}}
                <button 
                    x-on:click="expanded = !expanded"
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <svg x-show="!expanded" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <svg x-show="expanded" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $key }}</span>
                    </div>
                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                        <span class="hidden md:inline">{{ $group['summary']['hari_jualan'] ?? 1 }} hari</span>
                        <span class="text-blue-600 dark:text-blue-400">{{ $group['summary']['titip'] ?? 0 }}</span>
                        <span class="text-emerald-600 dark:text-emerald-400">{{ $group['summary']['laku'] ?? 0 }}</span>
                        <span class="text-purple-600 dark:text-purple-400 font-medium">Rp {{ number_format($group['summary']['omset'] ?? 0, 0, ',', '.') }}</span>
                        <svg x-bind:class="{ 'rotate-180': expanded }" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>
                
                {{-- Card Content --}}
                <div x-show="expanded" x-collapse>
                    @if(isset($group['products']))
                        {{-- Nested: Products under each Produsen --}}
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($group['products'] as $productName => $productData)
                                <div x-data="{ productExpanded: false }" class="border-l-4 border-l-primary-500">
                                    <button 
                                        x-on:click="productExpanded = !productExpanded"
                                        class="w-full px-4 py-2 bg-white dark:bg-slate-900 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $productName }}</span>
                                        </div>
                                        <div class="flex items-center gap-3 text-xs text-gray-500">
                                            <span>{{ $productData['summary']['titip'] ?? 0 }} titip</span>
                                            <span class="text-emerald-600">{{ $productData['summary']['laku'] ?? 0 }} laku</span>
                                            <span class="font-medium">Rp {{ number_format($productData['summary']['omset'] ?? 0, 0, ',', '.') }}</span>
                                        </div>
                                    </button>
                                    <div x-show="productExpanded" x-collapse class="bg-gray-50 dark:bg-slate-800/50">
                                        @foreach($productData['details'] ?? [] as $detail)
                                            <div class="px-6 py-2 flex items-center justify-between text-sm border-b border-gray-100 dark:border-gray-800 last:border-0">
                                                <span class="text-gray-500 dark:text-gray-400">
                                                    @if(isset($detail->tgl))
                                                        {{ \Carbon\Carbon::parse($detail->tgl)->format('d M') }}
                                                    @elseif(isset($detail->bln))
                                                        {{ \Carbon\Carbon::createFromFormat('m', $detail->bln)->format('F') }}
                                                    @else
                                                        -
                                                    @endif
                                                </span>
                                                <span>{{ $detail->total_titip ?? 0 }} → {{ $detail->total_laku ?? 0 }}</span>
                                                <span class="font-medium">Rp {{ number_format($detail->total_omset ?? 0, 0, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif(isset($group['details']))
                        {{-- Simple: Direct details --}}
                        <div class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($group['details'] as $detail)
                                <div class="px-4 py-2 flex items-center justify-between text-sm bg-white dark:bg-slate-900">
                                    <span class="text-gray-600 dark:text-gray-400">
                                        @if(isset($detail->tgl))
                                            {{ \Carbon\Carbon::parse($detail->tgl)->format('d M') }}
                                        @elseif(isset($detail->bln))
                                            {{ \Carbon\Carbon::createFromFormat('m', $detail->bln)->format('F') }}
                                        @else
                                            {{ $detail->produsen_nama ?? '' }}
                                        @endif
                                    </span>
                                    <div class="flex items-center gap-4">
                                        <span class="text-blue-600 dark:text-blue-400">{{ $detail->total_titip ?? 0 }}</span>
                                        <span class="text-emerald-600 dark:text-emerald-400">{{ $detail->total_laku ?? 0 }}</span>
                                        <span class="font-medium text-purple-600 dark:text-purple-400">Rp {{ number_format($detail->total_omset ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="mt-4 text-lg font-medium">Tidak ada data untuk periode ini</p>
                <p class="text-sm mt-1">Coba ubah filter atau pilih tanggal lain</p>
            </div>
        @endforelse
    </div>
</div>
