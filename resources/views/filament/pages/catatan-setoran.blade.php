<div class="fi-page bg-slate-900 min-h-screen text-slate-200">
    {{-- Header --}}
    <div class="bg-slate-800 border-b border-slate-700 px-4 py-4">
        <div class="max-w-full mx-auto">
            <h1 class="text-2xl font-bold text-slate-100">📋 Catatan Setoran — {{ $monthLabel }}</h1>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="max-w-full mx-auto px-4 py-3">
        <div class="flex flex-wrap gap-2">
            <span class="setoran-badge setoran-badge-total">Total: {{ $summary['total_transaksi'] ?? 0 }}</span>
            <span class="setoran-badge setoran-badge-ok">OK: {{ $summary['ok'] ?? 0 }}</span>
            <span class="setoran-badge setoran-badge-s">S: {{ $summary['s'] ?? 0 }}</span>
            <span class="setoran-badge setoran-badge-t">Terlambat: {{ $summary['terlambat'] ?? 0 }}</span>
            <span class="setoran-badge setoran-badge-belum">Belum: {{ $summary['belum'] ?? 0 }}</span>
        </div>
    </div>

    {{-- Filters --}}
    <div class="max-w-full mx-auto px-4 pb-3">
        <form method="GET" action="" class="flex flex-wrap gap-2 items-center">
            <select name="pedagang_id" onchange="this.form.submit()" class="setoran-select setoran-select-pedagang">
                <option value="">-- Semua Pedagang --</option>
                @foreach($pedagangOptions as $id => $nama)
                    <option value="{{ $id }}" {{ $id == $selectedPedagangId ? 'selected' : '' }}>{{ $nama }}</option>
                @endforeach
            </select>
            <select name="month" onchange="this.form.submit()" class="setoran-select">
                @foreach($monthOptions as $m => $label)
                    <option value="{{ $m }}" {{ $m == $selectedMonth ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="year" onchange="this.form.submit()" class="setoran-select">
                @foreach($yearOptions as $y)
                    <option value="{{ $y }}" {{ $y == $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Grid Table --}}
    <div class="max-w-full mx-auto px-4 pb-6 overflow-x-auto">
        <div class="bg-slate-800 rounded-lg border border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="setoran-table">
                    <thead>
                        <tr>
                            <th class="setoran-th-no">#</th>
                            <th class="setoran-th-nama">Nama</th>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                <th class="setoran-th-day">{{ $d }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grid as $idx => $row)
                            <tr>
                                <td class="setoran-td-no">{{ $idx + 1 }}</td>
                                <td class="setoran-td-nama">{{ $row['nama'] }}</td>
                                @for($d = 1; $d <= $daysInMonth; $d++)
                                    @php
                                        $cell = $row['days'][$d] ?? null;
                                        $hasTransaksi = $cell['has_transaksi'] ?? false;
                                        $keterangan = $cell['keterangan'] ?? null;
                                        $jumlah = $cell['jumlah'] ?? null;

                                        // Determine cell display & color
                                        $display = '-';
                                        $cellClass = 'setoran-cell-empty';

                                        if ($hasTransaksi) {
                                            if ($keterangan === 'Ok') {
                                                $display = 'OK';
                                                $cellClass = 'setoran-cell-ok';
                                            } elseif ($keterangan === 'S') {
                                                $display = 'S';
                                                $cellClass = 'setoran-cell-s';
                                            } elseif ($keterangan && str_starts_with($keterangan, 'T')) {
                                                $display = $keterangan;
                                                $cellClass = 'setoran-cell-t';
                                            } else {
                                                $display = '❌';
                                                $cellClass = 'setoran-cell-belum';
                                            }
                                        }
                                    @endphp
                                    <td class="setoran-td-day {{ $cellClass }}" 
                                        data-pedagang="{{ $row['id'] }}" 
                                        data-tanggal="{{ sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $d) }}"
                                        data-keterangan="{{ $keterangan ?? '' }}"
                                        data-jumlah="{{ $jumlah ?? 0 }}"
                                        title="{{ $jumlah ? 'Rp ' . number_format($jumlah, 0, ',', '.') : '' }}">
                                        <span>{{ $display }}</span>
                                    </td>
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $daysInMonth + 2 }}" class="px-4 py-8 text-center text-slate-500">
                                    Tidak ada data setoran untuk periode ini
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="max-w-full mx-auto px-4 pb-6">
        <div class="bg-slate-800 rounded-lg border border-slate-700 p-4">
            <h3 class="text-sm font-medium text-slate-400 mb-2">Legenda:</h3>
            <div class="flex flex-wrap gap-4 text-xs">
                <div class="flex items-center gap-2">
                    <span class="setoran-cell-ok px-2 py-1 rounded text-xs font-bold">OK</span>
                    <span class="text-slate-400">Tepat Waktu</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="setoran-cell-s px-2 py-1 rounded text-xs font-bold">S</span>
                    <span class="text-slate-400">Saldo 0</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="setoran-cell-t px-2 py-1 rounded text-xs font-bold">T1</span>
                    <span class="text-slate-400">Terlambat</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="setoran-cell-belum px-2 py-1 rounded text-xs font-bold">❌</span>
                    <span class="text-slate-400">Belum Setor</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="setoran-cell-empty px-2 py-1 rounded text-xs text-slate-600">-</span>
                    <span class="text-slate-400">Tidak ada transaksi</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- STYLES --}}
@push('styles')
<style>
    .setoran-table {
        width: 100%;
        border-collapse: collapse;
        font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
        letter-spacing: 0.2px;
    }

    .setoran-table thead {
        background: rgba(255, 255, 255, 0.03);
    }

    .setoran-table th {
        padding: 8px 4px;
        text-align: center;
        color: #64748b;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 2;
        background: #1e293b;
    }

    .setoran-th-no {
        width: 30px;
    }

    .setoran-th-nama {
        width: 120px;
        text-align: left;
        position: sticky;
        left: 0;
        z-index: 3;
        background: #1e293b;
    }

    .setoran-th-day {
        width: 36px;
        min-width: 36px;
    }

    .setoran-table td {
        padding: 6px 4px;
        text-align: center;
        font-size: 10px;
        font-weight: 700;
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        border-right: 1px solid rgba(255, 255, 255, 0.02);
        transition: all 0.15s;
        height: 32px;
    }

    .setoran-td-no {
        color: #475569;
    }

    .setoran-td-nama {
        text-align: left;
        color: #cbd5e1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px;
        position: sticky;
        left: 0;
        z-index: 1;
        background: #1e293b;
    }

    .setoran-cell-empty { color: #1e293b; }
    .setoran-cell-ok { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .setoran-cell-s { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
    .setoran-cell-t { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .setoran-cell-belum { background: rgba(239, 68, 68, 0.12); color: #ef4444; }

    .setoran-table tbody tr:hover .setoran-td-nama { color: #f1f5f9; }
    .setoran-table tbody tr:hover { background: rgba(255, 255, 255, 0.02); }

    .setoran-select {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #e2e8f0;
        border-radius: 4px;
        padding: 4px 8px;
        font-size: 11px;
        cursor: pointer;
    }

    .setoran-select:focus {
        outline: none;
        border-color: #10b981;
    }

    .setoran-select-pedagang {
        min-width: 160px;
        background: rgba(16, 185, 129, 0.05);
        border-color: rgba(16, 185, 129, 0.2);
    }

    .setoran-badge {
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .setoran-badge-total {
        background: rgba(148, 163, 184, 0.15);
        color: #94a3b8;
        border: 1px solid rgba(148, 163, 184, 0.2);
    }

    .setoran-badge-ok {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .setoran-badge-s {
        background: rgba(56, 189, 248, 0.15);
        color: #38bdf8;
        border: 1px solid rgba(56, 189, 248, 0.2);
    }

    .setoran-badge-t {
        background: rgba(251, 191, 36, 0.15);
        color: #fbbf24;
        border: 1px solid rgba(251, 191, 36, 0.2);
    }

    .setoran-badge-belum {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
</style>
@endpush
