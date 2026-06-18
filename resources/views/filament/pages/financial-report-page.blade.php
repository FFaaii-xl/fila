<x-filament-panels::page>
    {{-- Filter --}}
    @include('admin.reports.financial-filter', [
        'months' => $months,
        'month' => $month,
        'year' => $year,
        'years' => $years,
    ])

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Produsen Table --}}
        <x-filament::section heading="REKAP PRODUSEN" icon="heroicon-o-building-storefront">
            <div class="overflow-x-auto">
                <table class="fi-ta-table w-full text-start divide-y divide-gray-200 dark:divide-white/5">
                    <thead>
                        <tr>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Tanggal</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Omset</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Kas</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Tabungan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @forelse($recap['produsen'] ?? [] as $row)
                            <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $row['date'] ?? '-' }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-right font-bold">Rp {{ number_format($row['sales'] ?? 0, 0, ',', '.') }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-right text-emerald-600 dark:text-emerald-400 font-mono">Rp {{ number_format($row['kas'] ?? 0, 0, ',', '.') }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-right text-blue-600 dark:text-blue-400 font-mono">Rp {{ number_format($row['tabungan'] ?? 0, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-6 text-gray-400">Tidak ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Pedagang Table --}}
        <x-filament::section heading="REKAP PEDAGANG" icon="heroicon-o-shopping-bag">
            <div class="overflow-x-auto">
                <table class="fi-ta-table w-full text-start divide-y divide-gray-200 dark:divide-white/5">
                    <thead>
                        <tr>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Tanggal</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Modal</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Kas</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Tabungan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @forelse($recap['pedagang'] ?? [] as $row)
                            <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $row['date'] ?? '-' }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-right font-bold">Rp {{ number_format($row['sales'] ?? 0, 0, ',', '.') }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-right text-emerald-600 dark:text-emerald-400 font-mono">Rp {{ number_format($row['kas'] ?? 0, 0, ',', '.') }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-right text-blue-600 dark:text-blue-400 font-mono">Rp {{ number_format($row['tabungan'] ?? 0, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-6 text-gray-400">Tidak ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>

    {{-- Summary & Expenses --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mt-6">
        {{-- Expenses --}}
        <div class="lg:col-span-7">
            <x-filament::section heading="PENGELUARAN OPERASIONAL" icon="heroicon-o-receipt-percent">
                <div class="overflow-x-auto">
                    <table class="fi-ta-table w-full text-start divide-y divide-gray-200 dark:divide-white/5">
                        <thead>
                            <tr>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Tgl</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Keterangan</th>
                                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @forelse($recap['summary']['expenses'] ?? [] as $expense)
                                <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400">{{ isset($expense['tanggal']) ? \Carbon\Carbon::parse($expense['tanggal'])->format('d M') : '-' }}</td>
                                    <td class="fi-ta-cell px-3 py-4 text-sm">{{ $expense['keterangan'] ?? '-' }}</td>
                                    <td class="fi-ta-cell px-3 py-4 text-sm text-right font-bold text-danger-600 dark:text-danger-400">Rp {{ number_format($expense['jumlah'] ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-6 text-gray-400">Tidak ada pengeluaran</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>

        {{-- Consolidation --}}
        <div class="lg:col-span-5">
            <x-filament::section heading="KONSOLIDASI AKHIR" icon="heroicon-o-calculator">
                <div class="space-y-4">
                    <div class="p-4 rounded-xl bg-blue-50 dark:bg-blue-500/10 border-l-4 border-blue-500">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pemasukan</p>
                        <p class="text-2xl font-black text-blue-600 dark:text-blue-400">Rp {{ number_format((float) ($recap['summary']['total_pemasukan'] ?? 0), 0, ',', '.') }}</p>
                    </div>

                    <div class="p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border-l-4 border-red-500">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pengeluaran</p>
                        <p class="text-2xl font-black text-danger-600 dark:text-danger-400">Rp {{ number_format((float) ($recap['summary']['total_pengeluaran'] ?? 0), 0, ',', '.') }}</p>
                    </div>

                    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border-l-4 border-emerald-500">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Sisa Setor</p>
                        <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format((float) ($recap['summary']['total_setor'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
