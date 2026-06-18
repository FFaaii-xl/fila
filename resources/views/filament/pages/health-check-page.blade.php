<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-primary-500/10 rounded-xl text-primary-500">
                    <x-heroicon-o-cpu-chip class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">System Status</p>
                    <p class="text-2xl font-black {{ $health['status'] === 'Healthy' ? 'text-emerald-500' : 'text-rose-500' }}">{{ $health['status'] }}</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-500/10 rounded-xl text-blue-500">
                    <x-heroicon-o-shield-check class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Integrity Score</p>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $health['score'] }}%</p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-500/10 rounded-xl text-purple-500">
                    <x-heroicon-o-clock class="w-6 h-6" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Scan</p>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ date('H:i:s', strtotime($health['last_scan'])) }}</p>
                </div>
            </div>
        </x-filament::section>
    </div>

    @if(!empty($health['issues']))
        <x-filament::section heading="Detected Anomalies" icon="heroicon-o-exclamation-triangle">
            <div class="overflow-x-auto">
                <table class="fi-ta-table w-full text-start divide-y divide-gray-200 dark:divide-white/5">
                    <thead>
                        <tr>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Severity</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Type</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Description</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white">Identifier</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($health['issues'] as $issue)
                            <tr class="fi-ta-row">
                                <td class="fi-ta-cell px-3 py-4 text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $issue['severity'] === 'Critical' ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/10 dark:bg-rose-400/10 dark:text-rose-400 dark:ring-rose-400/20' : 'bg-yellow-50 text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-500 dark:ring-yellow-400/20' }}">
                                        {{ $issue['severity'] }}
                                    </span>
                                </td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $issue['type'] }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $issue['description'] }}</td>
                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $issue['id'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="py-10 text-center">
                <div class="w-16 h-16 bg-emerald-500/10 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-500/20">
                    <x-heroicon-o-check-badge class="w-8 h-8 text-emerald-500" />
                </div>
                <h3 class="text-lg font-bold text-emerald-500 uppercase tracking-widest">Sistem Sehat Walafiat</h3>
                <p class="text-sm text-gray-400 mt-2">Tidak ditemukan anomali pada saldo maupun transaksi.</p>
                <div class="mt-6 flex justify-center gap-6 text-xs text-gray-500">
                    <div class="flex flex-col">
                        <span class="text-gray-900 dark:text-white font-bold text-base">{{ $health['stats']['saldo_scanned'] ?? 0 }}</span>
                        <span>Saldo Akun Diverifikasi</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-900 dark:text-white font-bold text-base">{{ $health['stats']['proup_scanned'] ?? 0 }}</span>
                        <span>Audit ProUp ({{ $health['stats']['days_scanned'] ?? 7 }} Hari)</span>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
