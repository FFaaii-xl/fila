<x-filament-panels::page>
    <div x-data="{ date: '{{ $date }}' }">
        {{-- Date Picker Toolbar --}}
        <x-filament::section>
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-calendar-days class="w-5 h-5 text-gray-400" />
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Pasar</span>
                    <form method="GET" action="{{ url()->current() }}">
                        <input
                            type="date"
                            name="date"
                            value="{{ $date }}"
                            onchange="this.form.submit()"
                            class="fi-input block w-auto rounded-lg border-none bg-white/5 px-3 py-1.5 text-sm text-gray-950 shadow-sm ring-1 ring-inset ring-gray-950/10 dark:text-white dark:ring-white/20 focus:ring-2 focus:ring-primary-600"
                        />
                    </form>
                </div>
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">
                    <x-heroicon-o-printer class="w-4 h-4" />
                    Cetak
                </button>
            </div>
        </x-filament::section>

        {{-- Summary Metric --}}
        <div class="mt-6">
            <x-filament::section>
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 py-2">
                    <div class="text-center md:text-left">
                        <h2 class="text-4xl font-black text-gray-950 dark:text-white">Rp {{ number_format($total_payout, 0, ',', '.') }}</h2>
                        <p class="text-xs font-bold text-primary-600 dark:text-primary-400 uppercase tracking-widest mt-1">Total Uang Perlu Disiapkan Untuk Produsen</p>
                    </div>
                    <div class="text-center md:text-right px-6 py-2 border-l border-gray-200 dark:border-white/10">
                        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-widest block mb-1">Status Keamanan</span>
                        <div class="flex items-center gap-2 justify-center md:justify-end">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_10px_rgba(16,185,129,0.5)]"></div>
                            <span class="text-sm font-black text-gray-950 dark:text-white tracking-tight">ANTI-TOMBOK ACTIVE</span>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-7 gap-6">
            {{-- Denomination Breakdown --}}
            <div class="lg:col-span-4">
                <x-filament::section heading="Konfigurasi Pecahan Uang" icon="heroicon-o-banknotes">
                    <x-slot name="headerEnd">
                        <span class="text-xs font-mono text-primary-500 uppercase tracking-widest font-bold">Greedy Optimization</span>
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="fi-ta-table w-full text-start divide-y divide-gray-200 dark:divide-white/5">
                            <thead>
                                <tr>
                                    <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-center" style="width: 50px;">#</th>
                                    <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Pecahan</th>
                                    <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-center">Kebutuhan (Lembar)</th>
                                    <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Total Nilai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @foreach($breakdown as $idx => $item)
                                <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group">
                                    <td class="fi-ta-cell px-3 py-4 text-sm text-center text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                    <td class="fi-ta-cell px-3 py-4 text-sm">
                                        <div class="flex items-center gap-3">
                                            <div class="w-12 h-7 rounded-md bg-primary-50 dark:bg-primary-500/10 border border-primary-200 dark:border-primary-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                                                <span class="text-xs font-black text-primary-600 dark:text-primary-400">{{ number_format($item['value']/1000, 0) }}K</span>
                                            </div>
                                            <div>
                                                <span class="text-sm font-bold text-gray-950 dark:text-white block">{{ $item['label'] }}</span>
                                                <span class="text-xs text-gray-400 font-mono uppercase tracking-tight">Currency Unit</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell px-3 py-4 text-center">
                                        <span class="text-lg font-black font-mono text-primary-600 dark:text-primary-400">{{ $item['count'] }}</span>
                                        <span class="block text-xs text-gray-400 font-bold uppercase">Sheets</span>
                                    </td>
                                    <td class="fi-ta-cell px-3 py-4 text-right font-mono text-gray-700 dark:text-gray-300">
                                        <span class="text-xs text-gray-400 mr-1">Rp</span>{{ number_format($item['total'], 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-primary-50/50 dark:bg-primary-500/5 border-t border-primary-200 dark:border-primary-500/20">
                                <tr>
                                    <td colspan="3" class="text-right py-5 pr-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Total Kalkulasi Uang</td>
                                    <td class="text-right font-mono text-primary-600 dark:text-primary-400 text-2xl font-black py-5 pr-3">
                                        <span class="text-sm opacity-50 mr-1">Rp</span>{{ number_format(collect($breakdown)->sum('total'), 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </x-filament::section>
            </div>

            {{-- Producer List --}}
            <div class="lg:col-span-3">
                <x-filament::section heading="Daftar Penerima Nota" icon="heroicon-o-user-group">
                    <div class="max-h-[600px] overflow-y-auto">
                        <table class="fi-ta-table w-full text-start divide-y divide-gray-200 dark:divide-white/5">
                            <thead>
                                <tr>
                                    <th class="fi-ta-header-cell px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white sticky top-0 bg-white dark:bg-gray-900 z-10">Produsen</th>
                                    <th class="fi-ta-header-cell px-4 py-3 text-sm font-semibold text-gray-950 dark:text-white text-right sticky top-0 bg-white dark:bg-gray-900 z-10">Bayar Bersih</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                                @forelse($producers as $prod)
                                <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group">
                                    <td class="fi-ta-cell px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            <div class="w-1.5 h-1.5 rounded-full bg-primary-300 dark:bg-primary-500/30 group-hover:bg-primary-500 transition-colors"></div>
                                            {{ $prod['nama'] }}
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell px-4 py-3 text-sm text-right font-mono text-gray-600 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                        {{ number_format($prod['payout'], 0, ',', '.') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center py-12">
                                        <div class="flex flex-col items-center gap-2 text-gray-400">
                                            <x-heroicon-o-inbox class="w-12 h-12 opacity-30" />
                                            <span class="text-xs font-bold uppercase tracking-widest">Tidak ada transaksi</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(count($producers) > 0)
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-white/5 flex justify-between items-center text-gray-500">
                        <span class="text-xs font-bold uppercase tracking-widest">Total Penerima</span>
                        <span class="text-xs font-bold font-mono">{{ count($producers) }} ORANG</span>
                    </div>
                    @endif
                </x-filament::section>
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center py-6 mt-6 opacity-30">
            <p class="text-xs font-mono uppercase tracking-widest">Citroroso Financial Shield &copy; 2026 &bull; Automatic Cash Recon Engine</p>
        </div>
    </div>
</x-filament-panels::page>
