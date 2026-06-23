<div class="fi-page">
    {{-- Header with sticky filter bar --}}
    <div class="sticky top-0 z-10 bg-white dark:bg-slate-900 border-b shadow-sm">
        <div class="px-4 py-4">
            <h1 class="fi-title text-2xl font-bold text-gray-900 dark:text-white">Laporan Tabungan</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                @if(isset($periods[$periodIdx]))
                    Periode: {{ $periods[$periodIdx]['label'] }}
                @endif
                @if($ownerType)
                    | Tipe: {{ $ownerType }}
                @endif
            </p>
        </div>
        
        {{-- Filter Controls --}}
        <div class="px-4 pb-4">
            <form method="GET" class="grid grid-cols-2 md:grid-cols-5 gap-3">
                {{-- Mode --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Mode</label>
                    <select name="mode" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
                        <option value="per_bulan" {{ $mode == 'per_bulan' ? 'selected' : '' }}>Per Bulan</option>
                        <option value="per_tanggal" {{ $mode == 'per_tanggal' ? 'selected' : '' }}>Per Tanggal</option>
                        <option value="range" {{ $mode == 'range' ? 'selected' : '' }}>Total</option>
                    </select>
                </div>
                
                {{-- Owner Type --}}
                @if($isAdminOrPengurus)
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Owner</label>
                    <select name="owner_type" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
                        <option value="Pedagang" {{ $ownerType == 'Pedagang' ? 'selected' : '' }}>Pedagang</option>
                        <option value="Produsen" {{ $ownerType == 'Produsen' ? 'selected' : '' }}>Produsen</option>
                    </select>
                </div>
                @endif
                
                {{-- Period --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Periode</label>
                    <select name="period_idx" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
                        @foreach($periods as $idx => $p)
                            <option value="{{ $idx }}" {{ $periodIdx === $idx ? 'selected' : '' }}>
                                {{ $p['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                {{-- Month (Per Tanggal mode) --}}
                @if($mode === 'per_tanggal')
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Bulan</label>
                    <select name="month" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg">
                        @foreach($availableMonths as $val => $label)
                            <option value="{{ $val }}" {{ $selectedMonth == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                {{-- Actions --}}
                <div class="flex items-end gap-2">
                    <a href="{{ request()->fullUrlWithQuery(['format_k' => $formatK == '1' ? '0' : '1']) }}" 
                       class="px-3 py-2 text-sm rounded-lg border {{ $formatK == '1' ? 'bg-primary-50 text-primary-700 border-primary-200' : 'bg-gray-50 text-gray-700 border-gray-200' }}">
                        F.K
                    </a>
                    <a href="{{ route('admin.tabungan.export', request()->query()) }}" 
                       class="px-3 py-2 text-sm rounded-lg bg-emerald-500 text-white hover:bg-emerald-600">
                        Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="px-4 py-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <p class="text-xs text-blue-600 dark:text-blue-400 font-medium">Jumlah Owner</p>
                <p class="text-xl font-bold text-blue-900 dark:text-blue-300">{{ count($owners) }}</p>
            </div>
            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4">
                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Total Tabungan</p>
                <p class="text-xl font-bold text-emerald-900 dark:text-emerald-300">
                    @if($formatK == '1')
                        {{ number_format($grandTotal / 1000, 1) }}K
                    @else
                        Rp {{ number_format($grandTotal, 0, ',', '.') }}
                    @endif
                </p>
            </div>
            @if($mode === 'per_tanggal')
            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
                <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">Bulan Ini</p>
                <p class="text-xl font-bold text-amber-900 dark:text-amber-300">
                    @if($formatK == '1')
                        {{ number_format($grandMonthTotal / 1000, 1) }}K
                    @else
                        Rp {{ number_format($grandMonthTotal, 0, ',', '.') }}
                    @endif
                </p>
            </div>
            @endif
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                <p class="text-xs text-purple-600 dark:text-purple-400 font-medium">Kolom Data</p>
                <p class="text-xl font-bold text-purple-900 dark:text-purple-300">{{ count($columnHeaders) }}</p>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="px-4 pb-6">
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="reportTable">
                <thead class="bg-gray-50 dark:bg-slate-800">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        @foreach($columnHeaders as $key => $label)
                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $label }}</th>
                        @endforeach
                        @if($mode === 'per_tanggal')
                            <th class="px-3 py-2 text-right text-xs font-medium text-primary-600 uppercase">Total<br><span class="font-normal opacity-60">Bulan</span></th>
                        @endif
                        <th class="px-3 py-2 text-right text-xs font-medium text-emerald-600 uppercase">Total<br><span class="font-normal opacity-60">Periode</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @php $no = 1; @endphp
                    @forelse($owners as $id => $name)
                        @if(isset($ownerTotals[$id]))
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 owner-row">
                                <td class="px-3 py-2 text-sm text-gray-400">{{ $no++ }}</td>
                                <td class="px-3 py-2 text-sm font-bold text-gray-900 dark:text-white owner-name">{{ $name }}</td>
                                @foreach($columnHeaders as $key => $label)
                                    <td class="px-2 py-2 text-sm text-right font-mono {{ isset($grid[$id][$key]) ? 'opacity-100' : 'opacity-30' }}">
                                        @if(isset($grid[$id][$key]))
                                            @if($formatK == '1')
                                                {{ number_format($grid[$id][$key] / 1000, 1) }}K
                                            @else
                                                {{ number_format($grid[$id][$key], 0, ',', '.') }}
                                            @endif
                                        @else
                                            0
                                        @endif
                                    </td>
                                @endforeach
                                @if($mode === 'per_tanggal')
                                    <td class="px-3 py-2 text-sm text-right font-bold font-mono text-primary-600">
                                        @if($formatK == '1')
                                            {{ number_format(($ownerMonthTotals[$id] ?? 0) / 1000, 1) }}K
                                        @else
                                            {{ number_format($ownerMonthTotals[$id] ?? 0, 0, ',', '.') }}
                                        @endif
                                    </td>
                                @endif
                                <td class="px-3 py-2 text-sm text-right font-bold font-mono text-emerald-600">
                                    @if($formatK == '1')
                                        {{ number_format($ownerTotals[$id] / 1000, 1) }}K
                                    @else
                                        {{ number_format($ownerTotals[$id], 0, ',', '.') }}
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="100" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada data tabungan untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($grandTotal > 0)
                <tfoot class="bg-gray-100 dark:bg-slate-800 font-bold">
                    <tr>
                        <td colspan="2" class="px-3 py-2 text-xs uppercase tracking-wider text-center">GRAND TOTAL</td>
                        @foreach($columnHeaders as $key => $label)
                            <td class="px-2 py-2 text-sm text-right font-mono">
                                @if($formatK == '1')
                                    {{ number_format(($colTotals[$key] ?? 0) / 1000, 1) }}K
                                @else
                                    {{ number_format($colTotals[$key] ?? 0, 0, ',', '.') }}
                                @endif
                            </td>
                        @endforeach
                        @if($mode === 'per_tanggal')
                            <td class="px-3 py-2 text-sm text-right font-mono text-primary-600">
                                @if($formatK == '1')
                                    {{ number_format($grandMonthTotal / 1000, 1) }}K
                                @else
                                    {{ number_format($grandMonthTotal, 0, ',', '.') }}
                                @endif
                            </td>
                        @endif
                        <td class="px-3 py-2 text-sm text-right font-mono text-emerald-600">
                            @if($formatK == '1')
                                {{ number_format($grandTotal / 1000, 1) }}K
                            @else
                                {{ number_format($grandTotal, 0, ',', '.') }}
                            @endif
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<script>
    // Search functionality
    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#reportTable tbody tr.owner-row');
        
        rows.forEach(row => {
            let nameCell = row.querySelector('.owner-name');
            if (nameCell) {
                let name = nameCell.textContent || nameCell.innerText;
                row.style.display = name.toLowerCase().includes(filter) ? "" : "none";
            }
        });
    });
</script>
