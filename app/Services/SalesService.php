<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pedagang;
use App\Traits\MerchantFinancialRules;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class SalesService
{
    use MerchantFinancialRules;

    /**
     * SELF-HEALING SENTINEL:
     * Memeriksa integritas data summary untuk N hari terakhir.
     * Jika ditemukan mismatch antara Penjualan (Legacy) dan Summary (Laravel),
     * picu hitung ulang otomatis.
     */
    public function sentinelRepair(int $days = 3): void
    {
        $cacheKey = 'sentinel_last_patrol';
        // Throttle: Patroli hanya berjalan maksimal sekali setiap 5 menit
        if (Cache::has($cacheKey)) {
            return;
        }

        $repairsDone = 0;
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $start = $date.' 00:00:00';
            $end = $date.' 23:59:59';

            // LIGHTWEIGHT CHECK: Bandingkan MAX(updated_at) tabel penjualan dengan MAX(updated_at) summary
            $lastSaleUpdate = DB::table('penjualan')
                ->whereNull('deleted_at')
                ->where('status', 'Ok')
                ->whereBetween('tanggal', [$start, $end])
                ->max('updated_at');

            // Jika tidak ada penjualan sama sekali, abaikan
            if (! $lastSaleUpdate) {
                continue;
            }

            $lastSummaryUpdate = DB::table('sales_summaries')
                ->where('date', $date)
                ->max('updated_at');

            // HEALING TRIGGER: Jika summary belum ada atau ketinggalan zaman
            if (! $lastSummaryUpdate || $lastSaleUpdate > $lastSummaryUpdate) {
                $this->refreshSummary($date);
                $repairsDone++;

                // NUCLEAR THROTTLE: Jangan perbaiki lebih dari 1 hari dalam 1 request untuk mencegah timeout
                if ($repairsDone >= 1) {
                    break;
                }
            }
        }

        Cache::put($cacheKey, true, 300); // 300s = 5m
    }

    /**
     * Hitung Analitik Nota (Shared between Controller and Page)
     */
    public function calculateNotaAnalytics($penjualans, $allTransaksis): array
    {
        // Aggregate data untuk Card Utama
        $totalTitip = $penjualans->sum('titip');
        $totalLaku = $penjualans->sum('laku');
        $avgLakuPercent = $totalTitip > 0 ? round(($totalLaku / $totalTitip) * 100) : 0;

        $flattenedTransaksis = $allTransaksis->flatten();
        $totalSettlement = $flattenedTransaksis->sum('jumlah');
        $totalKas = $flattenedTransaksis->sum('kas');
        $totalKemarin = $flattenedTransaksis->sum('kemarin');

        // Grouping by Product ID (Avoid nested string accessor mapping inside 1000+ items)
        $productStats = $penjualans->groupBy('produk_id')->map(function ($items) {
            $first = $items->first();

            return [
                'nama' => $first->produk->nama ?? 'Unknown',
                'sold' => $items->sum('laku'),
                'value' => $items->sum(fn ($i) => $i->laku * $i->harga_jual),
            ];
        })->values();

        // Grouping by Produsen ID
        $produsenStats = $penjualans->groupBy('produk.produsen_id')->map(function ($items) {
            $first = $items->first();

            return [
                'nama' => $first->produk->produsen->nama ?? 'Unknown',
                'sold' => $items->sum('laku'),
                'value' => $items->sum(fn ($i) => $i->laku * $i->harga_jual),
            ];
        })->values();

        return [
            'market_health' => $avgLakuPercent,
            'total_titip' => $totalTitip,
            'total_laku' => $totalLaku,
            'financial' => [
                'settlement' => $totalSettlement,
                'kas' => $totalKas,
                'kemarin' => $totalKemarin,
            ],
            'rankings' => [
                'products' => [
                    'by_sold' => $productStats->sortByDesc('sold')->take(10)->values()->toArray(),
                    'by_value' => $productStats->sortByDesc('value')->take(10)->values()->toArray(),
                ],
                'produsens' => [
                    'by_sold' => $produsenStats->sortByDesc('sold')->take(10)->values()->toArray(),
                    'by_value' => $produsenStats->sortByDesc('value')->take(10)->values()->toArray(),
                ],
            ],
        ];
    }

    /**
     * Refresh Summary untuk Tanggal tertentu
     * Menggunakan Raw SQL untuk kecepatan maksimal
     */
    public function refreshSummary(string $date): void
    {
        $start = $date.' 00:00:00';
        $end = $date.' 23:59:59';

        DB::transaction(function () use ($date, $start, $end) {
            // 0. Pre-fetch detail transaksi (lain-lain) to avoid N+1
            $detailTransaksis = DB::table('detail_transaksi')
                ->join('transaksi', 'detail_transaksi.transaksi_id', '=', 'transaksi.id')
                ->whereBetween('transaksi.tanggal', [$start, $end])
                ->select('transaksi.id as trx_id', DB::raw('SUM(detail_transaksi.jumlah) as total_lain'))
                ->groupBy('transaksi.id')
                ->pluck('total_lain', 'trx_id');

            // 0.1 Pre-fetch detail tabungan to avoid Cartesian product duplication
            $detailTabungans = DB::table('detail_tabungan')
                ->join('transaksi', 'detail_tabungan.transaksi_id', '=', 'transaksi.id')
                ->whereBetween('transaksi.tanggal', [$start, $end])
                ->whereNull('detail_tabungan.deleted_at')
                ->select('transaksi.id as trx_id', DB::raw('SUM(detail_tabungan.jumlah) as total_tabungan'))
                ->groupBy('transaksi.id')
                ->pluck('total_tabungan', 'trx_id');

            // 1. Hapus summary lama untuk tanggal tersebut
            DB::table('sales_summaries')->where('date', $date)->delete();

            // 2. Ringkasan per Pedagang (Aligned with idx_penjualan_tanggal_pedagang)
            $pedagangSummaries = DB::table('penjualan')
                ->join('pedagang', 'penjualan.pedagang_id', '=', 'pedagang.id')
                ->leftJoin('transaksi', function ($join) use ($date) {
                    $join->on('pedagang.id', '=', 'transaksi.owner_id')
                        ->where('transaksi.owner_type', '=', 'Pedagang')
                        ->whereBetween('transaksi.tanggal', [$date.' 00:00:00', $date.' 23:59:59'])
                        ->whereNull('transaksi.deleted_at');
                })
                ->whereNull('penjualan.deleted_at')
                ->where('penjualan.status', 'Ok')
                ->whereBetween('penjualan.tanggal', [$start, $end])
                ->selectRaw('? as date', [$date])
                ->selectRaw('? as type', ['pedagang'])
                ->addSelect([
                    'penjualan.pedagang_id as type_id',
                    'pedagang.nama as pedagang_nama',
                    'pedagang.tabungan_rate as pr_tabungan',
                    DB::raw('SUM(penjualan.titip) as total_titip'),
                    DB::raw('SUM(penjualan.laku) as total_laku'),
                    DB::raw('SUM(penjualan.laku * penjualan.harga_beli) as total_modal'),
                    DB::raw('SUM(penjualan.laku * penjualan.harga_jual) as total_omset'),
                    DB::raw('SUM(penjualan.sisa_jual) as total_sisa_jual'),
                    DB::raw('COUNT(DISTINCT penjualan.produk_id) as item_count'),
                    DB::raw('COUNT(DISTINCT penjualan.produk_id) as reach_count'),
                    DB::raw('CASE WHEN SUM(penjualan.titip) > 0 THEN (SUM(penjualan.laku) / SUM(penjualan.titip)) * 100 ELSE 0 END as persentase_laku'),
                    DB::raw('MAX(transaksi.id) as trx_id'),
                    DB::raw('MAX(transaksi.kas) as trx_kas'),
                    DB::raw('MAX(transaksi.kemarin) as trx_kemarin'),
                    DB::raw('MAX(transaksi.pembulatan) as trx_pembulatan'),
                    DB::raw('NOW() as created_at'),
                    DB::raw('NOW() as updated_at'),
                ])
                ->groupBy('penjualan.pedagang_id', 'pedagang.nama', 'pedagang.tabungan_rate')
                ->get()
                ->map(function ($item) {
                    // Apply Global Iuran Rule via Trait
                    $item->total_modal = $this->getAdjustedMerchantModal(
                        (float) $item->total_modal,
                        (int) $item->item_count,
                        (string) $item->pedagang_nama
                    );

                    $kasCalc = $this->getTieredMerchantKas((float) $item->total_modal);
                    $item->kas = ($item->trx_kas !== null && $item->trx_kas > 0) ? $item->trx_kas : $kasCalc;
                    $item->tabungan = $item->trx_id ? (float) ($detailTabungans[$item->trx_id] ?? $item->pr_tabungan) : $item->pr_tabungan;
                    $item->kemarin = $item->trx_kemarin !== null ? (float) $item->trx_kemarin : 0;
                    $item->pembulatan = $item->trx_pembulatan !== null ? (float) $item->trx_pembulatan : 0;
                    $item->lain_lain = $item->trx_id ? (float) ($detailTransaksis[$item->trx_id] ?? 0) : 0;

                    // Remove temporary fields before DB insert
                    unset($item->pedagang_nama, $item->pr_tabungan, $item->trx_id, $item->trx_kas, $item->trx_kemarin, $item->trx_pembulatan);

                    return $item;
                });

            // 3. Ringkasan per Produsen
            $produsenSummaries = DB::table('penjualan')
                ->whereNull('penjualan.deleted_at')
                ->join('produk', 'penjualan.produk_id', '=', 'produk.id')
                ->join('produsen', 'produk.produsen_id', '=', 'produsen.id')
                ->leftJoin('transaksi', function ($join) use ($date) {
                    $join->on('produsen.id', '=', 'transaksi.owner_id')
                        ->where('transaksi.owner_type', '=', 'Produsen')
                        ->whereBetween('transaksi.tanggal', [$date.' 00:00:00', $date.' 23:59:59'])
                        ->whereNull('transaksi.deleted_at');
                })
                ->where('penjualan.status', 'Ok')
                ->whereBetween('penjualan.tanggal', [$start, $end])
                ->selectRaw('? as date', [$date])
                ->selectRaw('? as type', ['produsen'])
                ->addSelect([
                    'produk.produsen_id as type_id',
                    'produsen.tabungan_rate as pr_tabungan',
                    DB::raw('SUM(penjualan.titip) as total_titip'),
                    DB::raw('SUM(penjualan.laku) as total_laku'),
                    DB::raw('SUM(penjualan.laku * penjualan.harga_beli) as total_modal'),
                    DB::raw('SUM(penjualan.laku * penjualan.harga_jual) as total_omset'),
                    DB::raw('SUM(penjualan.sisa_jual) as total_sisa_jual'),
                    DB::raw('COUNT(DISTINCT penjualan.produk_id) as item_count'),
                    DB::raw('COUNT(DISTINCT penjualan.pedagang_id) as reach_count'),
                    DB::raw('CASE WHEN SUM(penjualan.titip) > 0 THEN (SUM(penjualan.laku) / SUM(penjualan.titip)) * 100 ELSE 0 END as persentase_laku'),
                    DB::raw('MAX(transaksi.id) as trx_id'),
                    DB::raw('MAX(transaksi.kas) as trx_kas'),
                    DB::raw('MAX(transaksi.kemarin) as trx_kemarin'),
                    DB::raw('MAX(transaksi.pembulatan) as trx_pembulatan'),
                    DB::raw('NOW() as created_at'),
                    DB::raw('NOW() as updated_at'),
                ])
                ->groupBy('produk.produsen_id', 'produsen.tabungan_rate')
                ->get()
                ->map(function ($item) {
                    $kasFlat = (int) app(SettingsService::class)->get('kas_produsen_flat', 1500);
                    $bayar = (float) $item->total_modal;
                    $receh = fmod($bayar - $kasFlat, 1000);
                    $kasCalc = $kasFlat + ($receh < 0 ? 0 : $receh);

                    $item->kas = $item->trx_kas !== null ? $item->trx_kas : $kasCalc;
                    $item->tabungan = $item->trx_id ? (float) ($detailTabungans[$item->trx_id] ?? $item->pr_tabungan) : $item->pr_tabungan;
                    $item->kemarin = $item->trx_kemarin !== null ? (float) $item->trx_kemarin : 0;
                    $item->pembulatan = $item->trx_pembulatan !== null ? (float) $item->trx_pembulatan : 0;
                    $item->lain_lain = $item->trx_id ? (float) ($detailTransaksis[$item->trx_id] ?? 0) : 0;

                    unset($item->pr_tabungan, $item->trx_id, $item->trx_kas, $item->trx_kemarin, $item->trx_pembulatan);

                    return $item;
                });

            // 4. Ringkasan per Produk
            $produkSummaries = DB::table('penjualan')
                ->whereNull('deleted_at')
                ->where('status', 'Ok')
                ->whereBetween('tanggal', [$start, $end])
                ->selectRaw('? as date', [$date])
                ->selectRaw('? as type', ['produk'])
                ->addSelect([
                    'produk_id as type_id',
                    DB::raw('SUM(titip) as total_titip'),
                    DB::raw('SUM(laku) as total_laku'),
                    DB::raw('SUM(laku * harga_beli) as total_modal'),
                    DB::raw('SUM(laku * harga_jual) as total_omset'),
                    DB::raw('SUM(sisa_jual) as total_sisa_jual'),
                    DB::raw('COUNT(DISTINCT pedagang_id) as item_count'),
                    DB::raw('COUNT(DISTINCT pedagang_id) as reach_count'),
                    DB::raw('CASE WHEN SUM(titip) > 0 THEN (SUM(laku) / SUM(titip)) * 100 ELSE 0 END as persentase_laku'),
                    DB::raw('0 as kas'),
                    DB::raw('0 as tabungan'),
                    DB::raw('0 as kemarin'),
                    DB::raw('0 as pembulatan'),
                    DB::raw('0 as lain_lain'),
                    DB::raw('NOW() as created_at'),
                    DB::raw('NOW() as updated_at'),
                ])
                ->groupBy('produk_id')
                ->get();

            // Insert hasil ke tabel summary
            $all = $pedagangSummaries->concat($produsenSummaries)->concat($produkSummaries);

            if ($all->isNotEmpty()) {
                DB::table('sales_summaries')->insert($all->map(fn ($i) => (array) $i)->toArray());
            }
        });

        // Invalidate related caches
        Cache::forget("not_reported_{$date}");
        Cache::forget("dashboard_hub_{$date}");
    }

    /**
     * FORCE RESYNC: Memaksa hitung ulang summary untuk rentang tanggal tertentu.
     */
    public function forceResyncRange(string $startDate, string $endDate): void
    {
        $current = strtotime($startDate);
        $last = strtotime($endDate);

        while ($current <= $last) {
            $date = date('Y-m-d', $current);
            $this->refreshSummary($date);
            $current = strtotime('+1 day', $current);
        }
    }

    /**
     * Ambil data grafik performa (Omset/Laku/Titip) dari tabel summary
     */
    public function getSummaryChartData(string $type, string $startDate, string $endDate, ?int $typeId = null, string $valueField = 'total_omset'): array
    {
        $query = DB::table('sales_summaries')
            ->where('type', $type)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        $results = $query->select('date')
            ->selectRaw("SUM({$valueField}) as total")
            ->groupBy('date')
            ->pluck('total', 'date');

        $data = [];
        $current = strtotime($startDate);
        $last = strtotime($endDate);

        while ($current <= $last) {
            $date = date('Y-m-d', $current);
            $label = $this->formatDateLabel($date);
            // Gunakan key yang unik dan terurut namun tetap readable
            $data[$date] = [
                'x' => $label,
                'y' => (float) ($results[$date] ?? 0),
            ];
            $current = strtotime('+1 day', $current);
        }

        return array_values($data);
    }

    /**
     * Ambil metrik tunggal untuk tanggal tertentu
     */
    public function getDailyMetric(string $type, string $date, ?int $typeId = null, string $field = 'total_omset'): float
    {
        $query = DB::table('sales_summaries')
            ->where('type', $type)
            ->where('date', $date);

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return (float) $query->sum($field);
    }

    /**
     * Helper Formatter untuk Label Chart (Shared)
     */
    public function formatDateLabel(string $date): string
    {
        $timestamp = strtotime($date);
        $dayIntToIndo = [0 => 'M', 1 => 'S', 2 => 'S', 3 => 'R', 4 => 'K', 5 => 'J', 6 => 'S'];
        $monthIndo = ['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu', '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'];

        return date('d', $timestamp).$monthIndo[date('m', $timestamp)].'('.$dayIntToIndo[date('w', $timestamp)].')';
    }

    /**
     * Hitung Rata-rata Penjualan dalam Rentang Waktu (Misal 30 Hari)
     */
    public function calculateAverageMetrics(string $type, string|Carbon $startDate, string|Carbon $endDate, ?int $typeId = null): array
    {
        // Convert Carbon to string if needed
        $startDate = $startDate instanceof Carbon ? $startDate->toDateString() : $startDate;
        $endDate = $endDate instanceof Carbon ? $endDate->toDateString() : $endDate;
        
        $query = DB::table('sales_summaries')
            ->where('type', $type)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        $stats = $query->select(
            DB::raw('SUM(total_titip) as sum_titip'),
            DB::raw('SUM(total_laku) as sum_laku'),
            DB::raw('SUM(total_omset) as sum_omset'),
            DB::raw('SUM(total_modal) as sum_modal'),
            DB::raw('COUNT(DISTINCT date) as days')
        )->first();

        $days = (int) ($stats->days ?: 1);

        return [
            'avg_titip' => $stats->sum_titip / $days,
            'avg_laku' => $stats->sum_laku / $days,
            'avg_omset' => $stats->sum_omset / $days,
            'avg_modal' => $stats->sum_modal / $days,
            'days_count' => $days,
            'health_percent' => $stats->sum_titip > 0 ? round(($stats->sum_laku / $stats->sum_titip) * 100, 1) : 0,
        ];
    }

    /**
     * Diagnostic Health Check (Nuclear Integrity Scan)
     */
    public function checkSystemHealth(): array
    {
        $issues = [];

        // 1. Cek Integritas Saldo vs Log
        $saldoAnomalies = DB::table('saldo')
            ->select('saldo.id', 'saldo.owner_type', 'saldo.owner_id', 'saldo.jumlah as current_saldo')
            ->addSelect(DB::raw('(SELECT SUM(jumlah) FROM log_saldo WHERE saldo_id = saldo.id AND status = "Ok") as calculated_saldo'))
            ->havingRaw('current_saldo != calculated_saldo')
            ->get();

        foreach ($saldoAnomalies as $anomaly) {
            $issues[] = [
                'type' => 'Balance Integrity',
                'description' => "Owner {$anomaly->owner_type} ID {$anomaly->owner_id} has mismatching balance. Current: {$anomaly->current_saldo}, Calculated: {$anomaly->calculated_saldo}",
                'severity' => 'Critical',
                'id' => $anomaly->id,
            ];
        }

        // 2. [REMOVED] Cek Transaksi vs Details (Financial Drift) - Dihapus karena logikanya salah (detail_transaksi = lain-lain, bukan komponen penyusun total transaksi).

        // 3. Cek Anomali ProUp (7 Hari Terakhir)
        $sevenDaysAgo = now('Asia/Jakarta')->subDays(7)->toDateString();
        $recentSummaries = DB::table('sales_summaries')
            ->where('type', 'pedagang')
            ->where('date', '>=', $sevenDaysAgo)
            ->get();

        foreach ($recentSummaries as $summary) {
            $rawModal = DB::table('penjualan')
                ->where('pedagang_id', $summary->type_id)
                ->where('tanggal', $summary->date)
                ->whereNull('deleted_at')
                ->where('status', 'Ok')
                ->sum(DB::raw('laku * harga_beli'));

            if ($rawModal === 0) {
                continue;
            }

            $pedagangName = DB::table('pedagang')->where('id', $summary->type_id)->value('nama');
            $expectedProUp = $this->calculateMerchantProup((float) $rawModal, (int) $summary->item_count, (string) $pedagangName);
            $expectedTotalModal = $rawModal + $expectedProUp;

            $diff = abs($summary->total_modal - $expectedTotalModal);
            if ($diff > 1) {
                $issues[] = [
                    'type' => 'ProUp Discrepancy',
                    'description' => "Pedagang {$pedagangName} on {$summary->date} has invalid ProUp. Diff: Rp ".number_format($diff, 0, ',', '.'),
                    'severity' => 'Critical',
                    'id' => $summary->type_id,
                ];
            }
        }

        $stats = [
            'saldo_scanned' => DB::table('saldo')->count(),
            'proup_scanned' => count($recentSummaries),
            'days_scanned' => 7,
        ];

        return [
            'status' => count($issues) === 0 ? 'Healthy' : 'Anomalies Detected',
            'score' => max(0, 100 - (count($issues) * 5)),
            'issues' => $issues,
            'stats' => $stats,
            'last_scan' => now('Asia/Jakarta')->toDateTimeString(),
        ];
    }

    /**
     * NUCLEAR RECONCILIATION WATCHDOG:
     * Melakukan audit keseimbangan uang masuk vs uang keluar hari ini.
     */
    public function getNuclearReconciliation(string $date): array
    {
        $start = $date.' 00:00:00';
        $end = $date.' 23:59:59';

        $transaksis = DB::table('transaksi')
            ->whereBetween('tanggal', [$start, $end])
            ->whereNull('deleted_at')
            ->where('status', 'Ok')
            ->get();

        $inbound = (float) $transaksis->where('owner_type', 'Pedagang')->sum('jumlah');
        $outbound = (float) $transaksis->where('owner_type', 'Produsen')->sum('jumlah');
        $totalKas = (float) $transaksis->sum('kas');

        $tabunganDetail = DB::table('detail_tabungan')
            ->join('transaksi', 'detail_tabungan.transaksi_id', '=', 'transaksi.id')
            ->whereBetween('transaksi.tanggal', [$start, $end])
            ->whereNull('transaksi.deleted_at')
            ->whereNull('detail_tabungan.deleted_at')
            ->where('transaksi.status', 'Ok')
            ->select('detail_tabungan.jumlah', 'transaksi.owner_type')
            ->get();

        $merchantTab = (float) $tabunganDetail->where('owner_type', 'Pedagang')->sum('jumlah');
        $producerTab = (float) abs($tabunganDetail->where('owner_type', 'Produsen')->sum('jumlah'));
        $totalTabunganInHand = $merchantTab + $producerTab;

        // Hitung Penyesuaian dari snapshot
        $totalProUp = 0;
        $totalLainLain = 0;
        $totalAdjustments = 0; // Rounding + Carry Over
        foreach ($transaksis as $t) {
            if (! empty($t->keterangan)) {
                $snap = json_decode((string) $t->keterangan, true);
                if (is_array($snap)) {
                    if ($t->owner_type === 'Pedagang') {
                        if (isset($snap['proup'])) {
                            $totalProUp += (float) $snap['proup'];
                        } else {
                            $bruto = (float) ($snap['bruto'] ?? 0);
                            $tab = (float) ($snap['tabungan'] ?? 0);
                            $lain = (float) ($snap['lain'] ?? 0);
                            $kas = (float) ($t->kas ?? 0);
                            $proupDeriv = (float) $t->jumlah - ($bruto + $kas + $tab + $lain);
                            $totalProUp += max(0, $proupDeriv);
                        }
                    }

                    if ($t->owner_type === 'Produsen') {
                        $totalLainLain += (float) ($snap['lain'] ?? 0);
                        $totalAdjustments += (float) ($snap['rounding'] ?? 0);
                        $totalAdjustments -= (float) ($snap['carry'] ?? 0); // Carry Over besok mengurangi payout hari ini
                    }
                }
            }
        }

        // Rumus Audit Inti:
        // (Inbound - Outbound) - (Kas + Tab) = Hasil + Lain - Adjustments
        $keseimbanganInti = ($inbound - $outbound) - ($totalKas + $totalTabunganInHand);

        // Kalkulasi Selisih Akhir
        $discrepancy = $keseimbanganInti - $totalProUp - $totalLainLain + $totalAdjustments;

        // Fallback untuk transaksi tanpa snapshot lengkap (v < 3.1)
        // Jika snapshot rounding kosong tapi ada selisih, coba cari di tabel transaksi langsung
        if ($totalAdjustments === 0 && abs($discrepancy) > 1000) {
            $dbRounding = (float) $transaksis->where('owner_type', 'Produsen')->sum('pembulatan');
            if ($dbRounding !== 0) {
                $discrepancy += $dbRounding;
            }
        }

        return [
            'inbound' => $inbound,
            'outbound' => $outbound,
            'kas' => $totalKas,
            'tabungan' => $totalTabunganInHand,
            'discrepancy' => $discrepancy,
            'status' => abs($discrepancy) < 1000 ? 'Balanced' : 'Mismatch', // Toleransi diperketat menjadi 1k
        ];
    }

    /**
     * UNIFIED HUB DATA: Mengambil data untuk Dashboard Utama
     * [ACCELERATION]: Menggunakan Smart Caching & Raw Optimized Joins
     */
    public function getDashboardHubData(string $date): array
    {
        $cacheKey = "dashboard_hub_{$date}";
        $ttl = $date === now()->toDateString() ? 300 : 7200; // 5 min vs 2 hours

        return Cache::flexible($cacheKey, [$ttl, $ttl + 300], function () use ($date) {
            $start = $date.' 00:00:00';
            $end = $date.' 23:59:59';

            // 0. Pre-fetch detail tabungan to avoid Cartesian product duplication
            $tabungans = DB::table('detail_tabungan')
                ->join('transaksi', 'detail_tabungan.transaksi_id', '=', 'transaksi.id')
                ->whereBetween('transaksi.tanggal', [$start, $end])
                ->whereNull('detail_tabungan.deleted_at')
                ->select('transaksi.id as trx_id', DB::raw('SUM(detail_tabungan.jumlah) as total_tabungan'))
                ->groupBy('transaksi.id')
                ->pluck('total_tabungan', 'trx_id');

            // 1. Pedagang Table (Nuclear Join)
            $pedagang = DB::table('pedagang as p')
                ->join('penjualan as pn', 'p.id', '=', 'pn.pedagang_id')
                ->leftJoin('saldo as s', function ($join) {
                    $join->on('p.id', '=', 's.owner_id')
                        ->where('s.owner_type', '=', 'Pedagang');
                })
                ->leftJoin('transaksi as t', function ($join) use ($date) {
                    $join->on('p.id', '=', 't.owner_id')
                        ->where('t.owner_type', '=', 'Pedagang')
                        ->whereBetween('t.tanggal', [$date.' 00:00:00', $date.' 23:59:59'])
                        ->whereNull('t.deleted_at');
                })
                ->select([
                    'p.id', 'p.nama', 'p.tabungan_rate as pr_tabungan',
                    DB::raw('t.id as trx_id'),
                    DB::raw('COUNT(DISTINCT pn.produk_id) as produk_count'),
                    DB::raw('SUM(pn.titip) as titip'),
                    DB::raw('SUM(pn.laku) as laku'),
                    DB::raw('SUM(pn.laku * pn.harga_beli) as setoran_modal'),
                    DB::raw('SUM(pn.laku * pn.harga_jual) as total_omset'),
                    DB::raw('MAX(pn.created_at) as sent_at'),
                    DB::raw("CASE 
                        WHEN t.status IS NOT NULL THEN t.status 
                        WHEN MAX(pn.status) = 'Draft' AND MAX(pn.keterangan) = 'Locked' THEN 'LOCKED'
                        ELSE MAX(pn.status) 
                    END as status"),
                    DB::raw('MAX(t.kas) as kas'),
                    DB::raw('MAX(t.jumlah) as setoran_net_fix'),
                    DB::raw('IFNULL(MAX(s.jumlah), 0) as current_saldo'),
                ])
                ->whereBetween('pn.tanggal', [$start, $end])
                ->whereNull('pn.deleted_at')
                ->groupBy('p.id', 'p.nama', 'p.tabungan_rate', 't.status', 't.id', 't.jumlah')
                ->get()
                ->map(function ($item) use ($tabungans) {
                    $item->tabungan_fix = $item->trx_id ? ($tabungans[$item->trx_id] ?? 0) : 0;

                    return $item;
                });

            // 2. Produsen Table (Nuclear Join)
            $produsen = DB::table('produsen as pr')
                ->join('produk as pd', 'pr.id', '=', 'pd.produsen_id')
                ->join('penjualan as pn', 'pd.id', '=', 'pn.produk_id')
                ->leftJoin('transaksi as t', function ($join) use ($date) {
                    $join->on('pr.id', '=', 't.owner_id')
                        ->where('t.owner_type', '=', 'Produsen')
                        ->whereBetween('t.tanggal', [$date.' 00:00:00', $date.' 23:59:59'])
                        ->whereNull('t.deleted_at');
                })
                ->select([
                    'pr.id', 'pr.nama', 'pr.tabungan_rate as pr_tabungan',
                    DB::raw('t.id as trx_id'),
                    DB::raw('GROUP_CONCAT(DISTINCT pd.nama SEPARATOR ", ") as produk_names'),
                    DB::raw('SUM(pn.laku) as laku'),
                    DB::raw('SUM(pn.laku * pn.harga_jual) as omset'),
                    DB::raw('SUM(pn.laku * pn.harga_beli) as hb_total'),
                    DB::raw("CASE 
                        WHEN t.status IS NOT NULL THEN t.status 
                        WHEN MAX(pn.status) = 'Draft' AND MAX(pn.keterangan) = 'Locked' THEN 'LOCKED'
                        ELSE MAX(pn.status) 
                    END as status"),
                    DB::raw('MAX(t.kas) as kas'),
                    DB::raw('MAX(t.jumlah) as bayar_net'),
                ])
                ->whereBetween('pn.tanggal', [$start, $end])
                ->whereNull('pn.deleted_at')
                ->groupBy('pr.id', 'pr.nama', 'pr.tabungan_rate', 't.status', 't.id', 't.jumlah')
                ->get()
                ->map(function ($item) use ($tabungans) {
                    $item->tabungan = $item->trx_id ? ($tabungans[$item->trx_id] ?? 0) : 0;

                    return $item;
                });

            // 3. Operational Intel (Belum Kirim)
            $operational = $this->getOperationalIntel($date);

            // 4. Lain-lain Hari Ini
            $lainLain = DB::table('detail_transaksi as dt')
                ->join('transaksi as t', 'dt.transaksi_id', '=', 't.id')
                ->join('produsen as p', 't.owner_id', '=', 'p.id')
                ->where('t.owner_type', 'Produsen')
                ->whereBetween('t.tanggal', [$date.' 00:00:00', $date.' 23:59:59'])
                ->whereNull('dt.deleted_at')
                ->select('dt.id', 'dt.keterangan', 'dt.jumlah', 'p.nama as owner_name', 't.status as trx_status')
                ->orderBy('dt.created_at', 'desc')
                ->get();

            return [
                'pedagang' => $pedagang,
                'produsen' => $produsen,
                'belum_kirim' => $operational['belum_kirim'],
                'lain_lain' => $lainLain,
                'is_public' => in_array($date, app(SettingsService::class)->get('public_nota_dates', []), true),
            ];
        });
    }

    /**
     * UNIFIED OPERATIONAL INTEL: Deteksi Belum Kirim menggunakan Unified Active Logic
     */
    public function getOperationalIntel(string $date): array
    {
        $activeIds = Pedagang::getActivePedagangIds(14); // Unified 14 days

        $alreadySentToday = DB::table('penjualan')
            ->whereBetween('tanggal', [$date.' 00:00:00', $date.' 23:59:59'])
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('pedagang_id')
            ->toArray();

        $belumKirim = DB::table('pedagang')
            ->whereIn('id', $activeIds)
            ->whereNotIn('id', $alreadySentToday)
            ->whereNull('deleted_at')
            ->select('id', 'nama')
            ->orderBy('nama')
            ->get();

        return [
            'belum_kirim' => $belumKirim,
        ];
    }

    /**
     * UNIFIED MERCHANT DASHBOARD: Merangkum data untuk login pedagang
     */
    public function getMerchantDashboardData(int $pedagangId, string $date): array
    {
        $start = $date.' 00:00:00';
        $end = $date.' 23:59:59';

        // 1. Ambil Summary harian (Metrik Utama)
        $summary = DB::table('sales_summaries')
            ->where('type', 'pedagang')
            ->where('type_id', $pedagangId)
            ->where('date', $date)
            ->first();

        // 2. Ambil Detail Produk hari ini (Manifest)
        $items = DB::table('penjualan as p')
            ->join('produk as pdk', 'p.produk_id', '=', 'pdk.id')
            ->where('p.pedagang_id', $pedagangId)
            ->whereBetween('p.tanggal', [$start, $end])
            ->whereNull('p.deleted_at')
            ->select([
                'pdk.nama',
                'p.titip',
                'p.laku',
                'p.sisa_jual',
                'p.harga_beli',
                'p.harga_jual',
                'p.status',
                'p.keterangan as lock_status',
            ])
            ->get()
            ->map(function ($i) {
                $i->retur = (int) $i->titip - (int) $i->laku - (int) $i->sisa_jual;
                $i->subtotal_modal = (float) $i->laku * (float) $i->harga_beli;
                $i->subtotal_omset = (float) $i->laku * (float) $i->harga_jual;

                return $i;
            });

        // 3. Ambil Tren 7 Hari
        $trend = $this->getSummaryChartData(
            'pedagang',
            date('Y-m-d', strtotime('-7 days', strtotime($date))),
            $date,
            $pedagangId,
            'total_omset'
        );

        // 4. Deteksi Status Laporan
        $firstItem = $items->first();
        $status = $firstItem->status ?? 'Operational Void';
        $isLocked = ($firstItem->lock_status ?? '') === 'Locked';

        return [
            'summary' => $summary,
            'items' => $items,
            'trend' => $trend,
            'status' => $status,
            'is_locked' => $isLocked,
        ];
    }

    /**
     * NUCLEAR LIVE INTEL: Ambil sisa stok meja real-time untuk Produsen
     */
    public function getLiveStockData(int $produsenId, string $date)
    {
        return DB::table('penjualan')
            ->join('produk', 'penjualan.produk_id', '=', 'produk.id')
            ->join('pedagang', 'penjualan.pedagang_id', '=', 'pedagang.id')
            ->where('produk.produsen_id', $produsenId)
            ->where('penjualan.tanggal', $date)
            ->whereNull('penjualan.deleted_at')
            ->select(
                'pedagang.nama as pedagang_nama',
                'produk.nama as produk_nama',
                'penjualan.titip',
                'penjualan.sisa_jual',
                'penjualan.updated_at'
            )
            ->orderBy('pedagang.nama')
            ->get();
    }
}
