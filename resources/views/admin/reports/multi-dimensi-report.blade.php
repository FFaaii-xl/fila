@include('admin.reports.report-style')

<div class="space-y-6">
    {{-- Toolbar --}}
    <x-hhr-toolbar>
        <x-slot:filters>
            <div class="hhr-group">
                <span class="hhr-label-ghost">Waktu</span>
                <select name="waktu" class="form-select" onchange="this.form.submit()" style="width: auto;">
                    <option value="Per Hari" {{ $waktu == 'Per Hari' ? 'selected' : '' }}>Per Hari</option>
                    <option value="Per Bulan" {{ $waktu == 'Per Bulan' ? 'selected' : '' }}>Per Bulan</option>
                </select>
            </div>

            <div class="hhr-group">
                <span class="hhr-label-ghost">Isi</span>
                <select name="isi" class="form-select" onchange="this.form.submit()" style="width: auto;">
                    <option value="Keseluruhan" {{ $isi == 'Keseluruhan' ? 'selected' : '' }}>Keseluruhan</option>
                    <option value="Produk" {{ $isi == 'Produk' ? 'selected' : '' }}>Produk</option>
                    <option value="Produsen" {{ $isi == 'Produsen' ? 'selected' : '' }}>Produsen</option>
                    <option value="Pedagang" {{ $isi == 'Pedagang' ? 'selected' : '' }}>Pedagang</option>
                </select>
            </div>

            <div class="hhr-group">
                <span class="hhr-label-ghost">Bulan</span>
                <select name="bulan" class="form-select" onchange="this.form.submit()" style="width: auto;">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $bulan == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $m, 1)->format('M') }}</option>
                    @endfor
                </select>
                <select name="tahun" class="form-select" onchange="this.form.submit()" style="width: auto;">
                    @for($y = now()->year; $y >= 2021; $y--)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </x-slot:filters>

        <x-slot:search>
            <div class="relative">
                <input type="text" name="filter_nama" value="{{ $filterNama }}" placeholder="Filter nama..." class="form-input w-full pl-8" style="padding-left: 1.8rem !important;" onchange="this.form.submit()">
                <div class="absolute left-2.5 top-1/2 -translate-y-1/2 opacity-30">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
                </div>
            </div>
        </x-slot:search>

        <x-slot:actions>
            <button type="button" onclick="window.print()" class="hhr-btn opacity-60 hover:opacity-100" title="Cetak">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2m-2 4H8v-7h8v7Z"/></svg>
            </button>
        </x-slot:actions>
    </x-hhr-toolbar>

    {{-- Section 1: Transaksi Report --}}
    <div class="box">
        <div style="padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 8px;">
            <span style="font-weight: 700; font-size: 14px;">📊 Laporan Transaksi</span>
            <span style="font-size: 11px; opacity: 0.5;">{{ $waktu }} &middot; {{ \Carbon\Carbon::create($tahun, $bulan, 1)->format('F Y') }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-list" id="transaksiTable">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">Tgl</th>
                        <th class="text-center" style="width: 70px;">Tipe</th>
                        <th class="text-right">Penjualan</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-right">Kas</th>
                        <th class="text-right">Tabungan</th>
                        <th class="text-right">Kemarin</th>
                        <th class="text-right">Bulatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaksiReport as $row)
                        <tr>
                            <td class="text-center">{{ $row->label }}</td>
                            <td class="text-center">
                                <span style="font-size: 10px; padding: 2px 6px; border-radius: 4px; {{ $row->owner_type === 'Produsen' ? 'background: rgba(16,185,129,0.15); color: #10b981;' : 'background: rgba(59,130,246,0.15); color: #3b82f6;' }}">
                                    {{ $row->owner_type === 'Produsen' ? 'PRD' : 'PDG' }}
                                </span>
                            </td>
                            <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format($row->penjualan, 0, ',', '.') }}</td>
                            <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format($row->total_jumlah, 0, ',', '.') }}</td>
                            <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format($row->total_kas, 0, ',', '.') }}</td>
                            <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format($row->tabungan, 0, ',', '.') }}</td>
                            <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format($row->total_kemarin, 0, ',', '.') }}</td>
                            <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format($row->total_pembulatan, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center p-8 opacity-50 italic">Tidak ada data transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($transaksiReport) > 0)
                <tfoot>
                    <tr class="font-bold">
                        <td colspan="2" class="text-center uppercase">Total</td>
                        <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format(collect($transaksiReport)->sum('penjualan'), 0, ',', '.') }}</td>
                        <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format(collect($transaksiReport)->sum('total_jumlah'), 0, ',', '.') }}</td>
                        <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format(collect($transaksiReport)->sum('total_kas'), 0, ',', '.') }}</td>
                        <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format(collect($transaksiReport)->sum('tabungan'), 0, ',', '.') }}</td>
                        <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format(collect($transaksiReport)->sum('total_kemarin'), 0, ',', '.') }}</td>
                        <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format(collect($transaksiReport)->sum('total_pembulatan'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Section 2: Produk Report --}}
    @if(count($produkReport) > 0)
    <div class="box">
        <div style="padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 8px;">
            <span style="font-weight: 700; font-size: 14px;">🛍️ Rangkuman Produk</span>
            <span style="font-size: 11px; opacity: 0.5;">{{ count($produkReport) }} produk</span>
        </div>
        <div class="table-responsive">
            <table class="table table-list">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="text-center">No</th>
                        <th>Produk</th>
                        <th>Produsen</th>
                        <th class="text-right">Omset (HB)</th>
                        <th class="text-right">Laku</th>
                        <th class="text-center">Hari</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($produkReport as $idx => $row)
                        <tr>
                            <td class="text-center opacity-60">{{ $idx + 1 }}</td>
                            <td class="font-bold">{{ $row->nama_produk }}</td>
                            <td style="opacity: 0.7;">{{ $row->nama_produsen }}</td>
                            <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format($row->omset, 0, ',', '.') }}</td>
                            <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format($row->total_laku, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $row->hari }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold">
                        <td colspan="3" class="text-center uppercase">Total</td>
                        <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format(collect($produkReport)->sum('omset'), 0, ',', '.') }}</td>
                        <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format(collect($produkReport)->sum('total_laku'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    {{-- Section 3: Pedagang Report --}}
    @if(count($pedagangReport) > 0)
    <div class="box">
        <div style="padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; gap: 8px;">
            <span style="font-weight: 700; font-size: 14px;">🧑‍💼 Rangkuman Pedagang</span>
            <span style="font-size: 11px; opacity: 0.5;">{{ count($pedagangReport) }} pedagang</span>
        </div>
        <div class="table-responsive">
            <table class="table table-list">
                <thead>
                    <tr>
                        <th style="width: 40px;" class="text-center">No</th>
                        <th>Pedagang</th>
                        <th class="text-right">Omset (HJ)</th>
                        <th class="text-right">Modal (HB)</th>
                        <th class="text-right">Laba</th>
                        <th class="text-center">Trx</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pedagangReport as $idx => $row)
                        <tr>
                            <td class="text-center opacity-60">{{ $idx + 1 }}</td>
                            <td class="font-bold">{{ $row->nama_pedagang }}</td>
                            <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format($row->omset, 0, ',', '.') }}</td>
                            <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format($row->modal, 0, ',', '.') }}</td>
                            <td class="text-right" style="font-family: 'Space Mono', monospace; color: #10b981;">{{ number_format($row->laba, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $row->jumlah_transaksi }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold">
                        <td colspan="2" class="text-center uppercase">Total</td>
                        <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format(collect($pedagangReport)->sum('omset'), 0, ',', '.') }}</td>
                        <td class="text-right" style="font-family: 'Space Mono', monospace;">{{ number_format(collect($pedagangReport)->sum('modal'), 0, ',', '.') }}</td>
                        <td class="text-right" style="font-family: 'Space Mono', monospace; color: #10b981;">{{ number_format(collect($pedagangReport)->sum('laba'), 0, ',', '.') }}</td>
                        <td class="text-center">{{ collect($pedagangReport)->sum('jumlah_transaksi') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
</div>
