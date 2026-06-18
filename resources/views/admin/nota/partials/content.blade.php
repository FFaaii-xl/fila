{{-- Loading State (Akan hilang setelah konten loaded) --}}
<div id="nota-loading" class="nota-loading-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 9999; font-family: 'Outfit', sans-serif;">
    <div style="text-align: center;">
        <div style="width: 40px; height: 40px; border: 3px solid #e5e7eb; border-top-color: #10b981; border-radius: 50%; animation: nota-spin 0.8s linear infinite; margin: 0 auto 16px;"></div>
        <p style="color: #6b7280; font-size: 14px; font-weight: 500;">Memuat Nota...</p>
        <p style="color: #9ca3af; font-size: 11px; margin-top: 4px;">Sedang prepare data penjualan</p>
    </div>
</div>

<style>
@keyframes nota-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.nota-loading-overlay {
    transition: opacity 0.3s ease;
}
.nota-loading-overlay.hidden {
    opacity: 0;
    pointer-events: none;
}
</style>

<script>
// Hide loading after content is ready
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var loading = document.getElementById('nota-loading');
        if (loading) {
            loading.classList.add('hidden');
            setTimeout(function() {
                loading.style.display = 'none';
            }, 300);
        }
    }, 100);
});
// Fallback: hide loading after 3 seconds regardless
setTimeout(function() {
    var loading = document.getElementById('nota-loading');
    if (loading) {
        loading.classList.add('hidden');
        loading.style.display = 'none';
    }
}, 3000);
</script>

@foreach(collect($notads)->chunk(4) as $chunk)
    <section class="page-container" aria-label="Halaman Nota {{ $loop->iteration }}">
        <div class="nota-grid">
            @foreach($chunk as $nota)
                <article class="nota-card" itemscope itemtype="https://schema.org/Receipt">
                    <!-- Header Nota (Legacy Table Style) -->
                    <table class="nota-header-table" style="width: 100%; border-collapse: collapse; margin-bottom: 2px;">
                        <tr>
                            <td rowspan="2" style="width: 35px; border: 1px solid black; text-align: center; font-size: 19px; font-weight: bold; font-family: 'Outfit', sans-serif; font-variant-numeric: tabular-nums;">
                                {{ $nota['no_nota'] }}
                            </td>
                            <td style="border: 1px solid black; padding: 2px 4px; font-weight: 500; font-size: 13px; text-transform: uppercase;">
                                {{ strtoupper(abbreviateProductName($nota['sections'][0]['produk']->nama)) }}
                                @if(count($nota['sections']) > 1)
                                    <span style="font-size: 10px; opacity: 0.6;">(+{{ count($nota['sections']) - 1 }})</span>
                                @endif
                            </td>
                            <td style="width: 80px; border: 1px solid black; text-align: center; font-size: 12px; font-family: 'Outfit', sans-serif; font-variant-numeric: tabular-nums;">
                                {{ date('d/m/Y', strtotime($nota['tanggal'])) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 2px 4px; font-size: 13px; text-transform: uppercase; font-weight: bold;">
                                {{ strtoupper(($nota['produsen']->gender == 'female' ? 'B. ' : 'P. ') . $nota['produsen']->nama) }}
                            </td>
                            <td style="width: 80px; border: 1px solid black; padding: 0; display: table-cell;">
                                <div style="display: flex; height: 100%;">
                                    <div style="flex: 1; border-right: 1px solid black; text-align: center; font-size: 11px; align-self: center;">
                                        @if(count($nota['sections']) === 1)
                                            {{ number_format($nota['sections'][0]['produk']->harga_beli, 0, ',', '.') }}
                                        @else
                                            &nbsp;
                                        @endif
                                    </div>
                                    <div style="flex: 1; text-align: center; font-size: 13px; font-weight: 900; align-self: center;">
                                        {{ ($nota['produsen']->bundle_ke ?? 0) > 0 ? 'B' . $nota['produsen']->bundle_ke : '' }}
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>

                    @foreach($nota['sections'] as $sectionIndex => $section)
                        <!-- Product Table Section -->
                        <div class="product-section" style="{{ $sectionIndex > 0 ? 'margin-top: 4px; border-top: 1px dashed rgba(0,0,0,0.1); padding-top: 2px;' : '' }}">
                            @if(count($nota['sections']) > 1)
                                <div class="section-subtitle uppercase mono" style="font-size: 8.5px; font-weight: bold; margin-bottom: 1px;">
                                    {{ $section['produk']->nama }} ({{ number_format($section['produk']->harga_beli, 0, ',', '.') }})
                                </div>
                            @endif
                            <table class="nota-table" style="table-layout: fixed; width: 100%;">
                                @if($sectionIndex === 0)
                                <thead>
                                    <tr>
                                        <th style="width: 18px;">NO</th>
                                        <th style="text-align: left; width: 85px;">NAMA</th>
                                        <th style="width: 20px;">Ttp</th>
                                        <th style="width: 20px;">S.Jl</th>
                                        <th style="width: 20px;">Rtrn</th>
                                        <th style="width: 20px;">Lku</th>
                                        <th style="width: 42px;">BYAR</th>
                                    </tr>
                                </thead>
                                @endif
                                <tbody>
                                    @foreach($section['items'] as $index => $item)
                                        @php
                                            $isAllZero = $item->titip == 0 && $item->sisa_jual == 0 && $item->ret == 0 && $item->laku == 0 && ($item->f_bayar == 0 || $item->f_bayar === '0');
                                        @endphp
                                        <tr class="tr-lh" {!! $isAllZero ? 'style="background-color: #fff !important;"' : '' !!}>
                                            <td class="m s9" style="width: 18px;">{{ $loop->iteration }}</td>
                                            <td class="{{ $item->is_r ? 'r' : '' }} font-outfit" style="width: 85px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $item->p_display_name }}</td>
                                            <td class="{{ $item->c_titip }} m" style="width: 20px;">{{ $item->titip == 0 ? '' : $item->titip }}</td>
                                            <td class="{{ $item->c_sisa }} m" style="width: 20px;">{{ $item->sisa_jual == 0 ? '' : $item->sisa_jual }}</td>
                                            <td class="{{ $item->c_ret }} m" style="width: 20px;">{{ $item->ret == 0 ? '' : $item->ret }}</td>
                                            <td class="{{ $item->c_laku }} m" style="width: 20px;">{{ $item->laku == 0 ? '' : $item->laku }}</td>
                                            <td class="{{ $item->c_bayar }} m" style="width: 42px;">{{ ($item->f_bayar == 0 || $item->f_bayar === '0') ? '' : $item->f_bayar }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    @php
                                        $avgLaku = $section['sumTitip'] > 0 ? round(($section['sumLaku'] / $section['sumTitip']) * 100) : 0;
                                    @endphp
                                    <tr style="font-weight: bold; font-size: 10px;">
                                        <td colspan="2" style="text-transform: uppercase; width: auto;">{{ 'Laku ' . $avgLaku . '%' }}</td>
                                        <td class="mono" style="width: 20px;">{{ $section['sumTitip'] }}</td>
                                        <td class="mono" style="width: 20px;">{{ $section['sumSisaJual'] }}</td>
                                        <td class="mono" style="width: 20px;">{{ $section['sumReturn'] }}</td>
                                        <td class="mono" style="width: 20px;">{{ $section['sumLaku'] }}</td>
                                        <td class="mono" style="width: 42px;">
                                            @if($section['sumBayar'] >= 1000000)
                                                {{ number_format($section['sumBayar'] / 1000, 0, ',', '.') }}K
                                            @else
                                                {{ number_format($section['sumBayar'], 0, ',', '.') }}
                                            @endif
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endforeach

                    @php
                        $t = $nota['transaksi'];
                        $isOk = $t && strtolower($t->status) === 'ok';
                        
                        // [NUCLEAR_SNAPSHOT_SHIELD] Prioritaskan data dari snapshot jika sudah OK
                        $snapshot = null;
                        if ($isOk && !empty($t->keterangan)) {
                            $decoded = json_decode((string)$t->keterangan, true);
                            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['v'])) {
                                $snapshot = $decoded;
                            }
                        }

                        // [DRY] Gunakan helper central untuk semua sumber data
                        if ($snapshot) {
                            // DATA SNAPSHOT - Pakai nilai dari snapshot
                            $summary = \App\Helpers\NotaSummaryHelper::fromSnapshot($snapshot, $t->id);
                            $summary['lain_source'] = 'SNAPSHOT';
                        } elseif ($isOk) {
                            // LEGACY OK - Ambil langsung dari kolom transaksi
                            $summary = \App\Helpers\NotaSummaryHelper::fromLegacy($nota['totalBayarProdusen'] ?? 0, $t);
                            $summary['lain_source'] = 'HISTORICAL';
                        } else {
                            // SIMULASI DRAFT/PENDING
                            // [NUCLEAR_SYNC] Ambil langsung dari sim_result yang sudah dihitung akurat di Controller
                            // agar nota visual 100% identik dengan hasil print txt (NotaBackupService)
                            $simResult = $nota['sim_result'] ?? [];
                            $summary = [
                                'bayar' => $nota['totalBayarProdusen'] ?? 0,
                                'kas' => $simResult['kas'] ?? 0,
                                'kemarin' => $simResult['kemarin'] ?? 0,
                                'lain' => $simResult['lain'] ?? 0,
                                'tabungan' => $simResult['tabungan'] ?? 0,
                                'pembulatan' => $simResult['pembulatan_adjustment'] ?? 0,
                                'payout' => $simResult['payout'] ?? 0,
                                'lain_source' => 'SIMULASI',
                            ];
                        }
                    @endphp

                    <!-- Keuangan (Hanya Tampil di Nota Pertama Produsen) -->
                    <section class="nota-summary" aria-label="Ringkasan Keuangan">
                        @if($nota['is_first_produk'] ?? false)
                            <div class="summary-left">
                                <div class="row-flex"><span>Bayar</span> <span
                                        class="mono">{{ number_format($summary['bayar'], 0, ',', '.') }}</span></div>
                                <div class="row-flex"><span>Kas</span> <span
                                        class="mono">-{{ number_format($summary['kas'], 0, ',', '.') }}</span></div>
                                <div class="row-flex"><span>Kemarin</span> <span
                                        class="mono">{{ ($summary['kemarin'] >= 0 ? '+' : '') . number_format($summary['kemarin'], 0, ',', '.') }}</span></div>
                            </div>
                            <div class="summary-right">
                                <div class="row-flex"><span>Lain</span> <span
                                        class="mono">{{ ($summary['lain'] >= 0 ? '+' : '-') . number_format(abs($summary['lain']), 0, ',', '.') }}</span></div>
                                <div class="row-flex"><span>Tabungan</span> <span
                                        class="mono">-{{ number_format($summary['tabungan'], 0, ',', '.') }}</span></div>
                                <div class="row-flex"><span>Pembulatan</span> <span class="mono">{{ ($summary['pembulatan'] >= 0 ? '+' : '') . number_format($summary['pembulatan'], 0, ',', '.') }}</span></div>
                            </div>
                        @else
                            <div class="summary-left" style="border-right: none; display: flex; align-items: center; justify-content: center; width: 100%; padding: 4px;">
                                <div style="text-align: center;  width: 100%;">TOTAL BAYAR {{ number_format($nota['totalBayarProdusen'], 0, ',', '.') }} Detail di nota pertama</div>
                            </div>
                        @endif
                    </section>

                    <footer class="nota-footer">
                        @if($nota['is_first_produk'] ?? false)
                        <div class="payout-container" style="background: {{ $theme['payout_bg'] }}; border: 1px solid {{ $theme['payout_border'] }}; padding: 4px 6px; border-radius: 3px; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: center; color: {{ $theme['payout_text'] }};">
                            <span class="payout-label uppercase tracking-widest" style="font-size: 12px; font-weight: 700;">Uang Hari Ini Rp.</span>
                            <span class="payout-value mono" style="font-size: 12px; font-weight: 700;">{{ number_format($summary['payout'], 0, ',', '.') }}</span>
                        </div>
                        @endif
                        
                        <div class="nota-footer-id mono text-center" style="font-size: 8.5px; margin-top: 2px; opacity: 0.8;">
                            {{ $nota['no_nota'] }} | {{ strtoupper($nota['produsen']->nama) }} | {{ abbreviateProductName($nota['sections'][0]['produk']->nama) }} | {{ date('d-m-y', strtotime($nota['tanggal'])) }} | {{ ($nota['produsen']->bundle_ke ?? 0) > 0 ? 'B' . $nota['produsen']->bundle_ke : '' }}
                        </div>
                        
                        <div class="nota-disclaimer text-center italic" style="font-size: 7px; margin-top: 1px; opacity: 0.5;">
                            * PERIKSA UANG SEBELUM PERGI. HUBUNGI ADMIN JIKA ADA SELISIH.
                        </div>
                    </footer>
                </article>
            @endforeach
        </div>
    </section>
@endforeach
