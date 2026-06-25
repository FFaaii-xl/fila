{{-- Merchant Sales Report - Native Filament Theme --}}
<div x-data="merchantSales()" class="space-y-6">
    
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
                            @input="filterTable()" 
                            placeholder="Cari pedagang..." 
                        />
                    </x-filament::input.wrapper>
                </div>
                
                {{-- Actions --}}
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <x-filament::button color="success" icon="heroicon-m-document-arrow-down" class="w-full sm:w-auto">
                        Export Excel
                    </x-filament::button>
                    <x-filament::button color="gray" icon="heroicon-m-printer" onclick="window.print()" class="w-full sm:w-auto">
                        Cetak
                    </x-filament::button>
                </div>
            </div>

            <hr class="border-t border-gray-200 dark:border-white/10 my-4" />

            {{-- Bottom Row: Filters --}}
            <div class="flex flex-col sm:flex-row items-center gap-4 flex-wrap">
                
                {{-- Mode Selector (Using button group style if possible, or just buttons) --}}
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
                            <x-filament::input.select wire:model.live="month">
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                                        {{ \Carbon\Carbon::create(2024, $m, 1)->format('M') }}
                                    </option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    @endif
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="year">
                            @foreach(range(now()->year - 2, now()->year) as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                @endif

                {{-- Pedagang Filter --}}
                @if(!$isPedagangUser && count($pedagangList) > 0)
                <div class="flex items-center gap-2">
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="pedagangId">
                            <option value="">Semua Pedagang</option>
                            @foreach($pedagangList as $pdk)
                                <option value="{{ $pdk->id }}">{{ $pdk->nama }}</option>
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
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <x-filament::section class="!p-3 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Pedagang</p>
            <p class="text-2xl font-bold mt-1 text-primary-600">{{ count($reportData) }}</p>
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
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Setoran</p>
            <p class="text-xl font-bold mt-1 text-primary-600">Rp {{ number_format($totals['setoran'] ?? 0, 0, ',', '.') }}</p>
        </x-filament::section>

        <x-filament::section class="!p-3 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Laba</p>
            <p class="text-xl font-bold mt-1 text-success-600">Rp {{ number_format($totals['laba'] ?? 0, 0, ',', '.') }}</p>
        </x-filament::section>
    </div>

    {{-- Alert: Belum Lapor --}}
    @if($notReported && count($notReported) > 0)
    <div class="p-3 rounded-xl border border-warning-200 bg-warning-50 dark:bg-warning-500/10 dark:border-warning-500/20">
        <div class="flex items-start gap-3">
            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-6 w-6 text-warning-500" />
            <div class="flex-1">
                <h3 class="text-xs font-bold text-warning-600 dark:text-warning-400">Belum Laporan:</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($notReported as $nama)
                        <span class="inline-flex items-center px-2 py-1 rounded-md bg-warning-100 text-warning-700 text-xs font-medium dark:bg-warning-500/20 dark:text-warning-400">
                            {{ $nama }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Data Table --}}
    <x-filament::section>
        @if(count($reportData) > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5" id="reportTable">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="px-2 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-2 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                            @php
                                $headerTitle = (isset($isPedagangProductMode) && $isPedagangProductMode) ? 'Produk' : ($mode === 'nama' ? 'Waktu' : ($mode === 'tahunan' ? 'Bulan' : 'Pedagang'));
                            @endphp
                            <a href="#" wire:click.prevent="sortBy('{{ in_array($mode, ['tanggal']) ? 'nama' : ($mode === 'nama' ? 'tgl' : 'bln') }}')" class="hover:text-primary-600">{{ $headerTitle }}</a>
                        </th>
                        <th class="px-2 py-1.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400"><a href="#" wire:click.prevent="sortBy('total_produk')" class="hover:text-primary-600">Prod</a></th>
                        <th class="px-2 py-1.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400"><a href="#" wire:click.prevent="sortBy('total_titip')" class="hover:text-primary-600">Titip</a></th>
                        <th class="px-2 py-1.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400"><a href="#" wire:click.prevent="sortBy('total_laku')" class="hover:text-primary-600">Laku</a></th>
                        <th class="px-2 py-1.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400"><a href="#" wire:click.prevent="sortBy('persen_laku')" class="hover:text-primary-600">%</a></th>
                        <th class="px-2 py-1.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 hidden md:table-cell"><a href="#" wire:click.prevent="sortBy('total_modal_final')" class="hover:text-primary-600">Modal</a></th>
                        <th class="px-2 py-1.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 hidden lg:table-cell"><a href="#" wire:click.prevent="sortBy('total_kas')" class="hover:text-primary-600">Kas</a></th>
                        <th class="px-2 py-1.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 hidden lg:table-cell"><a href="#" wire:click.prevent="sortBy('total_tab_final')" class="hover:text-primary-600">Tab</a></th>
                        <th class="px-2 py-1.5 text-right text-xs font-bold text-primary-600 dark:text-primary-400"><a href="#" wire:click.prevent="sortBy('total_setoran')" class="hover:text-primary-600">Setoran</a></th>
                        <th class="px-2 py-1.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 hidden md:table-cell"><a href="#" wire:click.prevent="sortBy('total_omset')" class="hover:text-primary-600">Omset</a></th>
                        <th class="px-2 py-1.5 text-right text-xs font-bold text-gray-500 dark:text-gray-400"><a href="#" wire:click.prevent="sortBy('total_laba')" class="hover:text-primary-600">Laba</a></th>
                        @if($mode === 'tahunan' || $mode === 'nama')
                        <th class="px-2 py-1.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @php 
                        $no = 1; 
                        $currentGroup = null;
                    @endphp
                    @foreach($reportData as $row)
                    @php
                        $groupName = null;
                        if ($mode === 'tahunan' && isset($row->bln)) {
                            $groupName = \Carbon\Carbon::createFromFormat('m', $row->bln)->format('F');
                        } elseif ($mode === 'nama' && isset($row->tgl)) {
                            $groupName = \Carbon\Carbon::parse($row->tgl)->format('M Y');
                        } elseif ($mode === 'range' && $rangeType === 'bulan' && isset($row->thn)) {
                            $groupName = \Carbon\Carbon::createFromDate($row->thn, $row->bln ?? 1, 1)->format('Y');
                        }

                        if ($groupName && $groupName !== $currentGroup) {
                            $currentGroup = $groupName;
                            echo '<tr class="bg-gray-100 dark:bg-white/5"><td colspan="13" class="px-4 py-2 font-bold text-xs text-primary-600 dark:text-primary-400 uppercase tracking-wider">' . $currentGroup . '</td></tr>';
                            $no = 1; // reset number per group
                        }
                    @endphp
                    <tr class="report-row hover:bg-gray-50 dark:hover:bg-white/5 transition-colors" data-name="{{ strtolower($row->nama ?? '') }}">
                        <td class="px-2 py-1.5 text-xs text-gray-500 dark:text-gray-400 text-center">{{ $no++ }}</td>
                        <td class="px-2 py-1.5 text-xs font-medium text-gray-900 dark:text-white whitespace-nowrap row-name" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                            @if(in_array($mode, ['tanggal'])) 
                                {{ $row->nama ?? '-' }} 
                            @elseif($mode === 'nama') 
                                {{ \Carbon\Carbon::parse($row->tgl)->format('d M Y') }} 
                            @elseif($mode === 'tahunan') 
                                {{ \Carbon\Carbon::createFromFormat('m', $row->bln)->format('F') }} 
                            @else
                                @if($rangeType === 'hari')
                                    {{ \Carbon\Carbon::parse($row->tgl ?? $row->thn)->format('d M Y') }}
                                @elseif($rangeType === 'bulan')
                                    {{ \Carbon\Carbon::createFromDate($row->thn ?? date('Y'), $row->bln ?? 1, 1)->format('F Y') }}
                                @else
                                    {{ $row->thn ?? '-' }}
                                @endif
                            @endif
                        </td>
                        <td class="px-2 py-1.5 text-xs text-gray-500 dark:text-gray-400 text-center">
                            {{ (isset($isPedagangProductMode) && $isPedagangProductMode) ? '-' : ($row->total_produk ?? 0) }}
                        </td>
                        <td class="px-2 py-1.5 text-xs text-gray-700 dark:text-gray-300 text-center">{{ number_format($row->total_titip ?? 0, 0, ',', '.') }}</td>
                        <td class="px-2 py-1.5 text-xs text-success-600 font-medium text-center">{{ number_format($row->total_laku ?? 0, 0, ',', '.') }}</td>
                        @php 
                            $percent = $row->total_titip > 0 ? round(($row->total_laku / $row->total_titip) * 100, 1) : 0;
                            $hue = min($percent * 1.4, 120);
                        @endphp
                        <td class="px-2 py-1.5 text-xs font-bold text-center" style="color: hsl({{ $hue }}, 80%, 45%); background: hsla({{ $hue }}, 80%, 45%, 0.1);">
                            {{ $percent }}%
                        </td>
                        <td class="px-2 py-1.5 text-xs text-gray-500 dark:text-gray-400 text-right hidden md:table-cell">
                            {{ number_format($row->total_modal_final ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-2 py-1.5 text-xs text-gray-500 dark:text-gray-400 text-right hidden lg:table-cell">
                            {{ (isset($isPedagangProductMode) && $isPedagangProductMode) ? '-' : number_format($row->total_kas ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-2 py-1.5 text-xs text-gray-500 dark:text-gray-400 text-right hidden lg:table-cell">
                            {{ (isset($isPedagangProductMode) && $isPedagangProductMode) ? '-' : number_format($row->total_tab_final ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-2 py-1.5 text-xs font-bold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-500/10 text-right">
                            {{ (isset($isPedagangProductMode) && $isPedagangProductMode) ? '-' : number_format($row->total_setoran ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-2 py-1.5 text-xs text-gray-700 dark:text-gray-300 text-right hidden md:table-cell">
                            {{ number_format($row->total_omset ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-2 py-1.5 text-xs font-bold {{ ($row->total_laba ?? 0) >= 0 ? 'text-success-600' : 'text-danger-600' }} text-right">
                            {{ number_format($row->total_laba ?? 0, 0, ',', '.') }}
                        </td>
                        @if($mode === 'tahunan' || $mode === 'nama')
                        <td class="px-2 py-1.5 text-center">
                            @if($mode === 'tahunan')
                            <x-filament::icon-button wire:click="$set('month', '{{ str_pad($row->bln, 2, '0', STR_PAD_LEFT) }}'); $set('mode', 'nama')" icon="heroicon-m-eye" color="primary" tooltip="Lihat Detail Bulan" />
                            @elseif($mode === 'nama')
                            <x-filament::icon-button wire:click="$set('selectedDate', '{{ $row->tgl }}'); $set('mode', 'tanggal')" icon="heroicon-m-eye" color="primary" tooltip="Lihat Detail Hari" />
                            @endif
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100 dark:bg-gray-900 border-t border-gray-200 dark:border-white/10 font-bold">
                        <td colspan="2" class="px-2 py-1.5 text-xs uppercase tracking-wider text-gray-500 text-center">Total Seluruh</td>
                        <td class="px-2 py-1.5 text-xs text-gray-700 dark:text-gray-300 text-center">{{ number_format($totals['produk'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-2 py-1.5 text-xs text-gray-700 dark:text-gray-300 text-center">{{ number_format($totals['titip'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-2 py-1.5 text-xs text-success-600 text-center">{{ number_format($totals['laku'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-2 py-1.5 text-xs text-center" style="background: hsla({{ min($globalPerc * 1.4, 120) }}, 80%, 45%, 0.1); color: hsl({{ min($globalPerc * 1.4, 120) }}, 80%, 45%);">
                            {{ $globalPerc }}%
                        </td>
                        <td class="px-2 py-1.5 text-xs text-gray-700 dark:text-gray-300 text-right hidden md:table-cell">{{ number_format($totals['modal'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-2 py-1.5 text-xs text-gray-700 dark:text-gray-300 text-right hidden lg:table-cell">{{ number_format($totals['kas'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-2 py-1.5 text-xs text-gray-700 dark:text-gray-300 text-right hidden lg:table-cell">{{ number_format($totals['tab'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-2 py-1.5 text-xs text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-500/10 text-right">{{ number_format($totals['setoran'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-2 py-1.5 text-xs text-gray-700 dark:text-gray-300 text-right hidden md:table-cell">{{ number_format($totals['omset'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-2 py-1.5 text-xs text-success-600 text-right">{{ number_format($totals['laba'] ?? 0, 0, ',', '.') }}</td>
                        @if($mode === 'tahunan' || $mode === 'nama')
                        <td></td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        <div class="p-8 text-center text-gray-500 dark:text-gray-400">
            <x-filament::icon icon="heroicon-o-document-magnifying-glass" class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
            <p class="text-lg font-medium">Hening...</p>
            <p class="text-sm">Data tidak ditemukan di semesta ini.</p>
        </div>
        @endif
    </x-filament::section>
</div>

<script>
    function merchantSales() {
        return {
            searchQuery: '',
            
            filterTable() {
                const query = this.searchQuery.toLowerCase();
                const rows = document.querySelectorAll('.report-row');
                let visibleNo = 1;
                
                rows.forEach(row => {
                    const name = row.dataset.name || '';
                    if (name.includes(query)) {
                        row.style.display = '';
                        row.querySelector('td:first-child').textContent = visibleNo++;
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        };
    }
</script>
