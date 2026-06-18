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
                        <option value="{{ $id }}" {{ $id == $pedagangId ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
                <select name="month" onchange="this.form.submit()" class="setoran-select">
                    @foreach($monthOptions as $m => $label)
                        <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="year" onchange="this.form.submit()" class="setoran-select">
                    @foreach($yearOptions as $y)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                
                <button type="button" @click="toggleAmountMode" class="setoran-btn-amount" :title="showAmountMode ? 'Sembunyikan Nilai' : 'Tampilkan Nilai Setoran'" :class="showAmountMode ? 'active' : ''">
                    <x-moonshine::icon icon="currency-dollar" size="4" />
                </button>
                <button type="button" @click="toggleWaMode" class="setoran-btn-wa" :title="waMode ? 'Keluar Mode WA' : 'Mode WA/Screenshot'">
                    <x-moonshine::icon icon="chat-bubble-left-right" size="4" />
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

    {{-- WA MODE HEADER (Only visible in WA mode) --}}
    <div x-show="waMode" class="wa-header" style="display: none;">
        <div class="wa-header-actions no-print" style="margin-bottom: 10px; display: flex; justify-content: center; gap: 10px;">
            <button type="button" @click="copyWaMessage" class="wa-btn-copy">
                <x-moonshine::icon icon="document-duplicate" size="4" />
                <span>Salin Pesan WA</span>
            </button>
            <button type="button" @click="toggleWaMode" class="wa-btn-close">
                <x-moonshine::icon icon="x-mark" size="4" />
                <span>Tutup</span>
            </button>
        </div>
        <div class="wa-header-title">REMINDER SETORAN — {{ strtoupper($monthLabel) }}</div>
        @if($pedagangId && count($grid) > 0)
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
                                $cell = $row['days'][$d];
                                $hasTransaksi = $cell['has_transaksi'];
                                $keterangan = $cell['keterangan'];
                                $jumlah = $cell['jumlah'];
                                $tanggal = sprintf('%04d-%02d-%02d', $year, $month, $d);

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
                            <td class="setoran-td-day {{ $cellClass }}" @if($hasTransaksi)
                                x-on:click="showTooltip($event, {{ $row['id'] }}, '{{ $tanggal }}', {{ $jumlah ?? 0 }})"
                                x-on:mouseenter="showTooltip($event, {{ $row['id'] }}, '{{ $tanggal }}', {{ $jumlah ?? 0 }})"
                                x-on:mouseleave="tooltip.show = false"
                                x-on:dblclick.prevent="autoSetoran($event, {{ $row['id'] }}, '{{ $tanggal }}')"
                                x-on:contextmenu.prevent="openStatusMenu($event, {{ $row['id'] }}, '{{ $tanggal }}', '{{ $keterangan }}')"
                            @endif data-pedagang="{{ $row['id'] }}" data-tanggal="{{ $tanggal }}"
                                data-keterangan="{{ $keterangan }}" data-jumlah="{{ $jumlah ?? 0 }}">
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

    {{-- TOOLTIP (appears on single click) --}}
    <div x-show="tooltip.show" x-ref="tooltip" class="setoran-tooltip"
        :style="'top:' + tooltip.y + 'px; left:' + tooltip.x + 'px;'" x-on:click.away="tooltip.show = false">
        <div class="setoran-tooltip-amount" x-text="tooltip.formatted"></div>
        <div class="setoran-tooltip-label">Jumlah Setoran</div>
    </div>

    {{-- STATUS MENU (appears on right click) --}}
    <div x-show="menu.show" x-transition.opacity x-ref="menu" class="setoran-menu"
        :style="'top:' + menu.y + 'px; left:' + menu.x + 'px;'" x-on:click.away="menu.show = false">
        <div class="setoran-menu-title">Ubah Status</div>
        <button x-on:click="setStatus('auto')" class="setoran-menu-btn setoran-menu-ok">✨ Otomatis</button>
        <button x-on:click="setStatus('not_late')" class="setoran-menu-btn setoran-menu-s">✅ Tidak Telat</button>
        <button x-on:click="setStatus('late')" class="setoran-menu-btn setoran-menu-t">⚠️ Telat (Auto T#)</button>
        <button x-on:click="setStatus('reset')" class="setoran-menu-btn setoran-menu-reset">✕ Hapus</button>
    </div>
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
        gap: 4px;
        margin-bottom: 4px;
    }

    .setoran-title {
        font-family: 'Playfair Display', serif;
        font-size: 14px;
        font-weight: 800;
        color: #e2e8f0;
        margin: 0;
    }

    .setoran-filter-form {
        display: flex;
        gap: 4px;
        align-items: center;
    }

    .setoran-select {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #e2e8f0;
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 10px;
        font-family: 'Outfit', sans-serif;
        cursor: pointer;
    }

    .setoran-select-pedagang {
        min-width: 140px;
        background: rgba(16, 185, 129, 0.05);
        border-color: rgba(16, 185, 129, 0.2);
    }

    .setoran-btn-amount {
        background: rgba(56, 189, 248, 0.1);
        color: #38bdf8;
        border: 1px solid rgba(56, 189, 248, 0.3);
        padding: 4px 6px;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .setoran-btn-amount:hover, .setoran-btn-amount.active {
        background: #38bdf8;
        color: white;
    }

    .setoran-btn-wa {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.3);
        padding: 4px 6px;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .setoran-btn-wa:hover {
        background: #10b981;
        color: white;
    }

    .setoran-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-bottom: 4px;
    }

    .setoran-badge {
        padding: 1px 8px;
        border-radius: 12px;
        font-size: 9px;
        font-weight: 700;
        font-family: 'Space Mono', monospace;
        text-transform: uppercase;
    }

    .setoran-badge-total {
        background: rgba(148, 163, 184, 0.15);
        color: #94a3b8;
        border: 1px solid rgba(148, 163, 184, 0.2);
    }

    .setoran-badge-ok {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .setoran-badge-s {
        background: rgba(56, 189, 248, 0.1);
        color: #38bdf8;
        border: 1px solid rgba(56, 189, 248, 0.2);
    }

    .setoran-badge-t {
        background: rgba(251, 191, 36, 0.1);
        color: #fbbf24;
        border: 1px solid rgba(251, 191, 36, 0.2);
    }

    .setoran-badge-belum {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .setoran-table-wrapper {
        overflow-x: auto;
        border-radius: 4px;
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .setoran-table {
        width: 100%;
        border-collapse: collapse;
        font-family: 'Outfit', sans-serif;
        letter-spacing: 0.2px;
    }

    .setoran-table thead {
        background: rgba(255, 255, 255, 0.03);
    }

    .setoran-table th {
        padding: 6px 4px;
        text-align: center;
        color: #64748b;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 2;
        background: #0f172a;
    }

    .setoran-th-no {
        width: 25px;
    }

    .setoran-th-nama {
        width: 100px;
        text-align: left;
        position: sticky;
        left: 0;
        z-index: 3;
        background: #0f172a;
    }

    .setoran-th-day {
        width: 36px;
        min-width: 36px;
    }

    .setoran-table td {
        padding: 6px 4px;
        text-align: center;
        font-size: 9px;
        font-weight: 700;
        border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        border-right: 1px solid rgba(255, 255, 255, 0.02);
        transition: background 0.15s;
        height: 28px;
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
        max-width: 100px;
        position: sticky;
        left: 0;
        z-index: 1;
        background: #0f172a;
    }

    .setoran-cell-empty { color: #1e293b; cursor: default; }
    .setoran-cell-ok { background: rgba(16, 185, 129, 0.12); color: #10b981; cursor: pointer; }
    .setoran-cell-s { background: rgba(56, 189, 248, 0.12); color: #38bdf8; cursor: pointer; }
    .setoran-cell-t { background: rgba(251, 191, 36, 0.12); color: #fbbf24; cursor: pointer; }
    .setoran-cell-belum { background: rgba(239, 68, 68, 0.08); color: #ef4444; cursor: pointer; }

    .setoran-cell-ok:hover, .setoran-cell-s:hover, .setoran-cell-t:hover, .setoran-cell-belum:hover {
        filter: brightness(1.3);
        transform: scale(1.1);
        z-index: 5;
        position: relative;
    }

    /* Tooltip & Menu */
    .setoran-tooltip { position: fixed; z-index: 1000; background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 8px; padding: 8px 14px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5); pointer-events: none; }
    .setoran-tooltip-amount { font-size: 14px; font-weight: 800; color: #f1f5f9; font-family: 'Space Mono', monospace; }
    .setoran-tooltip-label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 2px; }
    .setoran-menu { position: fixed; z-index: 1001; background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 8px; padding: 6px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6); display: flex; flex-direction: column; gap: 3px; min-width: 90px; }
    .setoran-menu-title { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.15em; padding: 2px 6px; font-weight: 700; }
    .setoran-menu-btn { padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 700; border: none; cursor: pointer; text-align: left; transition: background 0.15s; }
    .setoran-menu-ok { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .setoran-menu-s { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
    .setoran-menu-t { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
    .setoran-menu-reset { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

    /* --- WA MODE STYLES --- */
    .wa-mode-active {
        background: #0f172a !important;
        padding: 20px !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 99999 !important;
        overflow-y: auto !important;
    }

    .wa-header {
        margin-bottom: 20px;
        text-align: center;
    }

    .wa-header-title {
        font-family: 'Playfair Display', serif;
        font-size: 20px;
        font-weight: 900;
        color: #10b981;
        letter-spacing: 2px;
    }

    .wa-header-target {
        font-family: 'Outfit', sans-serif;
        font-size: 24px;
        font-weight: 800;
        color: #f1f5f9;
        margin-top: 10px;
        border-bottom: 2px solid #10b981;
        display: inline-block;
        padding: 0 20px 5px;
    }

    .wa-mode-active .setoran-table {
        font-size: 11px; /* Bigger for screenshot */
    }

    .wa-mode-active .setoran-table th, 
    .wa-mode-active .setoran-table td {
        font-size: 11px;
        height: 32px;
        padding: 8px 6px;
    }

    .wa-mode-active .setoran-th-nama, 
    .wa-mode-active .setoran-td-nama {
        font-size: 12px;
        width: 140px;
        max-width: 140px;
    }

    .wa-footer {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px dashed rgba(255,255,255,0.1);
    }

    .wa-footer-legend {
        display: flex;
        justify-content: center;
        gap: 15px;
        font-size: 10px;
        color: #94a3b8;
        margin-bottom: 10px;
    }

    .wa-footer-note {
        text-align: center;
        font-size: 12px;
        font-style: italic;
        color: #64748b;
    }

    .wa-btn-copy, .wa-btn-close {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
    }

    .wa-btn-copy {
        background: #10b981;
        color: white;
        border: none;
    }

    .wa-btn-copy:hover { background: #059669; }

    .wa-btn-close {
        background: rgba(255, 255, 255, 0.1);
        color: #cbd5e1;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .wa-btn-close:hover { background: rgba(255, 255, 255, 0.2); }

    .wa-table-scroll {
        overflow-x: visible !important;
    }

    /* Row hover */
    .setoran-table tbody tr:hover .setoran-td-nama { color: #f1f5f9; }
    .setoran-table tbody tr:hover { background: rgba(255, 255, 255, 0.02); }
</style>

{{-- ALPINE.JS LOGIC --}}
<script>
    function setoranGrid() {
        return {
            tooltip: { show: false, x: 0, y: 0, formatted: '' },
            menu: { show: false, x: 0, y: 0, pedagangId: null, tanggal: null, currentStatus: null },
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
                
                // Find all missing days (❌)
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
                this.tooltip.formatted = 'Rp ' + new Intl.NumberFormat('id-ID').format(jumlah);
                this.tooltip.x = event.clientX + 12;
                this.tooltip.y = event.clientY - 40;
                this.tooltip.show = true;
            },

            openStatusMenu(event, pedagangId, tanggal, currentStatus) {
                if (this.waMode) return;
                event.preventDefault();
                this.tooltip.show = false;
                this.menu.pedagangId = pedagangId;
                this.menu.tanggal = tanggal;
                this.menu.currentStatus = currentStatus;
                this.menu.x = event.clientX + 8;
                this.menu.y = event.clientY - 20;
                this.menu.show = true;
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

                try {
                    const response = await fetch('/admin/setoran/toggle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
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

                // Remove old classes
                cell.classList.remove('setoran-cell-ok', 'setoran-cell-s', 'setoran-cell-t', 'setoran-cell-belum');
                cell.dataset.keterangan = keterangan || '';

                let display = '❌';
                let cls = 'setoran-cell-belum';

                if (keterangan === 'Ok') { display = 'OK'; cls = 'setoran-cell-ok'; }
                else if (keterangan === 'S') { display = 'S'; cls = 'setoran-cell-s'; }
                else if (keterangan && keterangan.startsWith('T')) { display = keterangan; cls = 'setoran-cell-t'; }
                else if (!keterangan) { display = '❌'; cls = 'setoran-cell-belum'; }

                cell.classList.add(cls);
                
                // Update internal spans if they exist (for showAmountMode compatibility)
                const spanMissing = cell.querySelector('span[x-show="!showAmountMode"]');
                const spanAmount = cell.querySelector('span[x-show="showAmountMode"]');
                
                if (spanMissing && spanAmount) {
                    if (display === '❌') {
                        // Keep the structured spans for amount mode
                    } else {
                        // Overwrite with plain text for OK/S/T
                        cell.innerHTML = `<span>${display}</span>`;
                    }
                } else {
                    if (display === '❌' && Number(cell.dataset.jumlah) > 0) {
                        // Recreate the structure if we switched back to missing
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