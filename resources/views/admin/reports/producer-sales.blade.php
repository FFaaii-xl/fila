<div class="space-y-6 mesh-glow-bg" x-data="{ allOpen: {{ $mode === 'tanggal' ? 'true' : 'false' }} }">
    <x-hhr-toolbar>
        <x-slot:filters>
            {{-- Group 2: Mode Selector --}}
            <div class="hhr-group">
                <span class="hhr-label-ghost hidden sm:inline">Mode</span>
                <select name="mode" class="form-select" onchange="this.form.submit()" style="width: auto;">
                    <option value="tanggal" {{ $mode == 'tanggal' ? 'selected' : '' }}>Harian</option>
                    <option value="nama" {{ $mode == 'nama' ? 'selected' : '' }}>Bulanan</option>
                    <option value="tahunan" {{ $mode == 'tahunan' ? 'selected' : '' }}>Tahunan</option>
                    <option value="range" {{ $mode == 'range' ? 'selected' : '' }}>Range Tanggal</option>
                </select>
            </div>

            {{-- Group 3: Date/Range Inputs --}}
            <div class="hhr-group">
                @if($mode === 'tanggal')
                    <span class="hhr-label-ghost hidden sm:inline">Tgl</span>
                    <input type="date" name="tanggal" value="{{ $selectedDate }}" class="form-input" onchange="this.form.submit()" style="width: auto;">
                @elseif($mode === 'range')
                    <span class="hhr-label-ghost hidden sm:inline text-[10px]">Mulai</span>
                    <input type="date" name="date_start" value="{{ $dateStart }}" class="form-input" onchange="this.form.submit()" style="width: auto;">
                    <span class="hhr-label-ghost hidden sm:inline text-[10px]">Sampai</span>
                    <input type="date" name="date_end" value="{{ $dateEnd }}" class="form-input" onchange="this.form.submit()" style="width: auto;">
                @endif
            </div>

            {{-- Group 4: Producer Filter (HIDE when logged in as Produsen) --}}
            @if($mode !== 'tanggal' && !$isProdusenOnlyMode)
            <div class="hhr-group">
                <select name="produsen_id" class="form-select" onchange="this.form.submit()" style="width: auto; max-width: 140px;">
                    <option value="">Semua Produsen</option>
                    @foreach($produsenList as $ps)
                        <option value="{{ $ps->id }}" {{ $selectedProdusen == $ps->id ? 'selected' : '' }}>{{ $ps->nama }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Group 5: Period Selector (Month/Year) --}}
            @if($mode === 'nama' || $mode === 'tahunan')
            <div class="hhr-group">
                @if($mode === 'nama')
                    <select name="month" class="form-select" onchange="this.form.submit()" style="width: auto;">
                        @foreach(range(1,12) as $m)
                            <option value="{{ sprintf('%02d', $m) }}" {{ $selectedMonth == $m ? 'selected' : '' }}>{{ date('M', mktime(0,0,0,$m,1)) }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="year" class="form-select" onchange="this.form.submit()" style="width: auto;">
                    @for($y=date('Y'); $y>=2023; $y--)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            @endif
        </x-slot:filters>

        <x-slot:search>
            <div class="relative">
                <input type="text" id="liveSearch" placeholder="Cari produsen atau produk..." class="form-input w-full pl-8" style="padding-left: 1.8rem !important;">
                <div class="absolute left-2.5 top-1/2 -translate-y-1/2 opacity-30">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                </div>
            </div>
        </x-slot:search>

        <x-slot:actions>
            <a href="javascript:void(0)" class="hhr-btn hhr-btn-excel" title="Export Excel">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3 -3v-1m-4-4-4 4m0 0-4-4m4 4V4"/></svg>
            </a>
            <button type="button" onclick="window.print()" class="hhr-btn opacity-60 hover:opacity-100" title="Cetak">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-2 4H8v-7h8v7Z"/></svg>
            </button>
            <button type="button" @click.prevent="allOpen = !allOpen; $dispatch('toggle-all', { state: allOpen })" class="hhr-btn opacity-60 hover:opacity-100" :title="allOpen ? 'Tutup Semua' : 'Buka Semua'">
                <svg x-show="!allOpen" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/></svg>
                <svg x-show="allOpen" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25"/></svg>
            </button>
        </x-slot:actions>
    </x-hhr-toolbar>

    {{-- TOTAL FLOATING GLASS RIBBON --}}
    @php 
        $isProdusenSimplified = $isProdusenOnlyMode ?? false;
        $globalPerc = ($totals['titip'] > 0) ? round(($totals['laku'] / $totals['titip']) * 100, 1) : 0;
        $countLabel = $isProdusenSimplified ? 'Produk' : 'Produsen';
        $countValue = $groupedData->count();
    @endphp
    <div class="glass-pill py-1.5 px-5 sm:px-6 flex items-center justify-center gap-4 sm:gap-6 whitespace-nowrap overflow-x-auto no-scrollbar shadow-2xl transition-all duration-500 hover:bg-slate-900/60 relative">
        <div class="flex items-center gap-6 sm:gap-8 font-mono-numbers">
            {{-- Count --}}
            <div class="flex flex-col gap-0.5 text-center">
                <span class="metric-label-xs">{{ $countLabel }}</span>
                <span class="text-xs font-bold text-violet-400 tracking-tight">{{ $countValue }}</span>
            </div>
            <div class="w-px h-6 bg-white/10 hidden sm:block"></div>
            {{-- Titip --}}
            <div class="flex flex-col gap-0.5 text-center">
                <span class="metric-label-xs">Titipan</span>
                <span class="text-xs font-bold text-blue-400 opacity-80 tracking-tight">{{ alignUang($totals['titip'], false) }}</span>
            </div>
            {{-- Laku --}}
            <div class="flex flex-col gap-0.5 text-center">
                <span class="metric-label-xs">Terjual</span>
                <span class="text-xs font-bold text-emerald-400 tracking-tight">{{ alignUang($totals['laku'], false) }}</span>
            </div>
            {{-- Efficiency with inline bar --}}
            <div class="flex flex-col gap-1 text-center min-w-[60px]">
                <span class="metric-label-xs">Efisiensi</span>
                <span class="text-xs font-bold tracking-tight" style="color: hsl({{ $globalPerc * 1.4 }}, 100%, 50%) !important;">{{ $globalPerc }}%</span>
                <div class="w-full h-1 rounded-full bg-white/10 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700" style="width: {{ min($globalPerc, 100) }}%; background: hsl({{ $globalPerc * 1.4 }}, 100%, 50%);"></div>
                </div>
            </div>
            <div class="w-px h-6 bg-white/10 hidden sm:block"></div>
            {{-- Omset --}}
            <div class="flex flex-col gap-0.5 text-center">
                <span class="metric-label-xs">Omset Bruto</span>
                <span class="text-xs font-bold tracking-tight text-amber-400">{{ alignUang($totals['omset'], false) }}</span>
            </div>
        </div>
        
        <div class="absolute right-4 sm:right-8 flex items-center gap-3">
            <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
            <div class="text-[9px] font-mono opacity-20 uppercase tracking-[0.2em] hidden lg:block">
                {{ $mode === 'tanggal' ? 'LIVE' : 'v3.0' }}
            </div>
        </div>
    </div>

    {{-- DATA GROUPS (Two options: PRODUSEN SIMPLIFIED vs ADMIN NESTED) --}}
    @php 
        $isProdusenSimplified = $isProdusenOnlyMode ?? false;
 @endphp
    
    @if($isProdusenSimplified)
    {{-- ============================================ --}}
    {{-- PRODUSEN SIMPLIFIED: FLAT PRODUCT VIEW --}}
    {{-- ============================================ --}}
    <div class="space-y-2 pb-12" id="reportContainer">
        @php $produkNo = 0; @endphp
        @forelse($groupedData as $produkName => $data)
        @php 
            $produkNo++;
            $summary = $data['summary'];
            $perc = ($summary['titip'] > 0) ? round(($summary['laku'] / $summary['titip']) * 100, 1) : 0;
            $hariJualan = $summary['hari_jualan'] ?? 0;
            $isDaily = ($mode === 'tanggal');
            $isCompact = in_array($mode, ['nama', 'tahunan']);
            
            // Get details for this product based on mode
            if ($isDaily) {
                $productDetails = $data['details'] ?? collect();
            } else {
                $productDetails = $producerDetailData->filter(fn($item) => ($item->produk_nama ?? '') === $produkName)->sortBy('tgl');
            }
        @endphp 
        
        <div class="group-box glass-panel rounded-xl overflow-hidden" x-data="{ open: {{ $isDaily ? 'true' : 'false' }} }" @toggle-all.window="open = $event.detail.state">
            <div @click="open = !open" class="box-header flex flex-col sm:flex-row sm:items-center justify-between py-2.5 px-4 cursor-pointer hover:bg-white/5 transition-all border-b border-white/5">
                <div class="flex items-center gap-3">
                    <div class="transition-transform duration-300 opacity-30" :class="open ? 'rotate-90' : ''">
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </div>
                    <span class="num-badge num-badge-product">{{ $produkNo }}</span>
                    <h3 class="font-bold text-[10px] group-title select-none opacity-90 capitalize tracking-wide">
                        {{ ucwords(strtolower($produkName)) }}
                    </h3>
                    @if(!$isDaily)
                        <span class="font-mono text-[8px] opacity-20 font-normal bg-white/5 px-1.5 py-0.5 rounded">{{ $hariJualan }}D</span>
                    @endif
                </div>
                
                <div class="mobile-metric-bar font-mono-numbers mt-1.5 sm:mt-0 flex items-center gap-1.5">
                    <div class="metric-capsule pill-onyx"><span class="metric-label-xs">T</span> <span>{{ alignUang($summary['titip'], false) }}</span></div>
                    <div class="metric-capsule pill-onyx"><span class="metric-label-xs">L</span> <span class="font-bold text-emerald-400">{{ alignUang($summary['laku'], false) }}</span></div>
                    <div class="metric-capsule font-bold min-w-[40px] justify-center" style="color: hsl({{ $perc * 1.4 }}, 100%, 50%) !important; background: hsla({{ $perc * 1.4 }}, 100%, 50%, 0.08); border-color: hsla({{ $perc * 1.4 }}, 100%, 50%, 0.2);">{{ $perc }}%</div>
                    <div class="metric-capsule pill-onyx hidden sm:inline-flex"><span class="metric-label-xs">Rp</span> <span class="text-amber-400 opacity-80">{{ alignUang($summary['omset'], false) }}</span></div>
                </div>
            </div>

            <div x-show="open" x-collapse>
                <div class="bg-black/20">
                    @if($productDetails->isNotEmpty())
                        @include('admin.reports.producer-sales-table', [
                            'items' => $productDetails, 
                            'nested' => false,
                            'isProdusenPedagangMode' => $isProdusenPedagangMode
                        ])
                    @else
                        <div class="p-4 text-center text-[10px] opacity-30 italic">Tidak ada detail</div>
                    @endif
                </div>
            </div>
        </div>
        @empty
            <div class="box p-16 text-center opacity-30 italic text-[11px] border-none shadow-none bg-transparent letter-spacing-widest">
                <div class="editorial-title text-lg mb-2">Hening...</div>
                Data tidak ditemukan di semesta ini.
            </div>
        @endforelse
    </div>
    
    @else
    {{-- ============================================ --}}
    {{-- ADMIN/PENGURUS: THREE-COLUMN NESTED VIEW --}}
    {{-- ============================================ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 items-start pb-12" id="reportContainer">
        @php $produsenNo = 0; @endphp
        @forelse($groupedData as $groupName => $data)
        @php 
            $produsenNo++;
            $summary = $data['summary'];
            $perc = ($summary['titip'] > 0) ? round(($summary['laku'] / $summary['titip']) * 100, 1) : 0;
            $hariJualan = $summary['hari_jualan'] ?? 0;
            $isDaily = ($mode === 'tanggal');
        @endphp 

        {{-- MODE TAHUNAN ATAU BULANAN NESTED (TRIPLE COLLAPSE) --}}
        @if(($mode === 'tahunan' || $mode === 'nama') && !$selectedProdusen)
            <div class="group-box glass-panel rounded-xl overflow-hidden" x-data="{ openP: false }" @toggle-all.window="openP = $event.detail.state">
                <div @click="openP = !openP" class="box-header flex flex-col sm:flex-row sm:items-center justify-between py-2.5 px-4 cursor-pointer hover:bg-white/5 transition-all border-b border-white/5">
                    <div class="flex items-center gap-3">
                        <div class="transition-transform duration-300 opacity-30" :class="openP ? 'rotate-90' : ''">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                        </div>
                        <span class="num-badge num-badge-producer">{{ $produsenNo }}</span>
                        <h3 class="font-bold text-[10px] group-title capitalize tracking-wide opacity-90">{{ ucwords(strtolower($groupName)) }}</h3>
                        <span class="font-mono text-[8px] opacity-20 font-normal bg-white/5 px-1.5 py-0.5 rounded">{{ $hariJualan }}D</span>
                    </div>
                    
                    <div class="mobile-metric-bar font-mono-numbers mt-1.5 sm:mt-0 flex items-center gap-1.5">
                        <div class="metric-capsule pill-onyx"><span class="metric-label-xs">T</span> <span>{{ alignUang($summary['titip'], false) }}</span></div>
                        <div class="metric-capsule pill-onyx"><span class="metric-label-xs">L</span> <span class="font-bold text-emerald-400">{{ alignUang($summary['laku'], false) }}</span></div>
                        <div class="metric-capsule pill-onyx font-bold min-w-[40px] justify-center" style="color: hsl({{ $perc * 1.4 }}, 100%, 50%) !important;">{{ $perc }}%</div>
                        <div class="metric-capsule pill-onyx hidden sm:inline-flex"><span class="metric-label-xs">Rp</span> <span class="text-amber-400 opacity-80">{{ alignUang($summary['omset'], false) }}</span></div>
                    </div>
                </div>

                <div x-show="openP" x-collapse class="pl-3 sm:pl-8 border-l-2 border-emerald-500/20 ml-4 pb-3">
                    @php $produkNo = 0; @endphp
                    @foreach($data['products'] as $pName => $pData)
                    @php 
                        $produkNo++;
                        $pSummary = $pData['summary'];
                        $pPerc = ($pSummary['titip'] > 0) ? round(($pSummary['laku'] / $pSummary['titip']) * 100, 1) : 0;
                    @endphp
                    <div class="product-group mt-2 mr-3" x-data="{ openPr: false }" @toggle-all.window="openPr = $event.detail.state">
                        <div @click="openPr = !openPr" class="flex flex-col sm:flex-row sm:items-center justify-between py-1.5 px-3 cursor-pointer bg-white/[0.03] hover:bg-white/[0.07] rounded-lg transition-all border border-white/[0.06]">
                            <div class="flex items-center gap-2">
                                <span class="num-badge num-badge-product">{{ $produkNo }}</span>
                                <div class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background: hsl({{ $pPerc * 1.4 }}, 100%, 50%);"></div>
                                <span class="text-[9px] font-bold opacity-70 capitalize tracking-wider">{{ ucwords(strtolower($pName)) }}</span>
                                <span class="font-mono text-[7px] opacity-20">[{{ $pSummary['hari_jualan'] }}D]</span>
                            </div>
                            <div class="mobile-metric-bar font-mono-numbers mt-1 sm:mt-0 flex items-center gap-1.5">
                                <div class="metric-capsule"><span class="metric-label-xs">L</span> <span class="opacity-80">{{ alignUang($pSummary['laku'], false) }}</span></div>
                                <div class="metric-capsule pill-onyx font-bold border-transparent text-[9px]" style="color: hsl({{ $pPerc * 1.4 }}, 100%, 50%) !important;">{{ $pPerc }}%</div>
                            </div>
                        </div>
                        <div x-show="openPr" x-collapse>
                            <div class="mt-1 bg-black/20 rounded-lg overflow-hidden border border-white/5 mx-1">
                                @include('admin.reports.producer-sales-table', ['items' => $pData['details'], 'nested' => true])
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        {{-- MODE STANDAR (HARIAN / RIWAYAT / TAHUNAN FILTERED / RANGE) --}}
        @else
            <div class="group-box glass-panel rounded-xl overflow-hidden" x-data="{ open: {{ $isDaily ? 'true' : 'false' }} }" @toggle-all.window="open = $event.detail.state">
                <div @click="open = !open" class="box-header flex flex-col sm:flex-row sm:items-center justify-between py-2.5 px-4 cursor-pointer hover:bg-white/5 transition-all border-b border-white/5">
                    <div class="flex items-center gap-3">
                        <div class="transition-transform duration-300 opacity-30" :class="open ? 'rotate-90' : ''">
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                        </div>
                        <span class="num-badge num-badge-producer">{{ $produsenNo }}</span>
                        <h3 class="font-bold text-[10px] group-title select-none opacity-90 capitalize tracking-wide">
                            {{ ucwords(strtolower($groupName)) }}
                        </h3>
                        @if(!$isDaily)
                            <span class="font-mono text-[8px] opacity-20 font-normal bg-white/5 px-1.5 py-0.5 rounded">{{ $hariJualan }}D</span>
                        @endif
                        @if($isDaily)
                            @include('admin.components.btn-nota-produsen', ['produsenName' => $groupName, 'date' => $selectedDate])
                        @endif
                    </div>
                    
                    <div class="mobile-metric-bar font-mono-numbers mt-1.5 sm:mt-0 flex items-center gap-1.5">
                        <div class="metric-capsule pill-onyx"><span class="metric-label-xs">T</span> <span>{{ alignUang($summary['titip'], false) }}</span></div>
                        <div class="metric-capsule pill-onyx"><span class="metric-label-xs">L</span> <span class="font-bold text-emerald-400">{{ alignUang($summary['laku'], false) }}</span></div>
                        <div class="metric-capsule font-bold min-w-[40px] justify-center" style="color: hsl({{ $perc * 1.4 }}, 100%, 50%) !important; background: hsla({{ $perc * 1.4 }}, 100%, 50%, 0.08); border-color: hsla({{ $perc * 1.4 }}, 100%, 50%, 0.2);">{{ $perc }}%</div>
                        <div class="metric-capsule pill-onyx hidden sm:inline-flex"><span class="metric-label-xs">Rp</span> <span class="text-amber-400 opacity-80">{{ alignUang($summary['omset'], false) }}</span></div>
                    </div>
                </div>

                <div x-show="open" x-collapse>
                    <div class="bg-black/20">
                        @include('admin.reports.producer-sales-table', ['items' => $data['details'], 'nested' => false])
                    </div>
                </div>
            </div>
        @endif
        @empty
            <div class="box p-16 text-center opacity-30 italic text-[11px] border-none shadow-none bg-transparent letter-spacing-widest">
                <div class="editorial-title text-lg mb-2">Hening...</div>
                Data tidak ditemukan di semesta ini.
            </div>
        @endforelse
    </div>
    @endif
</div>

<script>
    // LIVE SEARCH LOGIC - ULTRALIGHT
    document.getElementById('liveSearch').addEventListener('input', function() {
        let keyword = this.value.toLowerCase();
        let boxes = document.querySelectorAll('.group-box');

        boxes.forEach(box => {
            let groupTitle = box.querySelector('.group-title').textContent.toLowerCase();
            let items = box.querySelectorAll('.item-row');
            let hasVisibleItem = false;

            if (keyword === '' || groupTitle.includes(keyword)) {
                box.style.display = '';
                items.forEach(i => i.style.display = '');
                return;
            }

            items.forEach(item => {
                let name = item.querySelector('.item-name')?.textContent.toLowerCase() || '';
                let time = item.querySelector('.row-time')?.textContent.toLowerCase() || '';
                if (name.includes(keyword) || time.includes(keyword)) {
                    item.style.display = '';
                    hasVisibleItem = true;
                } else {
                    item.style.display = 'none';
                }
            });
            box.style.display = (keyword !== '' && !hasVisibleItem) ? 'none' : '';
        });
    });

    // FITUR DRILL DOWN
    function goToBulanan(bulan, tahun) {
        let form = document.querySelector('form.hhr-toolbar');
        if (!form) return;
        
        let modeSelect = form.querySelector('[name="mode"]');
        if (modeSelect) modeSelect.value = 'nama';
        
        let monthInput = form.querySelector('[name="month"]');
        if(monthInput) {
            monthInput.value = bulan.toString().padStart(2, '0');
        } else {
            monthInput = document.createElement('input');
            monthInput.type = 'hidden';
            monthInput.name = 'month';
            monthInput.value = bulan.toString().padStart(2, '0');
            form.appendChild(monthInput);
        }
        
        let yearInput = form.querySelector('[name="year"]');
        if(yearInput) {
            yearInput.value = tahun;
        } else {
            yearInput = document.createElement('input');
            yearInput.type = 'hidden';
            yearInput.name = 'year';
            yearInput.value = tahun;
            form.appendChild(yearInput);
        }
        
        form.submit();
    }

    function goToHarian(tanggal) {
        let form = document.querySelector('form.hhr-toolbar');
        if (!form) return;

        let modeSelect = form.querySelector('[name="mode"]');
        if (modeSelect) modeSelect.value = 'tanggal';
        
        let dateInput = form.querySelector('input[name="tanggal"]');
        if(!dateInput) {
            dateInput = document.createElement('input');
            dateInput.type = 'hidden';
            dateInput.name = 'tanggal';
            form.appendChild(dateInput);
        }
        dateInput.value = tanggal;
        
        form.submit();
    }
</script>