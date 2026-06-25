<div x-data="setoranGrid()" class="setoran-container" :class="waMode ? 'wa-mode-active' : ''">
    {{-- TOOLBAR --}}
    <div class="setoran-toolbar no-print">
        <div class="setoran-toolbar-left">
            <h3 class="setoran-title">📋 Catatan Setoran — {{ $monthLabel }}</h3>
        </div>
        <div class="setoran-toolbar-right">
            <form method="GET" class="setoran-filter-form">
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
                
                <button type="button" @click="toggleAmountMode" class="setoran-btn-amount" :title="showAmountMode ? 'Sembunyikan Nilai' : 'Tampilkan Nilai Setoran'" :class="showAmountMode ? 'active' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-1.07.218-1.6.433-2.325a.983.983 0 01.567-.267c.221 0 .412.087.567.267.215.725.433 1.255.433 2.325zM11 12.5v-1.5c0-1.07-.218-1.6-.433-2.325a.983.983 0 00-.567-.267.983.983 0 00-.567.267C9.07 9.9 8.852 10.43 8.852 11.5v1.5c0 1.07.218 1.6.433 2.325.14.18.332.267.567.267.235 0 .427-.087.567-.267.215-.725.433-1.255.433-2.325zM13.5 3a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM12 9a1 1 0 100-2 1 1 0 000 2z"/>
                    </svg>
                </button>
                <button type="button" @click="toggleWaMode" class="setoran-btn-wa" :title="waMode ? 'Keluar Mode WA' : 'Mode WA/Screenshot'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    {{-- SUMMARY BADGES --}}
    <div class="setoran-summary no-print" x-show="!waMode">
        <span class="setoran-badge setoran-badge-total">Total: {{ $summary['total_transaksi'] }}</span>
        <span class="setoran-badge setoran-badge-ok">OK: {{ $summary['ok'] }}</span>
        <span class="setoran-badge setoran-badge-s">S: {{ $summary['s'] }}</span>
        <span class="setoran-badge setoran-badge-t">Terlambat: {{ $summary['terlambat'] }}</span>
        <span class="setoran-badge setoran-badge-belum">Belum: {{ $summary['belum'] }}</span>
    </div>

    {{-- WA MODE HEADER --}}
    <div x-show="waMode" class="wa-header" style="display: none;">
        <div class="wa-header-actions no-print" style="margin-bottom: 10px; display: flex; justify-content: center; gap: 10px;">
            <button type="button" @click="copyWaMessage" class="wa-btn-copy">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.379a3 3 0 00-.879-2.121L10.5 5.379A3 3 0 008.379 4.5H7v-1z"/>
                    <path d="M4.5 6A1.5 1.5 0 003 7.5v9A1.5 1.5 0 004.5 18h7a1.5 1.5 0 001.5-1.5v-5.879a1.5 1.5 0 00-.44-1.06L9.44 6.439A1.5 1.5 0 008.378 6H4.5z"/>
                </svg>
                <span>Salin Pesan WA</span>
            </button>
            <button type="button" @click="toggleWaMode" class="wa-btn-close">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
                <span>Tutup</span>
            </button>
        </div>
        <div class="wa-header-title">REMINDER SETORAN — {{ strtoupper($monthLabel) }}</div>
        @if($selectedPedagangId && count($grid) > 0)
            <div class="wa-header-target">{{ $grid[0]['nama'] }}</div>
        @endif
    </div>

    {{-- GRID TABLE --}}
    <div class="setoran-table-wrapper" :class="waMode ? 'wa-table-scroll' : ''">
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
                @foreach($grid as $idx => $row)
                    <tr>
                        <td class="setoran-td-no">{{ $idx + 1 }}</td>
                        <td class="setoran-td-nama">{{ $row['nama'] }}</td>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            @php
                                $cell = $row['days'][$d] ?? null;
                                $hasTransaksi = $cell['has_transaksi'] ?? false;
                                $keterangan = $cell['keterangan'] ?? null;
                                $jumlah = $cell['jumlah'] ?? null;
                                $tanggal = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $d);

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
                            <td class="setoran-td-day {{ $cellClass }}" @if($hasTransaksi)
                                x-on:click="showTooltip($event, {{ $row['id'] }}, '{{ $tanggal }}', {{ $jumlah ?? 0 }})"
                                x-on:mouseenter="showTooltip($event, {{ $row['id'] }}, '{{ $tanggal }}', {{ $jumlah ?? 0 }})"
                                x-on:mouseleave="tooltip.show = false"
                                x-on:dblclick.prevent="autoSetoran($event, {{ $row['id'] }}, '{{ $tanggal }}')"
                                x-on:contextmenu.prevent="openStatusMenu($event, {{ $row['id'] }}, '{{ $tanggal }}', '{{ $keterangan }}')"
                            @endif 
                            data-pedagang="{{ $row['id'] }}" data-tanggal="{{ $tanggal }}"
                            data-keterangan="{{ $keterangan ?? '' }}" data-jumlah="{{ $jumlah ?? 0 }}">
                                @if($display === '❌' && ($jumlah ?? 0) > 0)
                                    <span x-show="!showAmountMode">❌</span>
                                    <span x-show="showAmountMode" style="display: none;">{{ rtrim(rtrim(number_format($jumlah / 1000, 1, '.', ''), '0'), '.') }}</span>
                                @else
                                    <span>{{ $display }}</span>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- WA MODE FOOTER --}}
    <div x-show="waMode" class="wa-footer" style="display: none;">
        <div class="wa-footer-legend">
            <span><b>OK</b>: Tepat Waktu</span>
            <span><b>S</b>: Saldo 0</span>
            <span><b>T#</b>: Telat (# Hari)</span>
            <span><b>❌</b>: Belum Setor</span>
        </div>
        <div class="wa-footer-note">Silahkan segera melakukan penyetoran. Terima kasih.</div>
    </div>

    {{-- TOOLTIP --}}
    <div x-show="tooltip.show" x-ref="tooltip" class="setoran-tooltip"
        :style="'top:' + tooltip.y + 'px; left:' + tooltip.x + 'px;'" x-on:click.away="tooltip.show = false">
        <div class="setoran-tooltip-amount" x-text="tooltip.formatted"></div>
        <div class="setoran-tooltip-label">Jumlah Setoran</div>
    </div>

    {{-- STATUS MENU (Right Click) --}}
    <div x-show="menu.show" x-transition.opacity x-ref="menu" class="setoran-menu"
        :style="'top:' + menu.y + 'px; left:' + menu.x + 'px;'" x-on:click.away="menu.show = false">
        <div class="setoran-menu-title">Ubah Status</div>
        <button x-on:click="setStatus('auto')" class="setoran-menu-btn setoran-menu-ok">✨ Otomatis</button>
        <button x-on:click="setStatus('not_late')" class="setoran-menu-btn setoran-menu-s">✅ Tidak Telat</button>
        <button x-on:click="setStatus('late')" class="setoran-menu-btn setoran-menu-t">⚠️ Telat (Auto T#)</button>
        <button x-on:click="setStatus('reset')" class="setoran-menu-btn setoran-menu-reset">✕ Hapus</button>
    </div>

    {{-- MOBILE BOTTOM SHEET (for status selection) --}}
    <div x-show="mobileMenu.show" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-full"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-full"
         class="fixed inset-x-0 bottom-0 z-50 md:hidden">
        <div class="bg-slate-800 rounded-t-2xl border-t border-slate-700 p-4 pb-6 shadow-2xl">
            <div class="w-12 h-1 bg-slate-600 rounded-full mx-auto mb-4"></div>
            <h3 class="text-slate-200 text-center font-bold mb-4">Ubah Status Setoran</h3>
            <div class="text-slate-400 text-center text-xs mb-4">
                <span x-text="mobileMenu.pedagangName"></span> - <span x-text="mobileMenu.tanggal"></span>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <button x-on:click="setStatusMobile('auto')" class="setoran-mobile-btn setoran-mobile-ok">
                    <span class="text-lg">✨</span>
                    <span>Otomatis</span>
                </button>
                <button x-on:click="setStatusMobile('not_late')" class="setoran-mobile-btn setoran-mobile-s">
                    <span class="text-lg">✅</span>
                    <span>Tidak Telat</span>
                </button>
                <button x-on:click="setStatusMobile('late')" class="setoran-mobile-btn setoran-mobile-t">
                    <span class="text-lg">⚠️</span>
                    <span>Telat</span>
                </button>
                <button x-on:click="setStatusMobile('reset')" class="setoran-mobile-btn setoran-mobile-reset">
                    <span class="text-lg">✕</span>
                    <span>Hapus</span>
                </button>
            </div>
            <button x-on:click="mobileMenu.show = false" class="w-full mt-4 py-3 px-4 bg-slate-700 text-slate-300 rounded-lg font-medium">
                Batal
            </button>
        </div>
    </div>
    
    {{-- MOBILE BACKDROP --}}
    <div x-show="mobileMenu.show" x-transition.opacity class="fixed inset-0 bg-black/50 z-40 md:hidden"
         x-on:click="mobileMenu.show = false"></div>
</div>

{{-- STYLES --}}
<style>
    .setoran-container {
        position: relative;
    }

    .setoran-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
        padding: 12px 16px;
        background: rgba(30, 41, 59, 0.5);
        border-radius: 8px;
    }

    .setoran-title {
        font-size: 16px;
        font-weight: 700;
        color: #e2e8f0;
        margin: 0;
    }

    .setoran-filter-form {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
    }

    .setoran-select {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #e2e8f0;
        border-radius: 6px;
        padding: 6px 10px;
        font-size: 12px;
        cursor: pointer;
    }

    .setoran-select-pedagang {
        min-width: 140px;
    }

    .setoran-btn-amount, .setoran-btn-wa {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }

    .setoran-btn-amount {
        background: rgba(56, 189, 248, 0.1);
        color: #38bdf8;
    }

    .setoran-btn-amount:hover, .setoran-btn-amount.active {
        background: #38bdf8;
        color: white;
    }

    .setoran-btn-wa {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }

    .setoran-btn-wa:hover {
        background: #10b981;
        color: white;
    }

    .setoran-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
        padding: 0 4px;
    }

    .setoran-badge {
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .setoran-badge-total {
        background: rgba(148, 163, 184, 0.15);
        color: #94a3b8;
    }

    .setoran-badge-ok {
        background: rgba(16, 185, 129, 0.15);
        color: #10b981;
    }

    .setoran-badge-s {
        background: rgba(56, 189, 248, 0.15);
        color: #38bdf8;
    }

    .setoran-badge-t {
        background: rgba(251, 191, 36, 0.15);
        color: #fbbf24;
    }

    .setoran-badge-belum {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
    }

    .setoran-table-wrapper {
        overflow-x: auto;
        border-radius: 8px;
        background: rgba(30, 41, 59, 0.5);
    }

    .setoran-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
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

    .setoran-th-no { width: 30px; }
    .setoran-th-nama { width: 120px; text-align: left; position: sticky; left: 0; z-index: 3; background: #1e293b; }
    .setoran-th-day { width: 36px; min-width: 36px; }

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

    .setoran-td-no { color: #475569; }
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

    .setoran-cell-empty { color: #1e293b; cursor: default; }
    .setoran-cell-ok { background: rgba(16, 185, 129, 0.15); color: #10b981; cursor: pointer; }
    .setoran-cell-s { background: rgba(56, 189, 248, 0.15); color: #38bdf8; cursor: pointer; }
    .setoran-cell-t { background: rgba(251, 191, 36, 0.15); color: #fbbf24; cursor: pointer; }
    .setoran-cell-belum { background: rgba(239, 68, 68, 0.12); color: #ef4444; cursor: pointer; }

    .setoran-cell-ok:hover, .setoran-cell-s:hover, .setoran-cell-t:hover, .setoran-cell-belum:hover {
        filter: brightness(1.3);
    }

    .setoran-table tbody tr:hover .setoran-td-nama { color: #f1f5f9; }
    .setoran-table tbody tr:hover { background: rgba(255, 255, 255, 0.02); }

    /* Tooltip */
    .setoran-tooltip {
        position: fixed;
        z-index: 1000;
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 8px;
        padding: 8px 14px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        pointer-events: none;
    }

    .setoran-tooltip-amount {
        font-size: 14px;
        font-weight: 800;
        color: #f1f5f9;
    }

    .setoran-tooltip-label {
        font-size: 8px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-top: 2px;
    }

    /* Context Menu */
    .setoran-menu {
        position: fixed;
        z-index: 1001;
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 8px;
        padding: 6px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
        display: flex;
        flex-direction: column;
        gap: 3px;
        min-width: 100px;
    }

    .setoran-menu-title {
        font-size: 8px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        padding: 2px 6px;
        font-weight: 700;
    }

    .setoran-menu-btn {
        padding: 6px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        text-align: left;
        transition: background 0.15s;
    }

    .setoran-menu-ok { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .setoran-menu-s { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
    .setoran-menu-t { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .setoran-menu-reset { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

    .setoran-menu-btn:hover { filter: brightness(1.2); }

    /* Mobile Bottom Sheet Buttons */
    .setoran-mobile-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 16px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }

    .setoran-mobile-ok { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .setoran-mobile-s { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
    .setoran-mobile-t { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .setoran-mobile-reset { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

    /* WA Mode */
    .wa-mode-active .setoran-table {
        font-size: 12px;
    }

    .wa-mode-active .setoran-table th, 
    .wa-mode-active .setoran-table td {
        font-size: 12px;
        height: 36px;
        padding: 8px 6px;
    }

    .wa-mode-active .setoran-th-nama, 
    .wa-mode-active .setoran-td-nama {
        font-size: 13px;
        width: 140px;
        max-width: 140px;
    }

    .wa-header {
        margin-bottom: 20px;
        text-align: center;
    }

    .wa-header-title {
        font-size: 18px;
        font-weight: 900;
        color: #10b981;
        letter-spacing: 2px;
    }

    .wa-header-target {
        font-size: 22px;
        font-weight: 800;
        color: #f1f5f9;
        margin-top: 10px;
        border-bottom: 2px solid #10b981;
        display: inline-block;
        padding: 0 20px 5px;
    }

    .wa-footer {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px dashed rgba(255,255,255,0.1);
        text-align: center;
    }

    .wa-footer-legend {
        display: flex;
        justify-content: center;
        gap: 15px;
        font-size: 11px;
        color: #94a3b8;
        margin-bottom: 10px;
    }

    .wa-footer-note {
        font-size: 12px;
        font-style: italic;
        color: #64748b;
    }

    .wa-btn-copy, .wa-btn-close {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }

    .wa-btn-copy { background: #10b981; color: white; border: none; }
    .wa-btn-copy:hover { background: #059669; }
    .wa-btn-close { background: rgba(255, 255, 255, 0.1); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.2); }
    .wa-btn-close:hover { background: rgba(255, 255, 255, 0.2); }
</style>

{{-- ALPINE.JS LOGIC --}}
<script>
    function setoranGrid() {
        return {
            tooltip: { show: false, x: 0, y: 0, formatted: '' },
            menu: { show: false, x: 0, y: 0, pedagangId: null, tanggal: null, currentStatus: null },
            mobileMenu: { show: false, pedagangId: null, tanggal: null, pedagangName: '', status: null },
            waMode: false,
            showAmountMode: false,

            toggleAmountMode() {
                this.showAmountMode = !this.showAmountMode;
            },

            toggleWaMode() {
                this.waMode = !this.waMode;
                if (this.waMode) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = 'auto';
                }
            },

            copyWaMessage() {
                const targetName = document.querySelector('.wa-header-target')?.textContent || 'Pelanggan';
                const month = '{{ $monthLabel }}';
                
                const missingDays = [];
                document.querySelectorAll('.setoran-table tbody tr').forEach(row => {
                    row.querySelectorAll('.setoran-cell-belum').forEach(cell => {
                        const date = cell.dataset.tanggal;
                        if (date) {
                            const d = new Intl.DateTimeFormat('id-ID', { day: 'numeric' }).format(new Date(date));
                            missingDays.push(d);
                        }
                    });
                });

                if (missingDays.length === 0) {
                    alert('Tidak ada data setoran yang kurang.');
                    return;
                }

                const message = `*REMINDER SETORAN — ${month.toUpperCase()}*\n\nYth. *${targetName}*,\n\nBerikut adalah catatan setoran yang belum masuk:\n📅 Tanggal: *${missingDays.join(', ')}*\n\nMohon segera melakukan penyetoran. Terima kasih. 🙏`;

                navigator.clipboard.writeText(message).then(() => {
                    alert('Pesan WA berhasil disalin!');
                }).catch(err => {
                    console.error('Copy failed:', err);
                });
            },

            showTooltip(event, pedagangId, tanggal, jumlah) {
                if (this.waMode) return;
                this.menu.show = false;
                this.mobileMenu.show = false;
                this.tooltip.formatted = 'Rp ' + new Intl.NumberFormat('id-ID').format(jumlah);
                this.tooltip.x = event.clientX + 12;
                this.tooltip.y = event.clientY - 40;
                this.tooltip.show = true;
            },

            openStatusMenu(event, pedagangId, tanggal, currentStatus) {
                if (this.waMode) return;
                event.preventDefault();
                this.tooltip.show = false;
                this.mobileMenu.show = false;
                
                // Detect mobile
                if (window.innerWidth < 768) {
                    this.openMobileMenu(pedagangId, tanggal, currentStatus);
                    return;
                }
                
                this.menu.pedagangId = pedagangId;
                this.menu.tanggal = tanggal;
                this.menu.currentStatus = currentStatus;
                this.menu.x = event.clientX + 8;
                this.menu.y = event.clientY - 20;
                this.menu.show = true;
            },

            openMobileMenu(pedagangId, tanggal, currentStatus) {
                const row = document.querySelector(`td[data-pedagang="${pedagangId}"]`)?.closest('tr');
                const name = row?.querySelector('.setoran-td-nama')?.textContent || 'Pedagang';
                
                this.mobileMenu.pedagangId = pedagangId;
                this.mobileMenu.tanggal = tanggal;
                this.mobileMenu.pedagangName = name;
                this.mobileMenu.status = currentStatus;
                this.mobileMenu.show = true;
            },

            autoSetoran(event, pedagangId, tanggal) {
                if (this.waMode) return;
                this.menu.pedagangId = pedagangId;
                this.menu.tanggal = tanggal;
                this.setStatus('auto');
            },

            async setStatus(status) {
                const pedagangId = this.menu.pedagangId;
                const tanggal = this.menu.tanggal;
                this.menu.show = false;
                await this.updateSetoran(pedagangId, tanggal, status);
            },

            async setStatusMobile(status) {
                const pedagangId = this.mobileMenu.pedagangId;
                const tanggal = this.mobileMenu.tanggal;
                this.mobileMenu.show = false;
                await this.updateSetoran(pedagangId, tanggal, status);
            },

            async updateSetoran(pedagangId, tanggal, status) {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content 
                        || document.querySelector('input[name="_token"]')?.value;
                    
                    const response = await fetch('/admin/setoran/toggle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ pedagang_id: pedagangId, tanggal: tanggal, status: status }),
                    });

                    if (response.ok) {
                        const result = await response.json();
                        this.updateCell(pedagangId, tanggal, result.keterangan);
                    }
                } catch (err) {
                    console.error('Toggle failed:', err);
                }
            },

            updateCell(pedagangId, tanggal, keterangan) {
                const cell = document.querySelector(
                    `td[data-pedagang="${pedagangId}"][data-tanggal="${tanggal}"]`
                );
                if (!cell) return;

                cell.classList.remove('setoran-cell-ok', 'setoran-cell-s', 'setoran-cell-t', 'setoran-cell-belum');
                cell.dataset.keterangan = keterangan || '';

                let display = '❌';
                let cls = 'setoran-cell-belum';

                if (keterangan === 'Ok') { display = 'OK'; cls = 'setoran-cell-ok'; }
                else if (keterangan === 'S') { display = 'S'; cls = 'setoran-cell-s'; }
                else if (keterangan && keterangan.startsWith('T')) { display = keterangan; cls = 'setoran-cell-t'; }
                else if (!keterangan) { display = '❌'; cls = 'setoran-cell-belum'; }

                cell.classList.add(cls);
                
                const spanMissing = cell.querySelector('span[x-show="!showAmountMode"]');
                const spanAmount = cell.querySelector('span[x-show="showAmountMode"]');
                
                if (spanMissing && spanAmount) {
                    if (display === '❌') {
                        // Keep structured spans for amount mode
                    } else {
                        cell.innerHTML = `<span>${display}</span>`;
                    }
                } else {
                    if (display === '❌' && Number(cell.dataset.jumlah) > 0) {
                        const amt = Number(cell.dataset.jumlah) / 1000;
                        const formatted = Number.isInteger(amt) ? amt.toString() : amt.toFixed(1).replace(/\.0$/, '');
                        cell.innerHTML = `<span x-show="!showAmountMode">❌</span><span x-show="showAmountMode" style="display: ${this.showAmountMode ? 'inline' : 'none'};">${formatted}</span>`;
                    } else {
                        cell.innerHTML = `<span>${display}</span>`;
                    }
                }
            },
        };
    }
</script>
