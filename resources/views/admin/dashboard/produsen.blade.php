@php
    $genderLabel = $summary['gender'] ?? '-';
    $statusLabel = $summary['status'] ?? 'Aktif';
    $pembulatanTerakhir = $summary['pembulatan'] ?? 0;
    $tabunganLabel = isset($summary['tabungan']) ? 'Rp ' . number_format($summary['tabungan'], 0, ',', '.') : '-';
    $tabunganRateLabel = isset($summary['tabungan_rate']) ? number_format($summary['tabungan_rate'], 0, ',', '.') : '-';
    $bundleLabel = (($produsen->bundle_ke ?? 0) > 0) ? 'B' . $produsen->bundle_ke : '-';
    
    // Heatmap data
    $heatmap = $heatmapData['data'] ?? [];
    $heatmapMax = $heatmapData['max_value'] ?? 1;
@endphp

<div class="space-y-4" x-data="{ profileVisible: false }" x-init="setTimeout(() => profileVisible = true, 50)">
    {{-- Profil & Status Akun - Compact --}}
    <x-moonshine::layout.box 
        class="!rounded-xl !border-white/10 !bg-gradient-to-br !from-[#1a1430] !to-[#100d1c] !p-4 shadow-xl shadow-black/20 relative overflow-hidden"
    >
        <div class="relative z-10" :class="profileVisible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'" class="transition-all duration-500 ease-out">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500/30 to-emerald-500/30 flex items-center justify-center">
                        <x-moonshine::icon icon="user-circle" size="6" class="text-white" />
                    </div>
                    <div>
                        <h2 class="text-lg font-black tracking-tight text-white">{{ $produsen->nama }}</h2>
                        <p class="text-[9px] font-mono uppercase tracking-[0.15em] text-white/35">Ringkasan Operasional</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <div class="group rounded-lg border border-emerald-500/20 bg-emerald-500/5 px-2.5 py-1.5 hover:bg-emerald-500/10 transition-all cursor-default flex items-center gap-2">
                        <x-moonshine::icon icon="check-circle" size="3" class="text-emerald-400 flex-shrink-0" />
                        <span class="text-[9px] font-black text-emerald-400/50">Status</span>
                        <span class="text-[11px] font-black text-emerald-400 bg-emerald-500/20 px-2 py-0.5 rounded-md shadow-[0_0_8px_rgba(52,211,153,0.3)]">{{ $statusLabel }}</span>
                    </div>
                    <div class="group rounded-lg border border-sky-500/20 bg-sky-500/5 px-2.5 py-1.5 hover:bg-sky-500/10 transition-all cursor-default flex items-center gap-2">
                        <x-moonshine::icon icon="user" size="3" class="text-sky-400 flex-shrink-0" />
                        <span class="text-[9px] font-black text-sky-400/50">Gender</span>
                        <span class="text-[11px] font-black text-sky-300 bg-sky-500/20 px-2 py-0.5 rounded-md shadow-[0_0_8px_rgba(56,189,248,0.3)]">{{ $genderLabel }}</span>
                    </div>
                    <div class="group rounded-lg border border-amber-500/20 bg-amber-500/5 px-2.5 py-1.5 hover:bg-amber-500/10 transition-all cursor-default flex items-center gap-2">
                        <x-moonshine::icon icon="currency-dollar" size="3" class="text-amber-400 flex-shrink-0" />
                        <span class="text-[9px] font-black text-amber-400/50">Bulatan</span>
                        <span class="text-[11px] font-mono font-black text-amber-300 bg-amber-500/20 px-2 py-0.5 rounded-md shadow-[0_0_8px_rgba(251,191,36,0.3)]">Rp {{ number_format($pembulatanTerakhir, 0, ',', '.') }}</span>
                    </div>
                    <div class="group rounded-lg border border-indigo-500/20 bg-indigo-500/5 px-2.5 py-1.5 hover:bg-indigo-500/10 transition-all cursor-default flex items-center gap-2">
                        <x-moonshine::icon icon="banknotes" size="3" class="text-indigo-400 flex-shrink-0" />
                        <span class="text-[9px] font-black text-indigo-400/50">Tabungan</span>
                        <span class="text-[11px] font-mono font-black text-indigo-300 bg-indigo-500/20 px-2 py-0.5 rounded-md shadow-[0_0_8px_rgba(99,102,241,0.3)]">{{ $tabunganLabel }}</span>
                    </div>
                    <div class="group rounded-lg border border-indigo-500/20 bg-indigo-500/5 px-2.5 py-1.5 hover:bg-indigo-500/10 transition-all cursor-default flex items-center gap-2">
                        <x-moonshine::icon icon="arrow-up-right" size="3" class="text-indigo-400 flex-shrink-0" />
                        <span class="text-[9px] font-black text-indigo-400/50">Rate</span>
                        <span class="text-[11px] font-mono font-black text-indigo-300 bg-indigo-500/20 px-2 py-0.5 rounded-md shadow-[0_0_8px_rgba(99,102,241,0.3)]">{{ $tabunganRateLabel }}</span>
                    </div>
                    <div class="group relative rounded-lg border border-violet-500/20 bg-violet-500/5 px-2.5 py-1.5 hover:bg-violet-500/10 transition-all cursor-default flex items-center gap-2" x-data="{ showTooltip: false }">
                        <x-moonshine::icon icon="cube" size="3" class="text-violet-400 flex-shrink-0" />
                        <span class="text-[9px] font-black text-violet-400/50">Kelompok</span>
                        <button 
                            type="button"
                            class="text-[11px] font-black text-violet-300 bg-violet-500/20 px-2 py-0.5 rounded-md shadow-[0_0_8px_rgba(139,92,246,0.3)] hover:bg-violet-500/30 transition-colors"
                            @mouseenter="showTooltip = true"
                            @mouseleave="showTooltip = false"
                            @focus="showTooltip = true"
                            @blur="showTooltip = false"
                        >{{ $bundleLabel }}</button>
                        <template x-teleport="body">
                            <div x-show="showTooltip" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 translate-y-1"
                                 class="fixed px-3 py-2 bg-gray-900 border border-violet-500/30 rounded-lg shadow-xl z-[9999] min-w-[180px]"
                                 style="left: 50%; transform: translateX(-50%);"
                                 @mouseenter="showTooltip = true"
                                 @mouseleave="showTooltip = false">
                                <div class="text-[9px] font-black uppercase tracking-[0.1em] text-violet-400 mb-2 flex items-center gap-1">
                                    <x-moonshine::icon icon="users" size="3" class="text-violet-400" />
                                    Anggota Kelompok {{ $produsen->bundle_ke }}
                                </div>
                                <div class="space-y-1">
                                    @foreach($bundleMembers as $member)
                                        <div class="flex items-center gap-2 text-[10px] {{ $member['is_current'] ? 'text-emerald-400 font-bold' : 'text-white/70' }}">
                                            @if($member['is_current'])
                                                <x-moonshine::icon icon="check" size="3" class="text-emerald-400" />
                                            @else
                                                <span class="w-3"></span>
                                            @endif
                                            <span>{{ $member['nama'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </x-moonshine::layout.box>

    {{-- Heatmap & Stats - Compact Row --}}
    @php
        $totalSales = collect($heatmap)->sum('value');
        $totalMerchants = collect($heatmap)->sum('merchants');
        $activeDays = collect($heatmap)->filter(fn($d) => $d['value'] > 0)->count();
        $avgSales = $activeDays > 0 ? round($totalSales / $activeDays) : 0;
    @endphp
    
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2" x-data="{ visible: false }" x-init="setTimeout(() => visible = true, 100)">
        <div class="relative group rounded-xl border border-emerald-500/30 bg-gradient-to-br from-emerald-950/60 to-emerald-900/30 p-3 hover:border-emerald-400/50 hover:scale-[1.01] transition-all cursor-pointer overflow-hidden"
             :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'" class="transition-all duration-300">
            <div class="flex items-center gap-2 mb-1">
                <x-moonshine::icon icon="chart-bar" size="4" class="text-emerald-400" />
                <span class="text-[9px] font-black uppercase tracking-[0.1em] text-emerald-400/70">Total</span>
            </div>
            <div class="text-xl font-black font-mono text-white tabular-nums" x-data="{ count: 0 }" x-init="
                setTimeout(() => {
                    const target = {{ $totalSales }};
                    const duration = 800;
                    const start = performance.now();
                    const animate = (now) => {
                        const progress = Math.min((now - start) / duration, 1);
                        count = Math.floor(progress * target);
                        if (progress < 1) requestAnimationFrame(animate);
                    };
                    requestAnimationFrame(animate);
                }, 200);
            " x-text="count.toLocaleString('id-ID')">0</div>
            <div class="text-[8px] font-mono text-emerald-400/50">Rata: {{ number_format($avgSales) }}/hari</div>
        </div>
        
        <div class="relative group rounded-xl border border-blue-500/30 bg-gradient-to-br from-blue-950/60 to-blue-900/30 p-3 hover:border-blue-400/50 hover:scale-[1.01] transition-all cursor-pointer overflow-hidden"
             :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'" class="transition-all duration-300" style="transition-delay: 50ms;">
            <div class="flex items-center gap-2 mb-1">
                <x-moonshine::icon icon="users" size="4" class="text-blue-400" />
                <span class="text-[9px] font-black uppercase tracking-[0.1em] text-blue-400/70">Pedagang</span>
            </div>
            <div class="text-xl font-black font-mono text-white tabular-nums">{{ number_format($totalMerchants) }}</div>
            <div class="text-[8px] font-mono text-blue-400/50">60 hari terakhir</div>
        </div>
        
        <div class="relative group rounded-xl border border-violet-500/30 bg-gradient-to-br from-violet-950/60 to-violet-900/30 p-3 hover:border-violet-400/50 hover:scale-[1.01] transition-all cursor-pointer overflow-hidden"
             :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'" class="transition-all duration-300" style="transition-delay: 100ms;">
            <div class="flex items-center gap-2 mb-1">
                <x-moonshine::icon icon="calendar" size="4" class="text-violet-400" />
                <span class="text-[9px] font-black uppercase tracking-[0.1em] text-violet-400/70">Hari Aktif</span>
            </div>
            <div class="text-xl font-black font-mono text-white tabular-nums">{{ number_format($activeDays) }}</div>
            <div class="text-[8px] font-mono text-violet-400/50">dari 60 hari</div>
        </div>
        
        <div class="relative group rounded-xl border border-amber-500/30 bg-gradient-to-br from-amber-950/60 to-amber-900/30 p-3 hover:border-amber-400/50 hover:scale-[1.01] transition-all cursor-pointer overflow-hidden"
             :class="visible ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-2'" class="transition-all duration-300" style="transition-delay: 150ms;">
            <div class="flex items-center gap-2 mb-1">
                <x-moonshine::icon icon="arrow-trending-up" size="4" class="text-amber-400" />
                <span class="text-[9px] font-black uppercase tracking-[0.1em] text-amber-400/70">Rata-rata</span>
            </div>
            <div class="text-xl font-black font-mono text-white tabular-nums">{{ number_format($avgSales) }}</div>
            <div class="text-[8px] font-mono text-amber-400/50">item/hari</div>
        </div>
    </div>

    {{-- Nota Preview (Full Nota Style) --}}
    @if(($showNotaToday ?? false) && isset($produsen))
    <x-moonshine::layout.box class="!rounded-xl !border-white/10 !bg-white !p-0 shadow-xl overflow-hidden">
        @include('admin.dashboard.partials.nota-preview')
    </x-moonshine::layout.box>
    @endif

    {{-- Daftar Produk --}}
    <x-moonshine::table :simple="false" :notfound="$productStats->isEmpty()" :translates="['notfound' => 'Belum ada produk untuk produsen ini.']">
        <x-slot:thead>
            <tr>
                <th colspan="6" class="!p-2">
                    <div class="flex items-center gap-2">
                        <x-moonshine::icon icon="cube" size="4" class="text-violet-400" />
                        <span class="text-[10px] font-black text-violet-400/70 uppercase tracking-wider">Daftar Produk</span>
                    </div>
                </th>
            </tr>
            <tr>
                <th>#</th>
                <th>Produk</th>
                <th class="text-right">Harga Jual</th>
                <th class="text-right">Harga Beli</th>
                <th class="text-right">Last.Prod</th>
                <th class="text-right">Perf(30h)</th>
            </tr>
        </x-slot:thead>
        <x-slot:tbody>
            @foreach($productStats as $row)
                <tr>
                    <td class="font-mono text-xs text-white/45">{{ $row['no'] }}</td>
                    <td class="font-bold">{{ $row['nama'] }}</td>
                    <td class="text-right font-mono text-emerald-300">{{ number_format($row['harga_jual'], 0, ',', '.') }}</td>
                    <td class="text-right font-mono text-white/60">{{ number_format($row['harga_beli'], 0, ',', '.') }}</td>
                    <td class="text-right">
                        <span class="inline-flex min-w-[86px] justify-center rounded-full border border-sky-500/20 bg-sky-500/10 px-2.5 py-1 font-mono text-[10px] font-bold tracking-[0.08em] text-sky-300">
                            {{ $row['last_titip_at'] }}
                        </span>
                    </td>
                    <td class="text-right">
                        <div class="inline-flex flex-col items-end gap-1">
                            <span class="rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.18em] {{ $row['perf'] >= 80 ? 'border-emerald-500/25 bg-emerald-500/10 text-emerald-300' : ($row['perf'] >= 50 ? 'border-amber-500/25 bg-amber-500/10 text-amber-300' : 'border-rose-500/25 bg-rose-500/10 text-rose-300') }}">{{ $row['perf'] }}%</span>
                            <div class="h-1.5 w-20 overflow-hidden rounded-full bg-white/5">
                                <div class="h-full rounded-full bg-gradient-to-r from-violet-500 via-emerald-400 to-cyan-300" style="width: {{ min($row['perf'], 100) }}%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-slot:tbody>
    </x-moonshine::table>

    {{-- Nota Popup Modal --}}
    <div 
        x-data="{ 
            showNotaModal: false, 
            notaUrl: '', 
            notaDate: '',
            notaLoading: false,
            openNota(date) {
                this.notaDate = date;
                this.notaUrl = '/admin/print-nota?date=' + date + '&filter_produsen={{ $produsen->id }}&iframe=1';
                this.showNotaModal = true;
            },
            closeNota() {
                this.showNotaModal = false;
                this.notaUrl = '';
            }
        }"
        @keydown.escape.window="showNotaModal = false"
    >
        <template x-teleport="body">
            <div 
                x-show="showNotaModal" 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 backdrop-blur-sm"
                style="display: none;"
                @click.self="closeNota()"
            >
                <div class="relative w-[95vw] h-[90vh] max-w-7xl bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col">
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-900 text-white border-b border-gray-700">
                        <div class="flex items-center gap-3">
                            <x-moonshine::icon icon="document-text" size="5" class="text-emerald-400" />
                            <div>
                                <h3 class="font-bold text-sm">Nota Preview</h3>
                                <p class="text-xs text-gray-400" x-text="notaDate ? new Date(notaDate).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : ''"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a 
                                x-bind:href="'/admin/print-nota?date=' + notaDate + '&filter_produsen={{ $produsen->id }}'" 
                                target="_blank"
                                class="px-3 py-1.5 text-xs font-bold rounded border border-emerald-500/30 bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30 transition-colors flex items-center gap-1.5"
                            >
                                <x-moonshine::icon icon="printer" size="3" />
                                Cetak
                            </a>
                            <button 
                                @click="closeNota()" 
                                class="p-2 rounded hover:bg-white/10 transition-colors"
                            >
                                <x-moonshine::icon icon="x-mark" size="4" />
                            </button>
                        </div>
                    </div>
                    {{-- Modal Content --}}
                    <div class="flex-1 overflow-auto bg-gray-100">
                        <iframe 
                            x-show="notaUrl"
                            x-bind:src="notaUrl"
                            class="w-full h-full min-h-[600px]"
                            style="border: none;"
                            title="Nota Preview"
                        ></iframe>
                    </div>
                </div>
            </div>
        </template>
    
    {{-- Toolbar & Daftar Transaksi Produsen --}}
    @php
        // Generate all dates for the period
        $allDates = [];
        $startDate = now()->subDays(89)->startOfDay();
        $endDate = now();
        
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $allDates[] = [
                'tanggal' => $current->toDateString(),
                'tanggal_label' => $current->format('d M Y'),
                'day_of_week' => $current->dayOfWeek,
            ];
            $current->addDay();
        }
        
        // Merge with actual transaction data
        $transactionsByDate = $transactions->keyBy('tanggal')->toArray();
        
        $fullTimeline = collect($allDates)->map(function($date) use ($transactionsByDate) {
            $tanggal = $date['tanggal'];
            $transaction = $transactionsByDate[$tanggal] ?? null;
            
            return [
                'tanggal' => $tanggal,
                'tanggal_label' => $date['tanggal_label'],
                'day_of_week' => $date['day_of_week'],
                'has_transaction' => $transaction !== null,
                'jumlah' => $transaction['jumlah'] ?? 0,
                'kemarin' => $transaction['kemarin'] ?? 0,
                'pembulatan' => $transaction['pembulatan'] ?? 0,
                'kas' => $transaction['kas'] ?? 0,
                'penjualan' => $transaction['penjualan'] ?? 0,
                'nota_date' => $tanggal,
            ];
        })->values()->toArray();
        
        $transactionsJson = $fullTimeline;
    @endphp
    
    <div x-data="{
        transactions: {{ Js::from($transactionsJson) }},
        filterDays: 10,
        searchDate: '',
        get filteredTransactions() {
            let result = this.transactions;
            
            // Filter by days limit
            if (this.filterDays > 0) {
                const cutoffDate = new Date();
                cutoffDate.setDate(cutoffDate.getDate() - this.filterDays);
                result = result.filter(t => new Date(t.tanggal) >= cutoffDate);
            }
            
            // Filter by specific date
            if (this.searchDate) {
                result = result.filter(t => t.tanggal === this.searchDate);
            }
            
            return result;
        },
        setFilterDays(days) {
            this.filterDays = days;
            this.searchDate = '';
        },
        formatDate(dateStr) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const d = new Date(dateStr);
            return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        },
        formatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num);
        }
    }">
        <x-moonshine::table :simple="false" :notfound="count($transactions) === 0" :translates="['notfound' => 'Belum ada transaksi produsen.']">
            <x-slot:thead>
                <tr class="bg-gradient-to-r from-transparent via-white/5 to-transparent">
                    <th colspan="8" class="!p-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1">
                                <x-moonshine::icon icon="table-cells" size="4" class="text-amber-400" />
                                <span class="text-[10px] font-black text-amber-400/70 uppercase tracking-wider">transaksi (produsen)</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button 
                                    @click="setFilterDays(10)" 
                                    :class="filterDays === 10 && !searchDate ? 'bg-emerald-500/30 border-emerald-400 text-emerald-300' : 'bg-white/5 border-white/10 text-white/50 hover:bg-white/10'"
                                    class="px-2 py-1 text-[9px] font-bold rounded border transition-all">
                                    10h
                                </button>
                                <button 
                                    @click="setFilterDays(30)" 
                                    :class="filterDays === 30 && !searchDate ? 'bg-emerald-500/30 border-emerald-400 text-emerald-300' : 'bg-white/5 border-white/10 text-white/50 hover:bg-white/10'"
                                    class="px-2 py-1 text-[9px] font-bold rounded border transition-all">
                                    30h
                                </button>
                                <button 
                                    @click="setFilterDays(90)" 
                                    :class="filterDays === 90 && !searchDate ? 'bg-emerald-500/30 border-emerald-400 text-emerald-300' : 'bg-white/5 border-white/10 text-white/50 hover:bg-white/10'"
                                    class="px-2 py-1 text-[9px] font-bold rounded border transition-all">
                                    3bln
                                </button>
                                <button 
                                    @click="setFilterDays(0)" 
                                    :class="filterDays === 0 && !searchDate ? 'bg-emerald-500/30 border-emerald-400 text-emerald-300' : 'bg-white/5 border-white/10 text-white/50 hover:bg-white/10'"
                                    class="px-2 py-1 text-[9px] font-bold rounded border transition-all">
                                    All
                                </button>
                            </div>
                            <div class="relative">
                                <input 
                                    type="date" 
                                    x-model="searchDate"
                                    class="bg-white/5 border border-white/20 rounded px-2 py-1 text-[9px] font-mono text-white/70 w-28 focus:border-emerald-400/50 focus:outline-none"
                                    placeholder="Filter tanggal">
                            </div>
                        </div>
                    </th>
                </tr>
                <tr>
                    <th class="text-left w-8">#</th>
                    <th class="text-left">Tanggal</th>
                    <th class="text-right">Jumlah</th>
                    <th class="text-right">Kemarin</th>
                    <th class="text-right">Bulatan</th>
                    <th class="text-right">Kas</th>
                    <th class="text-right">Penjualan</th>
                    <th class="text-center">Nota</th>
                </tr>
            </x-slot:thead>
            <x-slot:tbody>
                <template x-for="(row, index) in filteredTransactions" :key="index">
                    <tr class="hover:bg-white/5 transition-colors" :class="!row.has_transaction ? 'opacity-40' : ''">
                        <td class="font-mono text-xs text-white/45" x-text="index + 1"></td>
                        <td class="font-mono text-xs whitespace-nowrap" :class="row.has_transaction ? 'text-white/70' : 'text-white/30'">
                            <span x-text="row.tanggal_label"></span>
                            <span x-show="!row.has_transaction" class="ml-1 text-[8px] font-bold text-rose-400 bg-rose-500/20 px-1 py-0.5 rounded">PRODUSEN LIBUR</span>
                        </td>
                        <td class="text-right font-mono font-bold text-emerald-300" x-text="row.has_transaction ? formatNumber(row.jumlah) : '-'"></td>
                        <td class="text-right font-mono text-white/65" x-text="row.has_transaction ? formatNumber(row.kemarin) : '-'"></td>
                        <td class="text-right font-mono text-amber-300" x-text="row.has_transaction ? formatNumber(row.pembulatan) : '-'"></td>
                        <td class="text-right font-mono text-cyan-300" x-text="row.has_transaction ? formatNumber(row.kas) : '-'"></td>
                        <td class="text-right font-mono font-bold text-white" x-text="row.has_transaction ? formatNumber(row.penjualan) : '-'"></td>
                        <td class="text-center">
                            <template x-if="row.has_transaction">
                                <button 
                                    @click="openNota(row.tanggal)"
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition-colors text-[9px] font-bold">
                                    <x-moonshine::icon icon="document-text" size="3" />
                                    Nota
                                </button>
                            </template>
                        </td>
                    </tr>
                </template>
                <tr x-show="filteredTransactions.length === 0">
                    <td colspan="8" class="text-center py-4 text-white/40 text-sm">
                        Tidak ada transaksi dalam periode ini
                    </td>
                </tr>
            </x-slot:tbody>
        </x-moonshine::table>
    </div>
    </div>
</div>
