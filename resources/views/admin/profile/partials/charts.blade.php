<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    @if($profile['type'] === 'pedagang')
        <!-- Pedagang Chart: Omset 7 Hari -->
        <div class="bg-gradient-to-br from-violet-950/60 to-indigo-950/40 rounded-xl p-5 border border-violet-500/20 shadow-lg">
            <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                <x-moonshine::icon icon="chart-bar" size="5" class="text-violet-400" />
                Omset 7 Hari Terakhir
            </h3>
            @php
                $chartData = $profile['chart_data'] ?? collect();
                $maxValue = $chartData->max('total_omset') ?? 1;
            @endphp
            <div class="flex items-end justify-between gap-2 h-32" x-data="{
                    data: {{ Js::from($chartData->map(fn($d) => ['date' => \Carbon\Carbon::parse($d->date)->format('d/m'), 'value' => (int)$d->total_omset])->values()) }},
                    max: {{ $maxValue }},
                    getHeight(val) { return (val / this.max) * 100; },
                    getBar(index) { return this.data[index] ? this.data[index].value : 0; },
                    getLabel(index) { return this.data[index] ? this.data[index].date : ''; }
                }">
                <template x-for="(item, index) in data" :key="index">
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-violet-900/40 rounded-t-md relative group" 
                             :style="'height: ' + getHeight(item.value) + '%'">
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-violet-900/90 text-violet-200 text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-mono">
                                Rp <span x-text="item.value.toLocaleString('id-ID')"></span>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-violet-600/60 to-violet-400/20 rounded-t-md"></div>
                        </div>
                        <span class="text-white/40 text-[10px] font-mono" x-text="item.date"></span>
                    </div>
                </template>
                @if($chartData->isEmpty())
                    <div class="w-full text-center text-white/40 text-sm py-8">Tidak ada data</div>
                @endif
            </div>
        </div>
        
        <!-- Top Products -->
        <div class="bg-gradient-to-br from-emerald-950/60 to-emerald-900/40 rounded-xl p-5 border border-emerald-500/20 shadow-lg">
            <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                <x-moonshine::icon icon="star" size="5" class="text-emerald-400" />
                Top 5 Produk Terlaris
            </h3>
            <div class="space-y-3">
                @forelse($profile['top_products'] as $index => $product)
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold text-xs">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1">
                            <div class="h-6 bg-emerald-900/40 rounded relative overflow-hidden">
                                <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-emerald-600/60 to-emerald-400/40 rounded" 
                                     style="width: {{ $loop->first ? 100 : ($product->total_laku / $profile['top_products']->first()->total_laku * 100) }}%"></div>
                                <span class="absolute inset-0 flex items-center px-2 text-white/80 text-xs font-medium">
                                    {{ $product->nama }}
                                </span>
                            </div>
                        </div>
                        <span class="text-emerald-400 font-mono text-sm font-bold">{{ number_format($product->total_laku) }}</span>
                    </div>
                @empty
                    <div class="text-white/40 text-sm text-center py-4">Tidak ada data</div>
                @endforelse
            </div>
        </div>
        
    @elseif($profile['type'] === 'produsen')
        <!-- Produsen Chart: Penjualan per Produk -->
        <div class="bg-gradient-to-br from-blue-950/60 to-indigo-950/40 rounded-xl p-5 border border-blue-500/20 shadow-lg">
            <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                <x-moonshine::icon icon="chart-bar-square" size="5" class="text-blue-400" />
                Penjualan per Produk (7 Hari)
            </h3>
            @php
                $chartData = $profile['chart_data'] ?? collect();
                $maxValue = $chartData->max('total_laku') ?? 1;
            @endphp
            <div class="flex items-end justify-between gap-2 h-32" x-data="{
                    data: {{ Js::from($chartData->map(fn($d) => ['date' => \Carbon\Carbon::parse($d->date)->format('d/m'), 'value' => (int)$d->total_laku])->values()) }},
                    max: {{ $maxValue }}
                }">
                <template x-for="(item, index) in data" :key="index">
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-blue-900/40 rounded-t-md relative group" 
                             :style="'height: ' + ((item.value / max) * 100) + '%'">
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-blue-900/90 text-blue-200 text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-mono">
                                <span x-text="item.value.toLocaleString('id-ID')"></span>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-blue-600/60 to-blue-400/20 rounded-t-md"></div>
                        </div>
                        <span class="text-white/40 text-[10px] font-mono" x-text="item.date"></span>
                    </div>
                </template>
                @if($chartData->isEmpty())
                    <div class="w-full text-center text-white/40 text-sm py-8">Tidak ada data</div>
                @endif
            </div>
        </div>
        
        <!-- Top Products -->
        <div class="bg-gradient-to-br from-violet-950/60 to-violet-900/40 rounded-xl p-5 border border-violet-500/20 shadow-lg">
            <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                <x-moonshine::icon icon="sparkles" size="5" class="text-violet-400" />
                Produk Terlaris
            </h3>
            <div class="space-y-3">
                @forelse($profile['top_products'] as $index => $product)
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full bg-violet-500/20 flex items-center justify-center text-violet-400 font-bold text-xs">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1">
                            <div class="h-6 bg-violet-900/40 rounded relative overflow-hidden">
                                <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-violet-600/60 to-violet-400/40 rounded" 
                                     style="width: {{ $loop->first ? 100 : ($product->total_laku / $profile['top_products']->first()->total_laku * 100) }}%"></div>
                                <span class="absolute inset-0 flex items-center px-2 text-white/80 text-xs font-medium">
                                    {{ $product->nama }}
                                </span>
                            </div>
                        </div>
                        <span class="text-violet-400 font-mono text-sm font-bold">{{ number_format($product->total_laku) }}</span>
                    </div>
                @empty
                    <div class="text-white/40 text-sm text-center py-4">Tidak ada data</div>
                @endforelse
            </div>
        </div>
        
    @elseif($profile['type'] === 'admin' || $profile['type'] === 'pengurus')
        <!-- Admin Chart: System Overview -->
        <div class="bg-gradient-to-br from-amber-950/60 to-orange-950/40 rounded-xl p-5 border border-amber-500/20 shadow-lg">
            <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                <x-moonshine::icon icon="chart-line" size="5" class="text-amber-400" />
                Omset Sistem (7 Hari)
            </h3>
            @php
                $chartData = $profile['stats']['chart_data'] ?? collect();
                $maxValue = $chartData->max('total_omset') ?? 1;
            @endphp
            <div class="flex items-end justify-between gap-2 h-32" x-data="{
                    data: {{ Js::from($chartData->map(fn($d) => ['date' => \Carbon\Carbon::parse($d->date)->format('d/m'), 'value' => (int)$d->total_omset])->values()) }},
                    max: {{ $maxValue }}
                }">
                <template x-for="(item, index) in data" :key="index">
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div class="w-full bg-amber-900/40 rounded-t-md relative group" 
                             :style="'height: ' + ((item.value / max) * 100) + '%'">
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-amber-900/90 text-amber-200 text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap font-mono">
                                Rp <span x-text="item.value.toLocaleString('id-ID')"></span>
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-amber-600/60 to-amber-400/20 rounded-t-md"></div>
                        </div>
                        <span class="text-white/40 text-[10px] font-mono" x-text="item.date"></span>
                    </div>
                </template>
                @if($chartData->isEmpty())
                    <div class="w-full text-center text-white/40 text-sm py-8">Tidak ada data</div>
                @endif
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="bg-gradient-to-br from-purple-950/60 to-pink-950/40 rounded-xl p-5 border border-purple-500/20 shadow-lg">
            <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
                <x-moonshine::icon icon="sparkles" size="5" class="text-purple-400" />
                Statistik Cepat
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-purple-900/30 rounded-lg p-3 text-center">
                    <p class="text-purple-300 text-xs uppercase tracking-wider mb-1">Produk</p>
                    <p class="text-white text-xl font-bold font-mono">{{ $profile['stats']['total_produk'] ?? 0 }}</p>
                </div>
                <div class="bg-pink-900/30 rounded-lg p-3 text-center">
                    <p class="text-pink-300 text-xs uppercase tracking-wider mb-1">Pending</p>
                    <p class="text-white text-xl font-bold font-mono">{{ $profile['stats']['pending_reports'] ?? 0 }}</p>
                </div>
                <div class="col-span-2 bg-emerald-900/30 rounded-lg p-3 text-center">
                    <p class="text-emerald-300 text-xs uppercase tracking-wider mb-1">Omset Bulan Ini</p>
                    <p class="text-white text-lg font-bold font-mono">Rp {{ number_format($profile['stats']['today_sales']->total_omset ?? 0) }}</p>
                </div>
            </div>
        </div>
    @endif
</div>