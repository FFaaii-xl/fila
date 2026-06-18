@include('admin.reports.report-style')

<div class="space-y-5">
    {{-- Enhanced Toolbar with better spacing --}}
    <x-hhr-toolbar>
        <x-slot:filters>
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            
            {{-- Group: Core Filters --}}
            <div class="hhr-group">
                <span class="hhr-label-ghost hidden sm:inline">Mode</span>
                <select name="mode" class="form-select" onchange="this.form.submit()">
                    <option value="tanggal" {{ $mode == 'tanggal' ? 'selected' : '' }}>Harian</option>
                    <option value="nama" {{ $mode == 'nama' ? 'selected' : '' }}>Bulanan</option>
                    <option value="tahunan" {{ $mode == 'tahunan' ? 'selected' : '' }}>Tahunan</option>
                    <option value="range" {{ $mode == 'range' ? 'selected' : '' }}>Range Tanggal</option>
                </select>
            </div>

            <div class="hhr-group">
                @if($mode === 'tanggal')
                    <span class="hhr-label-ghost hidden sm:inline">Tgl</span>
                    <input type="date" name="tanggal" value="{{ $selectedDate }}" class="form-input" onchange="this.form.submit()">
                @elseif($mode === 'range')
                    <span class="hhr-label-ghost hidden sm:inline text-[10px]">Mulai</span>
                    <input type="date" name="date_start" value="{{ $dateStart }}" class="form-input" onchange="this.form.submit()">
                    <span class="hhr-label-ghost hidden sm:inline text-[10px]">Sampai</span>
                    <input type="date" name="date_end" value="{{ $dateEnd }}" class="form-input" onchange="this.form.submit()">
                @endif
            </div>

            {{-- Pedagang dropdown: Only show for Admin/Pengurus --}}
            @if($mode !== 'tanggal' && in_array(auth()->user()->owner_type, ['Admin', 'Pengurus']))
            <div class="hhr-group">
                <select name="pedagang_id" class="form-select" onchange="this.form.submit()" style="max-width: 140px;">
                    <option value="">Semua Pedagang</option>
                    @foreach($pedagangList as $p)
                        <option value="{{ $p->id }}" {{ $pedagangId == $p->id ? 'selected' : '' }}>{{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($mode === 'nama' || $mode === 'tahunan')
            <div class="hhr-group">
                @if($mode === 'nama')
                <select name="month" class="form-select" onchange="this.form.submit()">
                    @foreach(range(1,12) as $m)
                        <option value="{{ sprintf('%02d', $m) }}" {{ $month == $m ? 'selected' : '' }}>{{ date('M', mktime(0,0,0,$m,1)) }}</option>
                    @endforeach
                </select>
                @endif

                <select name="year" class="form-select" onchange="this.form.submit()">
                    @for($y=date('Y'); $y>=2023; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            @endif
        </x-slot:filters>

        <x-slot:search>
            <div class="relative">
                <input type="text" id="liveSearch" placeholder="Cari nama atau tanggal..." class="form-input w-full pl-8" style="padding-left: 1.8rem !important;">
                <div class="absolute left-2.5 top-1/2 -translate-y-1/2 opacity-30">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                </div>
            </div>
        </x-slot:search>

        <x-slot:actions>
            <a href="javascript:void(0)" class="hhr-btn hhr-btn-excel" title="Export Excel">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3 -3v-1m-4-4-4 4m0 0-4-4m4 4V4"/></svg>
                <span class="hidden md:inline">Export</span>
            </a>
            <button type="button" onclick="window.print()" class="hhr-btn opacity-60 hover:opacity-100 hover:bg-white/10" title="Cetak Laporan">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-2 4H8v-7h8v7Z"/></svg>
            </button>
        </x-slot:actions>
    </x-hhr-toolbar>

    {{-- Enhanced Alert Box with better styling --}}
    @if($mode === 'tanggal' && isset($notReported) && $notReported->isNotEmpty())
    <div class="bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 rounded-lg p-4 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 mt-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-amber-500"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-amber-700 dark:text-amber-400 text-sm">⚠️ BELUM LAPORAN:</p>
                <p class="mt-1 text-sm text-amber-600 dark:text-amber-300/80 leading-relaxed">
                    @foreach($notReported as $nama)
                        <span class="inline-block bg-amber-100 dark:bg-amber-800/40 px-2 py-0.5 rounded text-xs mr-1 mb-1">{{ $nama }}</span>
                    @endforeach
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Enhanced Table Container with better shadows and borders --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700/50 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-list" id="reportTable">
                <thead class="bg-zinc-50 dark:bg-zinc-800/80 border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="text-center font-semibold" style="width: 40px;">No</th>
                        <th class="font-semibold" style="width: 150px;">
                            @php
                                $headerTitle = isset($isPedagangProductMode) && $isPedagangProductMode ? 'Produk' : ($mode === 'nama' ? 'Waktu' : ($mode === 'tahunan' ? 'Bulan' : 'Pedagang'));
                                $headerSort = in_array($mode, ['tanggal']) ? 'nama' : ($mode == 'nama' ? 'tgl' : 'bln');
                                if ($mode === 'range') {
                                    if ($rangeType === 'hari') { $headerTitle = 'Tanggal'; $headerSort = 'tgl'; }
                                    elseif ($rangeType === 'bulan') { $headerTitle = 'Bulan'; $headerSort = 'bln'; }
                                    else { $headerTitle = 'Tahun'; $headerSort = 'thn'; }
                                }
                            @endphp
                            {{ sortableHeader($headerTitle, $headerSort) }}
                        </th>
                        <th class="text-center font-semibold">{{ sortableHeader('Prod', 'total_produk') }}</th>
                        <th class="text-center font-semibold">{{ sortableHeader('Titip', 'total_titip') }}</th>
                        <th class="text-center font-semibold">{{ sortableHeader('Laku', 'total_laku') }}</th>
                        <th class="text-center font-semibold" style="width: 65px;">{{ sortableHeader('%', 'persen_laku') }}</th>
                        <th class="text-right font-semibold">{{ sortableHeader('Modal', 'total_modal_final') }}</th>
                        <th class="text-right font-semibold">{{ sortableHeader('Kas', 'total_kas') }}</th>
                        <th class="text-right font-semibold">{{ sortableHeader('Tab', 'total_tab_final') }}</th>
                        <th class="text-right font-semibold bg-blue-50/50 dark:bg-blue-900/20">{{ sortableHeader('Setoran', 'total_setoran') }}</th>
                        <th class="text-right font-semibold">{{ sortableHeader('Omset', 'total_omset') }}</th>
                        <th class="text-right font-semibold">{{ sortableHeader('Laba', 'total_laba') }}</th>
                        @if($mode === 'tahunan' || $mode === 'nama')
                        <th class="text-center font-semibold" style="width: 60px;">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @php $no = 1; @endphp
                    @forelse($reportData as $row)
                    <tr class="report-row hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="text-center row-no text-zinc-500 dark:text-zinc-400">{{ $no++ }}</td>
                        <td class="font-semibold row-name whitespace-nowrap overflow-hidden text-ellipsis text-zinc-800 dark:text-zinc-200" style="max-width: 150px;">
                            @if(in_array($mode, ['tanggal'])) 
                                {{ $row->nama }} 
                            @elseif($mode == 'nama') 
                                {{ date('d M Y', strtotime($row->tgl)) }} 
                            @elseif($mode == 'tahunan') 
                                {{ date('F', mktime(0,0,0,$row->bln,1)) }} 
                            @elseif($mode == 'range')
                                @if($rangeType === 'hari')
                                    {{ date('d M Y', strtotime($row->tgl)) }}
                                @elseif($rangeType === 'bulan')
                                    {{ date('F Y', mktime(0,0,0,$row->bln,1,$row->thn)) }}
                                @else
                                    {{ $row->thn }}
                                @endif
                            @endif
                        </td>
                        <td class="text-center text-sm text-zinc-600 dark:text-zinc-400">{{ (isset($isPedagangProductMode) && $isPedagangProductMode) ? '-' : $row->total_produk }}</td>
                        <td class="text-center text-zinc-700 dark:text-zinc-300">{{ alignUang($row->total_titip, false) }}</td>
                        <td class="text-center text-zinc-700 dark:text-zinc-300">{{ alignUang($row->total_laku, false) }}</td>
                        <td class="text-center font-bold text-white" style="background-color: {{ getHeatmapColor($row->persen_laku) }};">
                            {{ round($row->persen_laku, 1) }}%
                        </td>
                        <td class="text-right text-zinc-700 dark:text-zinc-300">{{ alignUang($row->total_modal_final, false) }}</td>
                        <td class="text-right text-sm text-zinc-500 dark:text-zinc-400">{{ (isset($isPedagangProductMode) && $isPedagangProductMode) ? '-' : alignUang($row->total_kas, false) }}</td>
                        <td class="text-right text-sm text-zinc-500 dark:text-zinc-400">{{ (isset($isPedagangProductMode) && $isPedagangProductMode) ? '-' : alignUang($row->total_tab_final, false) }}</td>
                        <td class="text-right font-bold text-blue-600 dark:text-blue-400 bg-blue-50/50 dark:bg-blue-900/20">{{ (isset($isPedagangProductMode) && $isPedagangProductMode) ? '-' : alignUang($row->total_setoran, false) }}</td>
                        <td class="text-right text-zinc-700 dark:text-zinc-300">{{ alignUang($row->total_omset, false) }}</td>
                        <td class="text-right font-semibold text-emerald-600 dark:text-emerald-400">{{ alignUang($row->total_laba, false) }}</td>
                        @if($mode === 'tahunan' || $mode === 'nama')
                        <td class="text-center">
                            @if($mode === 'tahunan')
                            <a href="javascript:void(0)" onclick="goToBulanan('{{ $row->bln }}', '{{ $year }}')" class="inline-flex items-center justify-center p-1.5 bg-blue-500/10 text-blue-500 hover:bg-blue-500/20 rounded-md transition-colors" title="Lihat Detail Bulan">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            @elseif($mode === 'nama')
                            <a href="javascript:void(0)" onclick="goToHarian('{{ $row->tgl }}')" class="inline-flex items-center justify-center p-1.5 bg-blue-500/10 text-blue-500 hover:bg-blue-500/20 rounded-md transition-colors" title="Lihat Detail Hari">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="12" class="text-center py-12 text-zinc-400 dark:text-zinc-500 italic">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mx-auto mb-2 opacity-50"><path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-6l-2-2H5a2 2 0 0 0-2 2z"/></svg>
                        Data tidak ditemukan.
                    </td></tr>
                    @endforelse
                </tbody>
                @if(isset($reportData) && $reportData->isNotEmpty())
                <tfoot>
                    <tr class="bg-gradient-to-r from-zinc-100 to-zinc-50 dark:from-zinc-800 dark:to-zinc-800/80 font-bold border-t-2 border-zinc-300 dark:border-zinc-600">
                        <td colspan="2" class="text-center uppercase text-xs text-zinc-600 dark:text-zinc-400 tracking-wide">Total Seluruh</td>
                        <td class="text-center text-xs text-zinc-600 dark:text-zinc-400">{{ $totals['produk'] ?? 0 }}</td>
                        <td class="text-center text-zinc-700 dark:text-zinc-300">{{ alignUang($totals['titip'] ?? 0, false) }}</td>
                        <td class="text-center text-zinc-700 dark:text-zinc-300">{{ alignUang($totals['laku'] ?? 0, false) }}</td>
                        <td class="text-center text-white" style="background-color: {{ getHeatmapColor(($totals['titip'] ?? 0) > 0 ? (($totals['laku'] ?? 0)/($totals['titip'] ?? 1))*100 : 0) }};">
                            {{ ($totals['titip'] ?? 0) > 0 ? round((($totals['laku'] ?? 0)/($totals['titip'] ?? 1))*100, 1) : 0 }}%
                        </td>
                        <td class="text-right text-zinc-700 dark:text-zinc-300">{{ alignUang($totals['modal'] ?? 0, false) }}</td>
                        <td class="text-right text-xs text-zinc-600 dark:text-zinc-400">{{ alignUang($totals['kas'] ?? 0, false) }}</td>
                        <td class="text-right text-xs text-zinc-600 dark:text-zinc-400">{{ alignUang($totals['tab'] ?? 0, false) }}</td>
                        <td class="text-right text-blue-700 dark:text-blue-400 bg-blue-100/50 dark:bg-blue-900/30">{{ alignUang($totals['setoran'] ?? 0, false) }}</td>
                        <td class="text-right text-zinc-700 dark:text-zinc-300">{{ alignUang($totals['omset'] ?? 0, false) }}</td>
                        <td class="text-right font-bold text-emerald-600 dark:text-emerald-400">{{ alignUang($totals['laba'] ?? 0, false) }}</td>
                        @if($mode === 'tahunan' || $mode === 'nama')
                        <td></td>
                        @endif
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<script>
    // FITUR LIVE SEARCH
    document.getElementById('liveSearch').addEventListener('input', function() {
        let keyword = this.value.toLowerCase();
        let rows = document.querySelectorAll('.report-row');
        let visibleNo = 1;

        rows.forEach(row => {
            // Ambil teks dari kolom Nama/Tanggal (Kolom ke-2)
            let nameText = row.querySelector('.row-name').textContent.toLowerCase();
            
            if (nameText.includes(keyword)) {
                row.style.display = '';
                // Update nomor urut agar tetap berurutan 1, 2, 3...
                row.querySelector('.row-no').textContent = visibleNo++;
            } else {
                row.style.display = 'none';
            }
        });
    });

    // FITUR DRILL DOWN
    function goToBulanan(bulan, tahun) {
        let form = document.getElementById('reportForm');
        form.querySelector('[name="mode"]').value = 'nama';
        
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
        let form = document.getElementById('reportForm');
        form.querySelector('[name="mode"]').value = 'tanggal';
        
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