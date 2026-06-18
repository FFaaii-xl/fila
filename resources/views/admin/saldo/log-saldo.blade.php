@include('admin.reports.report-style')
@php
    $withBalance = $logs->filter(fn($l) => $l->saldo_tersedia > 0);
    $zeroBalance = $logs->filter(fn($l) => $l->saldo_tersedia == 0);
@endphp

<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-black text-white italic font-playfair">Tracking Nalangi Pedagang</h2>
            <p class="text-[10px] text-slate-500 uppercase tracking-widest font-outfit">Perbandingan Tagihan Sistem vs Saldo Tersedia</p>
        </div>
    </div>

    <!-- Filter Section -->
    <x-hhr-toolbar>
        <x-slot:filters>
            <div class="hhr-group">
                <span class="hhr-label-ghost">Tanggal Penjualan</span>
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="form-input" style="width: auto;">
            </div>
        </x-slot:filters>
        
        <x-slot:search>
            {{-- Search unused on Log Saldo --}}
        </x-slot:search>

        <x-slot:actions>
            <button type="submit" class="hhr-btn hhr-btn-excel w-full md:w-auto" style="background: rgba(147,51,234,0.1) !important; color: #d8b4fe !important; border-color: rgba(147,51,234,0.3) !important;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                <span>Refresh Tracker</span>
            </button>
        </x-slot:actions>
    </x-hhr-toolbar>

    <!-- TABLE 1: MEMILIKI SALDO -->
    <div class="space-y-2">
        <div class="flex items-center gap-2 px-1">
            <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-400">Pedagang Memiliki Saldo (Aktif)</h3>
        </div>
        <div class="overflow-x-auto bg-slate-900/30 rounded-xl border border-slate-700/50">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800/50 text-[10px] uppercase tracking-widest text-slate-400 border-b border-slate-700/50">
                        <th class="px-4 py-3 font-semibold">Tanggal</th>
                        <th class="px-4 py-3 font-semibold">Pedagang</th>
                        <th class="px-4 py-3 font-semibold text-right">Tagihan Sistem</th>
                        <th class="px-4 py-3 font-semibold text-right">Saldo Tersedia</th>
                        <th class="px-4 py-3 font-semibold text-right">Selisih</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="text-xs text-slate-300 divide-y divide-slate-700/30">
                    @forelse($withBalance as $log)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-2 font-mono text-[11px] text-slate-400">
                                {{ \Carbon\Carbon::parse($log->tanggal)->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-2 font-bold uppercase text-slate-200">
                                {{ $log->pedagang_nama }}
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-slate-400">
                                {{ number_format($log->tagihan_sistem, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-slate-100">
                                {{ number_format($log->saldo_tersedia, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-right font-mono font-bold {{ $log->selisih < 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                                {{ $log->selisih > 0 ? '+' : '' }}{{ number_format($log->selisih, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                @if($log->selisih < 0)
                                    <span class="bg-rose-500/10 text-rose-500 border border-rose-500/20 px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-tighter">
                                        NALANGI
                                    </span>
                                @else
                                    <span class="bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-tighter">
                                        SISA SALDO
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 italic">
                                Tidak ada pedagang dengan saldo pada tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($withBalance->isNotEmpty())
                    <tfoot class="bg-slate-800/50 border-t border-slate-700 text-xs font-mono">
                        <tr class="font-bold">
                            <td colspan="2" class="px-4 py-3 text-slate-400 uppercase text-[10px] tracking-widest">TOTAL AKTIF</td>
                            <td class="px-4 py-3 text-right text-slate-400">
                                {{ number_format($withBalance->sum('tagihan_sistem'), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-100">
                                {{ number_format($withBalance->sum('saldo_tersedia'), 0, ',', '.') }}
                            </td>
                            @php $totalSelisih = $withBalance->sum('selisih'); @endphp
                            <td class="px-4 py-3 text-right {{ $totalSelisih < 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                                {{ $totalSelisih > 0 ? '+' : '' }}{{ number_format($totalSelisih, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- TABLE 2: SALDO NOL -->
    <div class="space-y-2 pt-4">
        <div class="flex items-center gap-2 px-1">
            <div class="w-1.5 h-4 bg-amber-500 rounded-full opacity-70"></div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 opacity-80">Pedagang Saldo Nol (Setor Sore)</h3>
        </div>
        <div class="overflow-x-auto bg-slate-900/10 rounded-xl border border-slate-800/50 border-dashed">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900/50 text-[10px] uppercase tracking-widest text-slate-500 border-b border-slate-800/50">
                        <th class="px-4 py-2 font-semibold">Tanggal</th>
                        <th class="px-4 py-2 font-semibold">Pedagang</th>
                        <th class="px-4 py-2 text-right font-semibold">Tagihan Sistem</th>
                        <th class="px-4 py-2 text-right font-semibold">Saldo Tersedia</th>
                        <th class="px-4 py-2 text-right font-semibold">Selisih</th>
                        <th class="px-4 py-2 text-center font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="text-xs text-slate-500 divide-y divide-slate-800/20">
                    @forelse($zeroBalance as $log)
                        <tr class="hover:bg-slate-800/10 transition-colors">
                            <td class="px-4 py-1.5 font-mono text-[10px]">
                                {{ \Carbon\Carbon::parse($log->tanggal)->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-1.5 uppercase font-medium">
                                {{ $log->pedagang_nama }}
                            </td>
                            <td class="px-4 py-1.5 text-right font-mono">
                                {{ number_format($log->tagihan_sistem, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-1.5 text-right font-mono">
                                {{ number_format($log->saldo_tersedia, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-1.5 text-right font-mono text-amber-400/80 font-bold">
                                {{ number_format($log->selisih, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-1.5 text-center">
                                <span class="bg-amber-500/10 text-amber-500 border border-amber-500/20 px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-tighter">
                                    SETOR SORE
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-slate-600 italic">
                                Tidak ada pedagang dengan saldo nol.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($zeroBalance->isNotEmpty())
                    <tfoot class="bg-slate-900/50 border-t border-slate-800/50 text-xs font-mono">
                        <tr class="font-bold">
                            <td colspan="2" class="px-4 py-2 text-slate-600 uppercase text-[9px] tracking-widest">TOTAL NOL</td>
                            <td class="px-4 py-2 text-right text-slate-600">
                                {{ number_format($zeroBalance->sum('tagihan_sistem'), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2 text-right text-slate-600">0</td>
                            <td class="px-4 py-2 text-right text-amber-500/80">
                                {{ number_format($zeroBalance->sum('selisih'), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<style>
    input[type="date"]::-webkit-calendar-picker-indicator {
        filter: invert(1);
        cursor: pointer;
    }
</style>
