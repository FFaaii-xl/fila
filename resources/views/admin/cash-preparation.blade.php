@include('admin.reports.report-style')

<div class="space-y-6">
    {{-- Toolbar --}}
    <x-hhr-toolbar>
        <x-slot:filters>
            <div class="hhr-group">
                <span class="hhr-label-ghost">Tanggal Pasar</span>
                <input type="date" name="date" value="{{ $date }}" class="form-input" onchange="this.form.submit()" style="width: auto;">
            </div>
        </x-slot:filters>
        
        <x-slot:search>
            {{-- Search unused on Cash Preparation --}}
        </x-slot:search>
        
        <x-slot:actions>
            <button type="button" onclick="window.print()" class="hhr-btn opacity-60 hover:opacity-100" title="Cetak">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-2 4H8v-7h8v7Z"/></svg>
            </button>
        </x-slot:actions>
    </x-hhr-toolbar>

    {{-- Summary Metric --}}
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="box p-6 bg-gradient-to-br from-emerald-500/10 to-transparent border-emerald-500/20">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-center md:text-left">
                        <h2 class="text-4xl font-black editorial-title text-white">Rp {{ number_format($total_payout, 0, ',', '.') }}</h2>
                        <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mt-1">Total Uang Perlu Disiapkan Untuk Produsen</p>
                    </div>
                    <div class="text-center md:text-right px-6 py-2 border-l border-emerald-500/20">
                         <span class="text-[10px] font-mono text-white/40 uppercase tracking-widest block mb-1">Status Keamanan</span>
                         <div class="flex items-center gap-2 justify-center md:justify-end">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shadow-[0_0_10px_rgba(16,185,129,0.5)]"></div>
                            <span class="text-sm font-black text-white tracking-tighter">ANTI-TOMBOK ACTIVE</span>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Denomination Breakdown --}}
        <div class="col-span-12 lg:col-span-7">
            <div class="box overflow-hidden shadow-2xl border-white/5">
                <div class="box-header" style="padding: 16px; border-bottom: 1px solid var(--white-5); display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02);">
                    <div class="flex items-center gap-3">
                         <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center border border-emerald-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0-13.5h.75m0 0h.375a1.125 1.125 0 0 1 1.125 1.125V15a1.125 1.125 0 0 1-1.125 1.125H21m-18.75 0h.75m0 0h.375a1.125 1.125 0 0 0 1.125-1.125V6.75A1.125 1.125 0 0 0 3.375 5.625H3M3.375 5.625h.375m0 0v-1.5h1.5v1.5m0 0h3.75m-3.75 0v-1.5h1.5v1.5m0 0h3.75m-3.75 0v-1.5h1.5v1.5m0 0h3.75M16.5 21V15m0 0 5.25 5.25M16.5 15l-5.25 5.25" /></svg>
                         </div>
                         <span class="font-black text-sm uppercase tracking-widest text-white/80">Konfigurasi Pecahan Uang</span>
                    </div>
                    <span class="text-[10px] font-mono text-emerald-500/50 uppercase tracking-widest font-black">Greedy Optimization</span>
                </div>
                <div class="p-0">
                    <table class="w-full text-left pos-table-manifest">
                        <thead>
                            <tr class="bg-white/5 border-b border-white/5">
                                <th class="text-center py-4" style="width: 60px;">#</th>
                                <th class="py-4">Pecahan</th>
                                <th class="text-center py-4">Kebutuhan (Lembar)</th>
                                <th class="text-right py-4 pr-6">Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($breakdown as $idx => $item)
                            <tr class="hover:bg-emerald-500/[0.02] transition-all group">
                                <td class="text-center opacity-20 font-mono text-xs">{{ $idx + 1 }}</td>
                                <td class="py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-7 rounded bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center group-hover:scale-110 transition-transform shadow-lg shadow-emerald-500/5">
                                            <span class="text-[10px] font-black text-emerald-500">{{ number_format($item['value']/1000, 0) }}K</span>
                                        </div>
                                        <div>
                                            <span class="text-sm font-bold text-white block">{{ $item['label'] }}</span>
                                            <span class="text-[9px] font-mono opacity-30 uppercase tracking-tighter">Currency Unit</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <span class="text-lg font-black font-mono-numbers text-emerald-400">
                                            {{ $item['count'] }}
                                        </span>
                                        <span class="text-[8px] font-black opacity-30 uppercase">Sheets</span>
                                    </div>
                                </td>
                                <td class="text-right font-mono-numbers text-white/90 pr-6">
                                    <span class="text-xs opacity-40 mr-1">Rp</span>{{ number_format($item['total'], 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-emerald-500/[0.02] border-t border-emerald-500/20">
                            <tr class="font-black">
                                <td colspan="3" class="text-right py-6 uppercase tracking-[0.2em] text-[10px] text-white/40">Total Kalkulasi Uang</td>
                                <td class="text-right font-mono-numbers text-emerald-400 text-2xl py-6 pr-6">
                                    <span class="text-sm opacity-50 mr-1">Rp</span>{{ number_format(collect($breakdown)->sum('total'), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Producer List --}}
        <div class="col-span-12 lg:col-span-5">
            <div class="box overflow-hidden border-white/5 h-full">
                <div class="box-header" style="padding: 16px; border-bottom: 1px solid var(--white-5); background: rgba(255,255,255,0.02);">
                    <div class="flex items-center gap-3">
                         <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center border border-blue-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                         </div>
                         <span class="font-black text-sm uppercase tracking-widest text-white/80">Daftar Penerima Nota</span>
                    </div>
                </div>
                <div class="p-0">
                    <div class="max-h-[600px] overflow-y-auto no-scrollbar">
                        <table class="w-full text-left pos-table-manifest">
                            <thead>
                                <tr class="bg-white/5 border-b border-white/5 sticky top-0 z-10 backdrop-blur-md">
                                    <th class="py-3 pl-6">Produsen</th>
                                    <th class="text-right py-3 pr-6">Bayar Bersih</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                @forelse($producers as $prod)
                                <tr class="hover:bg-white/5 transition-colors group">
                                    <td class="font-bold text-white/80 py-3 pl-6">
                                        <div class="flex items-center gap-2">
                                            <div class="w-1.5 h-1.5 rounded-full bg-blue-500/30 group-hover:bg-blue-500 transition-colors"></div>
                                            {{ $prod['nama'] }}
                                        </div>
                                    </td>
                                    <td class="text-right font-mono-numbers text-white/60 py-3 pr-6 group-hover:text-emerald-400 transition-colors">
                                        {{ number_format($prod['payout'], 0, ',', '.') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center p-12">
                                        <div class="flex flex-col items-center gap-2 opacity-20">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                                            </svg>
                                            <span class="text-xs font-black uppercase tracking-widest">Tidak ada transaksi</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(count($producers) > 0)
                    <div class="p-4 bg-white/[0.02] border-t border-white/5">
                        <div class="flex justify-between items-center opacity-50">
                            <span class="text-[9px] font-black uppercase tracking-widest">Total Penerima</span>
                            <span class="text-xs font-black font-mono-numbers">{{ count($producers) }} ORANG</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Footer Info --}}
    <div class="text-center opacity-30 py-4">
        <p class="text-[9px] font-mono uppercase tracking-[0.3em]">Citroroso Financial Shield &copy; 2026 &bull; Automatic Cash Recon Engine</p>
    </div>
</div>
