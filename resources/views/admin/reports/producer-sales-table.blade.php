<div class="table-responsive">
    <table class="table table-list border-t border-white/5">
        <thead class="bg-white/[0.03]">
            <tr>
                @if($mode === 'tanggal')
                    <th class="py-1.5 text-[8px] uppercase tracking-[0.2em] opacity-40 px-3 text-left" style="min-width: 100px;">Pedagang</th>
                @elseif($mode === 'tahunan' || ($mode === 'range' && isset($rangeType) && $rangeType === 'bulan'))
                    <th class="text-center py-1.5 text-[8px] uppercase tracking-[0.2em] opacity-40 px-3" style="width: 70px;">Bulan</th>
                    <th class="text-center py-1.5 text-[8px] uppercase tracking-[0.2em] opacity-40" style="width: 40px;">Hari</th>
                @elseif($mode === 'range' && isset($rangeType) && $rangeType === 'tahun')
                    <th class="text-center py-1.5 text-[8px] uppercase tracking-[0.2em] opacity-40 px-3" style="width: 70px;">Tahun</th>
                    <th class="text-center py-1.5 text-[8px] uppercase tracking-[0.2em] opacity-40" style="width: 40px;">Hari</th>
                @elseif(isset($selectedProdusen) || $mode === 'nama' || ($mode === 'range' && isset($rangeType) && $rangeType === 'hari'))
                    <th class="text-center py-1.5 text-[8px] uppercase tracking-[0.2em] opacity-40 px-3" style="width: 80px;">Tanggal</th>
                @endif
                
                <th class="text-center py-1.5 text-[8px] uppercase tracking-[0.2em] opacity-40" style="width: 60px;">Titip</th>
                <th class="text-center py-1.5 text-[8px] uppercase tracking-[0.2em] opacity-40" style="width: 60px;">Laku</th>
                <th class="text-center py-1.5 text-[8px] uppercase tracking-[0.2em] opacity-40" style="width: 50px;">%</th>
                <th class="text-right py-1.5 text-[8px] uppercase tracking-[0.2em] opacity-40 pr-4" style="width: 100px;">Omset</th>
                @if($mode === 'tahunan' || $mode === 'nama' || $mode === 'range')
                    <th class="text-center py-1.5 text-[8px] uppercase tracking-[0.2em] opacity-40" style="width: 40px;">Aksi</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-white/[0.04]">
            @foreach($items as $item)
            @php 
                $rowPerc = ($item->total_titip > 0) ? round(($item->total_laku / $item->total_titip) * 100, 1) : 0;
            @endphp
            <tr class="item-row hover:bg-emerald-500/5 transition-all duration-200 {{ $loop->even ? 'bg-white/[0.015]' : '' }}">
                @if($mode === 'tanggal')
                    <td class="font-bold item-name py-1.5 text-[10px] tracking-tight px-3 opacity-80 text-amber-400">{{ ucwords(strtolower($item->pedagang_nama ?? '-')) }}</td>
                @elseif($mode === 'tahunan' || ($mode === 'range' && isset($rangeType) && $rangeType === 'bulan'))
                    <td class="text-center py-1.5 font-mono-numbers text-[10px] row-time leading-none opacity-50 px-3">
                        {{ strtoupper(date('M', mktime(0,0,0, (int)($item->bln ?? 1), 1))) }} '{{ substr($item->thn ?? (isset($selectedYear) ? $selectedYear : date('Y')), -2) }}
                    </td>
                    <td class="text-center py-1.5 font-mono-numbers text-[9px] leading-none opacity-40">{{ $item->days_count ?? 0 }}</td>
                @elseif($mode === 'range' && isset($rangeType) && $rangeType === 'tahun')
                    <td class="text-center py-1.5 font-mono-numbers text-[10px] row-time leading-none opacity-50 px-3">
                        {{ $item->thn ?? '' }}
                    </td>
                    <td class="text-center py-1.5 font-mono-numbers text-[9px] leading-none opacity-40">{{ $item->days_count ?? 0 }}</td>
                @elseif(isset($selectedProdusen) || $mode === 'nama' || ($mode === 'range' && isset($rangeType) && $rangeType === 'hari'))
                    <td class="text-center py-1.5 font-mono-numbers text-[10px] row-time leading-none opacity-50 px-3">
                        {{ date('d M \'y', strtotime($item->tgl ?? now())) }}
                    </td>
                @endif
 
                <td class="text-center py-1.5 font-mono-numbers text-[10px] leading-none opacity-50">{{ alignUang($item->total_titip, false) }}</td>
                <td class="text-center py-1.5 font-bold font-mono-numbers text-[11px] text-emerald-400 leading-none">{{ alignUang($item->total_laku, false) }}</td>
                <td class="text-center py-1.5">
                    <div class="px-1.5 py-0.5 text-[9px] font-mono-numbers font-bold rounded inline-block" style="color: hsl({{ $rowPerc * 1.4 }}, 100%, 50%); background: hsla({{ $rowPerc * 1.4 }}, 100%, 50%, 0.08);">
                        {{ $rowPerc }}%
                    </div>
                </td>
                <td class="text-right py-1.5 font-mono-numbers text-[10px] leading-none pr-4 opacity-60 text-amber-400/70">{{ alignUang($item->total_omset, false) }}</td>
                
                @if($mode === 'tahunan' || $mode === 'nama' || $mode === 'range')
                <td class="text-center py-1.5">
                    @if($mode === 'tahunan' || ($mode === 'range' && isset($rangeType) && $rangeType === 'bulan'))
                        <a href="javascript:void(0)" onclick="goToBulanan('{{ $item->bln ?? 1 }}', '{{ $item->thn ?? (isset($selectedYear) ? $selectedYear : date('Y')) }}')" class="inline-flex items-center justify-center p-1 bg-blue-500/10 text-blue-500 hover:bg-blue-500/20 rounded-md transition-colors" title="Lihat Detail Bulan">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                    @elseif($mode === 'nama' || ($mode === 'range' && isset($rangeType) && $rangeType === 'hari'))
                        <a href="javascript:void(0)" onclick="goToHarian('{{ $item->tgl ?? '' }}')" class="inline-flex items-center justify-center p-1 bg-blue-500/10 text-blue-500 hover:bg-blue-500/20 rounded-md transition-colors" title="Lihat Detail Hari">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                    @endif
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
