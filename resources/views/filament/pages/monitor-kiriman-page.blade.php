<x-filament-panels::page>
    @php
        $prevDate = date('Y-m-d', strtotime('-1 day', strtotime($tanggal)));
        $nextDate = date('Y-m-d', strtotime('+1 day', strtotime($tanggal)));
        $today = now()->toDateString();

        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $dayName = $days[date('w', strtotime($tanggal))];
        $formattedDate = date('d M Y', strtotime($tanggal));
    @endphp

    {{-- Header & Date Navigation --}}
    <x-filament::section>
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Lihat kiriman per pedagang per hari secara lengkap
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="?date={{ $prevDate }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-white/5 dark:hover:bg-white/10 rounded-lg text-sm text-gray-700 dark:text-gray-300 transition-colors">
                    &larr; Prev
                </a>
                <div class="px-4 py-2 bg-primary-50 dark:bg-primary-500/10 border border-primary-200 dark:border-primary-500/20 rounded-lg">
                    <span class="text-primary-600 dark:text-primary-400 font-bold">{{ $dayName }}</span>
                    <span class="text-gray-600 dark:text-gray-300 ml-2">{{ $formattedDate }}</span>
                </div>
                <a href="?date={{ $nextDate }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-white/5 dark:hover:bg-white/10 rounded-lg text-sm text-gray-700 dark:text-gray-300 transition-colors {{ $tanggal >= $today ? 'opacity-50 pointer-events-none' : '' }}">
                    Next &rarr;
                </a>
                <a href="?date={{ $today }}" class="px-3 py-2 bg-success-50 dark:bg-success-500/10 hover:bg-success-100 dark:hover:bg-success-500/20 border border-success-200 dark:border-success-500/20 rounded-lg text-success-600 dark:text-success-400 text-sm font-medium transition-colors">
                    Hari Ini
                </a>
            </div>
        </div>
    </x-filament::section>

    {{-- Summary Metrics --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-2">
                <x-heroicon-o-users class="w-8 h-8 text-primary-500 mb-2 opacity-80" />
                <span class="text-3xl font-black font-mono text-gray-900 dark:text-white">{{ $total_pedagang }}</span>
                <span class="text-xs font-bold text-gray-500 uppercase mt-1">Total Pedagang</span>
            </div>
        </x-filament::section>
        
        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-2">
                <x-heroicon-o-check-circle class="w-8 h-8 text-success-500 mb-2 opacity-80" />
                <span class="text-3xl font-black font-mono text-success-600 dark:text-success-400">{{ $sudah_kirim }}</span>
                <span class="text-xs font-bold text-gray-500 uppercase mt-1">Sudah Kirim</span>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-2">
                <x-heroicon-o-clock class="w-8 h-8 text-warning-500 mb-2 opacity-80" />
                <span class="text-3xl font-black font-mono text-warning-600 dark:text-warning-400">{{ $belum_kirim }}</span>
                <span class="text-xs font-bold text-gray-500 uppercase mt-1">Belum Kirim</span>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-2">
                <x-heroicon-o-shopping-bag class="w-8 h-8 text-info-500 mb-2 opacity-80" />
                <span class="text-3xl font-black font-mono text-info-600 dark:text-info-400">{{ number_format($total_titip, 0, ',', '.') }}</span>
                <span class="text-xs font-bold text-gray-500 uppercase mt-1">Total Titip (Pcs)</span>
            </div>
        </x-filament::section>
    </div>

    {{-- Detail Kiriman Table --}}
    <div class="mt-6">
        <x-filament::section padding="0">
            <div class="overflow-x-auto">
                @if($pedagang->isEmpty())
                <div class="text-center py-12">
                    <x-heroicon-o-inbox class="w-16 h-16 mx-auto text-gray-400 opacity-50 mb-4" />
                    <p class="text-lg font-bold text-gray-900 dark:text-white">Belum Ada Kiriman</p>
                    <p class="text-sm mt-2 text-gray-500">Tidak ada pedagang yang mengirim pada tanggal ini</p>
                </div>
                @else
                <table class="fi-ta-table w-full text-start divide-y divide-gray-200 dark:divide-white/5">
                    <thead>
                        <tr>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center w-12">#</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-left">Pedagang</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Item</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Titip</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Laku</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Laku%</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Modal</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Omset</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Jam Kirim</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($pedagang as $idx => $p)
                        @php
                            $lakuPercent = $p->total_titip > 0 ? round(($p->total_laku / $p->total_titip) * 100, 1) : 0;
                            $statusColor = $p->status === 'Ok' ? 'success' : ($p->status === 'Pending' ? 'warning' : 'gray');
                            $sentTime = $p->sent_at ? date('H:i', strtotime($p->sent_at)) : '-';
                            
                            $words = explode(' ', $p->pedagang_nama);
                            $initials = count($words) >= 2 ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1)) : strtoupper(substr($p->pedagang_nama, 0, 2));
                        @endphp
                        <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                            <td class="fi-ta-cell px-3 py-4 text-center text-sm text-gray-400 font-mono">{{ $idx + 1 }}</td>
                            <td class="fi-ta-cell px-3 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-500/20 flex items-center justify-center">
                                        <span class="text-warning-600 dark:text-warning-400 font-bold text-sm">{{ $initials }}</span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white block">{{ $p->pedagang_nama }}</span>
                                        <span class="text-[10px] text-gray-500 uppercase tracking-wider mt-0.5 block">ID: {{ $p->pedagang_id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 text-center">
                                <span class="block text-sm font-mono font-bold text-gray-900 dark:text-white">{{ $p->item_count }}</span>
                                <span class="text-[9px] text-gray-400 uppercase">Item</span>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 text-center">
                                <span class="block text-sm font-mono font-bold text-gray-900 dark:text-white">{{ $p->total_titip }}</span>
                                <span class="text-[9px] text-gray-400 uppercase">PCS</span>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 text-center">
                                <span class="block text-sm font-mono font-bold text-success-600 dark:text-success-400">{{ $p->total_laku }}</span>
                                <span class="text-[9px] text-gray-400 uppercase">PCS</span>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 text-center">
                                <span class="px-2 py-1 bg-{{ $statusColor }}-50 dark:bg-{{ $statusColor }}-500/10 border border-{{ $statusColor }}-200 dark:border-{{ $statusColor }}-500/20 rounded-lg text-{{ $statusColor }}-600 dark:text-{{ $statusColor }}-400 font-bold text-xs inline-block">
                                    {{ $lakuPercent }}%
                                </span>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 text-right">
                                <span class="block text-sm font-mono font-bold text-gray-900 dark:text-white">Rp {{ number_format((float)$p->total_modal, 0, ',', '.') }}</span>
                                <span class="text-[9px] text-gray-400 uppercase">Modal</span>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 text-right">
                                <span class="block text-sm font-mono font-bold text-success-600 dark:text-success-400">Rp {{ number_format((float)$p->total_omset, 0, ',', '.') }}</span>
                                <span class="text-[9px] text-gray-400 uppercase">Omset</span>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 text-center text-sm text-gray-500">
                                {{ $sentTime }}
                            </td>
                            <td class="fi-ta-cell px-3 py-4 text-center">
                                <a href="{{ url('admin/upload-penjualan?date=' . $tanggal) }}" class="inline-flex items-center justify-center px-3 py-1 bg-primary-50 dark:bg-primary-500/10 hover:bg-primary-100 dark:hover:bg-primary-500/20 border border-primary-200 dark:border-primary-500/20 rounded-lg text-primary-600 dark:text-primary-400 text-xs font-bold transition">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-success-50 dark:bg-success-500/5 border-t-2 border-success-200 dark:border-success-500/20">
                        @php
                            $totalModal = $pedagang->sum('total_modal');
                            $totalOmset = $pedagang->sum('total_omset');
                        @endphp
                        <tr>
                            <td colspan="3" class="px-3 py-4 text-right text-xs font-black text-gray-500 uppercase tracking-widest">
                                Total ({{ count($pedagang) }} Pedagang)
                            </td>
                            <td class="px-3 py-4 text-center text-sm font-mono font-bold text-gray-900 dark:text-white">
                                {{ $pedagang->sum('total_titip') }}
                            </td>
                            <td class="px-3 py-4 text-center text-sm font-mono font-bold text-success-600 dark:text-success-400">
                                {{ $pedagang->sum('total_laku') }}
                            </td>
                            <td class="px-3 py-4 text-center"></td>
                            <td class="px-3 py-4 text-right text-base font-mono font-bold text-gray-900 dark:text-white">
                                Rp {{ number_format((float)$totalModal, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-4 text-right text-base font-mono font-bold text-success-600 dark:text-success-400">
                                Rp {{ number_format((float)$totalOmset, 0, ',', '.') }}
                            </td>
                            <td colspan="2" class="px-3 py-4"></td>
                        </tr>
                    </tfoot>
                </table>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
