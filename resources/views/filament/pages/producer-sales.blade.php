{{-- Producer Sales Report - Native Filament Theme --}}
<div x-data="{ allOpen: {{ $mode === 'tanggal' ? 'true' : 'false' }}, searchQuery: '' }" @toggle-all.window="allOpen = $event.detail.state" class="space-y-6">
    
    {{-- Toolbar --}}
    <x-filament::section>
        <div class="flex flex-col gap-4">
            {{-- Top Row: Search & Actions --}}
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                {{-- Search --}}
                <div class="w-full sm:w-1/3">
                    <x-filament::input.wrapper icon="heroicon-m-magnifying-glass">
                        <x-filament::input 
                            type="text" 
                            x-model="searchQuery" 
                            @input="filterGroups()" 
                            placeholder="Cari produsen/produk..." 
                        />
                    </x-filament::input.wrapper>
                </div>
                
                {{-- Actions --}}
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <x-filament::button color="gray" icon="heroicon-m-arrows-up-down" @click.prevent="allOpen = !allOpen; $dispatch('toggle-all', { state: allOpen })" class="w-full sm:w-auto">
                        <span x-text="allOpen ? 'Tutup Semua' : 'Buka Semua'"></span>
                    </x-filament::button>
                    <x-filament::button color="success" icon="heroicon-m-document-arrow-down" class="w-full sm:w-auto">
                        Export
                    </x-filament::button>
                    <x-filament::button color="gray" icon="heroicon-m-printer" onclick="window.print()" class="w-full sm:w-auto">
                        Cetak
                    </x-filament::button>
                </div>
            </div>

            <hr class="border-t border-gray-200 dark:border-white/10 my-4" />

            {{-- Bottom Row: Filters --}}
            <div class="flex flex-col sm:flex-row items-center gap-4 flex-wrap">
                
                {{-- Mode Selector --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mr-2">Mode:</span>
                    <x-filament::button wire:click="$set('mode', 'tanggal')" size="sm" color="{{ $mode === 'tanggal' ? 'primary' : 'gray' }}">
                        Harian
                    </x-filament::button>
                    <x-filament::button wire:click="$set('mode', 'nama')" size="sm" color="{{ $mode === 'nama' ? 'primary' : 'gray' }}">
                        Bulanan
                    </x-filament::button>
                    <x-filament::button wire:click="$set('mode', 'tahunan')" size="sm" color="{{ $mode === 'tahunan' ? 'primary' : 'gray' }}">
                        Tahunan
                    </x-filament::button>
                    <x-filament::button wire:click="$set('mode', 'range')" size="sm" color="{{ $mode === 'range' ? 'primary' : 'gray' }}">
                        Range
                    </x-filament::button>
                </div>

                {{-- Date/Range Inputs --}}
                <div class="flex items-center gap-2">
                    @if($mode === 'tanggal')
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="selectedDate" />
                        </x-filament::input.wrapper>
                    @elseif($mode === 'range')
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="dateStart" />
                        </x-filament::input.wrapper>
                        <span class="text-gray-400">-</span>
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="dateEnd" />
                        </x-filament::input.wrapper>
                    @endif
                </div>

                {{-- Month/Year Selector --}}
                @if($mode === 'nama' || $mode === 'tahunan')
                <div class="flex items-center gap-2">
                    @if($mode === 'nama')
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="selectedMonth">
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                        {{ \Carbon\Carbon::create(2024, $m, 1)->format('M') }}
                                    </option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    @endif
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedYear">
                            @foreach(range(now()->year - 2, now()->year) as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                @endif

                {{-- Produsen Filter --}}
                @if(!$isProdusenOnlyMode && count($produsenList) > 0)
                <div class="flex items-center gap-2">
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedProdusen">
                            <option value="">Semua Produsen</option>
                            @foreach($produsenList as $psn)
                                <option value="{{ $psn->id }}">{{ $psn->nama }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                @endif

            </div>
        </div>
    </x-filament::section>

    {{-- Summary Widget --}}
    @php 
        $globalPerc = ($totals['titip'] ?? 0) > 0 ? round((($totals['laku'] ?? 0) / ($totals['titip'] ?? 1)) * 100, 1) : 0;
        $countLabel = $isProdusenOnlyMode ? 'Produk' : 'Produsen';
        $countValue = is_array($groupedData) ? count($groupedData) : $groupedData->count();
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <x-filament::section class="!p-3 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ $countLabel }}</p>
            <p class="text-2xl font-bold mt-1 text-primary-600">{{ $countValue }}</p>
        </x-filament::section>
        
        <x-filament::section class="!p-3 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Titipan</p>
            <p class="text-2xl font-bold mt-1">{{ number_format($totals['titip'] ?? 0) }}</p>
        </x-filament::section>

        <x-filament::section class="!p-3 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Terjual</p>
            <p class="text-2xl font-bold mt-1 text-success-600">{{ number_format($totals['laku'] ?? 0) }}</p>
        </x-filament::section>

        <x-filament::section class="!p-3 text-center relative overflow-hidden">
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Efisiensi</p>
            <p class="text-2xl font-bold mt-1" style="color: hsl({{ min($globalPerc * 1.4, 120) }}, 80%, 45%);">{{ $globalPerc }}%</p>
            <div class="absolute bottom-0 left-0 h-1 bg-gray-200 w-full dark:bg-gray-700">
                <div class="h-full" style="width: {{ min($globalPerc, 100) }}%; background-color: hsl({{ min($globalPerc * 1.4, 120) }}, 80%, 45%);"></div>
            </div>
        </x-filament::section>

        <x-filament::section class="!p-3 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Omset Bruto</p>
            <p class="text-xl font-bold mt-1 text-primary-600">Rp {{ number_format($totals['omset'] ?? 0, 0, ',', '.') }}</p>
        </x-filament::section>
    </div>

    {{-- DATA GROUPS (ADMIN NESTED VIEW) --}}
    <div class="flex flex-col gap-4 pb-12" id="reportContainer">
        @forelse($groupedData as $key => $group)
        @php 
            $summary = $group['summary'] ?? [];
            $perc = ($summary['titip'] ?? 0) > 0 ? round(($summary['laku'] ?? 0) / ($summary['titip'] ?? 1) * 100, 1) : 0;
            $hariJualan = $summary['hari_jualan'] ?? 1;
            $isDaily = ($mode === 'tanggal');
        @endphp
        
        <div class="group-box fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden" x-data="{ open: {{ $isDaily ? 'true' : 'false' }} }" @toggle-all.window="open = $event.detail.state" data-group="{{ strtolower($key) }}">
            {{-- Card Header --}}
            <div @click="open = !open" class="flex flex-col sm:flex-row sm:items-center justify-between px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition-colors border-b border-gray-200 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <x-filament::icon icon="heroicon-m-chevron-right" class="h-5 w-5 text-gray-400 transition-transform duration-300" x-bind:class="open ? 'rotate-90' : ''" />
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-primary-100 text-primary-600 text-xs font-bold dark:bg-primary-500/20 dark:text-primary-400">{{ $loop->iteration }}</span>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white capitalize tracking-wide group-title">{{ ucwords(strtolower($key)) }}</h3>
                    @if(!$isDaily)
                        <x-filament::badge color="gray" size="sm">{{ $hariJualan }} Hari</x-filament::badge>
                    @endif
                </div>
                
                <div class="mt-2 sm:mt-0 flex items-center gap-3 text-sm">
                    <div class="flex items-center gap-1"><span class="text-gray-500">T:</span> <span class="font-medium text-gray-900 dark:text-gray-200">{{ number_format($summary['titip'] ?? 0, 0, ',', '.') }}</span></div>
                    <div class="flex items-center gap-1"><span class="text-gray-500">L:</span> <span class="font-medium text-success-600">{{ number_format($summary['laku'] ?? 0, 0, ',', '.') }}</span></div>
                    <x-filament::badge color="{{ $perc >= 80 ? 'success' : ($perc >= 50 ? 'warning' : 'danger') }}">{{ $perc }}%</x-filament::badge>
                    <div class="hidden sm:flex items-center gap-1"><span class="text-gray-500">Rp</span> <span class="font-bold text-primary-600 dark:text-primary-400">{{ number_format($summary['omset'] ?? 0, 0, ',', '.') }}</span></div>
                </div>
            </div>
            
            {{-- Card Content --}}
            <div x-show="open" x-collapse class="bg-gray-50/50 dark:bg-white/5">
                @if(isset($group['products']) && count($group['products']) > 0)
                    {{-- Nested Products --}}
                    <div class="pl-4 sm:pl-10 border-l-2 border-gray-200 dark:border-white/10 ml-6 py-3 pr-4">
                        @foreach($group['products'] as $productName => $productData)
                        @php 
                            $pSummary = $productData['summary'] ?? [];
                            $pPerc = ($pSummary['titip'] ?? 0) > 0 ? round(($pSummary['laku'] ?? 0) / ($pSummary['titip'] ?? 1) * 100, 1) : 0;
                            $pHari = $pSummary['hari_jualan'] ?? 1;
                        @endphp
                        <div class="product-group mt-2" x-data="{ openPr: false }" @toggle-all.window="openPr = $event.detail.state">
                            <div @click="openPr = !openPr" class="flex flex-col sm:flex-row sm:items-center justify-between px-2 py-1.5 cursor-pointer bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-gray-100 text-gray-600 text-xs font-medium dark:bg-gray-700 dark:text-gray-300">{{ $loop->iteration }}</span>
                                    <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 capitalize tracking-wider">{{ ucwords(strtolower($productName)) }}</h4>
                                    <span class="text-xs text-gray-400">[{{ $pHari }}h]</span>
                                </div>
                                <div class="mt-1 sm:mt-0 flex items-center gap-3 text-xs">
                                    <div class="flex items-center gap-1"><span class="text-gray-500">T:</span> <span class="font-medium">{{ number_format($pSummary['titip'] ?? 0, 0, ',', '.') }}</span></div>
                                    <div class="flex items-center gap-1"><span class="text-gray-500">L:</span> <span class="font-medium text-success-600">{{ number_format($pSummary['laku'] ?? 0, 0, ',', '.') }}</span></div>
                                    <span class="font-bold" style="color: hsl({{ min($pPerc * 1.4, 120) }}, 80%, 45%);">{{ $pPerc }}%</span>
                                </div>
                            </div>
                            <div x-show="openPr" x-collapse class="mt-1 bg-white dark:bg-gray-800 rounded-lg mx-1 overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10">
                                @foreach($productData['details'] ?? [] as $detail)
                                <div class="px-4 py-2 flex items-center justify-between text-xs border-b border-gray-100 dark:border-white/5 last:border-0 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium w-16">
                                        @if(isset($detail->tgl))
                                            {{ \Carbon\Carbon::parse($detail->tgl)->format('d M') }}
                                        @elseif(isset($detail->bln))
                                            {{ \Carbon\Carbon::createFromFormat('m', $detail->bln)->format('F') }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                    <div class="flex items-center gap-3 w-24 justify-center">
                                        <span class="font-medium text-gray-600 dark:text-gray-300">{{ $detail->total_titip ?? 0 }}</span>
                                        <x-filament::icon icon="heroicon-m-arrow-right" class="h-3 w-3 text-gray-400" />
                                        <span class="font-medium text-success-600">{{ $detail->total_laku ?? 0 }}</span>
                                    </div>
                                    <span class="font-semibold text-primary-600 dark:text-primary-400 w-24 text-right">Rp {{ number_format($detail->total_omset ?? 0, 0, ',', '.') }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                @elseif(isset($group['details']))
                    {{-- Direct Details --}}
                    <div class="divide-y divide-gray-100 dark:divide-white/5 p-4">
                        @foreach($group['details'] as $detail)
                        <div class="py-2 flex items-center justify-between text-sm hover:bg-gray-50 dark:hover:bg-white/5 transition-colors px-2 rounded-md">
                            <span class="text-gray-600 dark:text-gray-300 font-medium">
                                @if(isset($detail->tgl))
                                    {{ \Carbon\Carbon::parse($detail->tgl)->format('d M') }}
                                @elseif(isset($detail->bln))
                                    {{ \Carbon\Carbon::createFromFormat('m', $detail->bln)->format('F') }}
                                @else
                                    {{ $detail->produsen_nama ?? '' }}
                                @endif
                            </span>
                            <div class="flex items-center gap-6">
                                <span class="text-gray-600 dark:text-gray-300 w-8 text-right">{{ $detail->total_titip ?? 0 }}</span>
                                <span class="text-success-600 font-medium w-8 text-right">{{ $detail->total_laku ?? 0 }}</span>
                                <span class="text-primary-600 dark:text-primary-400 font-bold w-24 text-right">Rp {{ number_format($detail->total_omset ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <x-filament::section>
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
                    <p class="text-lg font-medium">Hening...</p>
                    <p class="text-sm">Data tidak ditemukan di semesta ini.</p>
                </div>
            </x-filament::section>
        </div>
        @endforelse
    </div>
</div>

<script>
    function filterGroups() {
        const query = document.querySelector('[x-model="searchQuery"]').value.toLowerCase();
        const groups = document.querySelectorAll('.group-box');
        
        groups.forEach(group => {
            const name = group.dataset.group || '';
            let hasVisibleProduct = false;
            
            // Check nested products if available
            const products = group.querySelectorAll('.product-group');
            if (products.length > 0) {
                products.forEach(prod => {
                    const prodName = prod.querySelector('.tracking-wider').textContent.toLowerCase();
                    if (prodName.includes(query) || name.includes(query)) {
                        prod.style.display = '';
                        hasVisibleProduct = true;
                    } else {
                        prod.style.display = 'none';
                    }
                });
            }
            
            if (name.includes(query) || hasVisibleProduct) {
                group.style.display = '';
            } else {
                group.style.display = 'none';
            }
        });
    }
</script>
