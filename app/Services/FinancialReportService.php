<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\FinancialPeriodDetection;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    use FinancialPeriodDetection;

    /**
     * Get combined Tabungan data for Pedagang and Produsen, based on period to current filter.
     */
    public function getTabunganExportData(int $year, int $month): array
    {
        // 1. Tentukan Awal Periode berjalan menggunakan Trait Pusat
        $start = $this->getCurrentPeriodStartDate();

        // 2. Set endDate berdasarkan filter (akhir dari bulan dan tahun itu)
        $endDate = Carbon::create($year, $month)->endOfMonth()->format('Y-m-d 23:59:59');

        // Batasi start agar tidak melebihi end date jika terjadi anomali
        if (Carbon::parse($start)->gt(Carbon::parse($endDate))) {
            $start = Carbon::create($year, $month)->startOfMonth()->format('Y-m-d');
        }

        // 3. Bangun Header Kolom Dinamis (M bulan antara start & endDate)
        $period = CarbonPeriod::create(Carbon::parse($start)->startOfMonth(), '1 month', Carbon::parse($endDate)->startOfMonth());
        $months = [];
        foreach ($period as $date) {
            $months[$date->format('Y-m')] = strtoupper($date->format('My'));
        }

        // 4. Query Detail Tabungan gabungan (Aligned with idx_filter1: deleted_at, keterangan, tanggal)
        $tabunganData = DB::table('detail_tabungan')
            ->whereNull('deleted_at')
            ->where('keterangan', 'NOT LIKE', 'finalize%') // Sesuaikan dengan deteksi periode di atas
            ->whereBetween('tanggal', [$start.' 00:00:00', $endDate])
            ->select([
                'owner_id',
                'owner_type',
                DB::raw("DATE_FORMAT(tanggal, '%Y-%m') as k"),
                DB::raw('SUM(jumlah) as total'),
            ])
            ->groupBy('owner_id', 'owner_type', 'k')
            ->get();

        // Mapping Data Total Per user & bulan
        $grid = [];
        foreach ($tabunganData as $row) {
            $grid[$row->owner_type][$row->owner_id][$row->k] = $row->total;
        }

        // 5. Query Master Users
        $pedagang = DB::table('pedagang')->whereNull('deleted_at')->orderBy('nama')->get();
        $produsen = DB::table('produsen')->whereNull('deleted_at')->orderBy('nama')->get();

        $rows = [];

        // Gabungkan Produsen dan Pedagang
        foreach ($pedagang as $p) {
            $rows[] = $this->buildRowData($p->id, $p->nama, 'Pedagang', $grid, $months);
        }
        foreach ($produsen as $p) {
            $rows[] = $this->buildRowData($p->id, $p->nama, 'Produsen', $grid, $months);
        }

        // Urutkan berdasarkan Type (Pedagang/Produsen) lalu Abjad Nama
        usort($rows, function ($a, $b) {
            $cmpType = strcmp(strtolower($a['type']), strtolower($b['type']));
            if ($cmpType === 0) {
                return strcmp(strtolower($a['nama']), strtolower($b['nama']));
            }

            return $cmpType;
        });

        // Tambahkan nomor urut
        foreach ($rows as $idx => &$r) {
            $r['no'] = $idx + 1;
        }

        return [
            'months' => $months, // e.g. ['2026-02' => 'FEB 2026', '2026-03' => 'MAR 2026']
            'rows' => $rows,
        ];
    }

    private function buildRowData($id, $nama, $type, $grid, $months)
    {
        $userData = [
            'nama' => $nama,
            'type' => $type,
            'total' => 0,
        ];

        foreach ($months as $k => $label) {
            $val = $grid[$type][$id][$k] ?? 0;
            $userData['months'][$k] = $val;
            $userData['total'] += $val;
        }

        return $userData;
    }

    /**
     * Get consolidated monthly daily recap for financial report.
     */
    public function getMonthlyDailyRecap(int $year, int $month): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        // 1. Fetch Global Sales from 'sales_summaries' (Massive speed gain: O(N_days) instead of O(N_transactions))
        $sales = DB::table('sales_summaries')
            ->where('type', 'pedagang')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select(DB::raw('date, SUM(total_omset) as revenue'))
            ->groupBy('date')
            ->pluck('revenue', 'date');

        if ($sales->isEmpty()) {
            // Fallback (hanya jika summary belum digenerate)
            $sales = DB::table('penjualan')
                ->whereNull('deleted_at')
                ->where('status', 'Ok')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->selectRaw('DATE(tanggal) as date, SUM(laku * harga_jual) as revenue')
                ->groupBy('date')
                ->pluck('revenue', 'date');
        }

        // 2. Fetch Kas from 'transaksi' table (Index-Aware WHERE Order)
        $transactions = DB::table('transaksi')
            ->whereNull('deleted_at')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw('DATE(tanggal) as date, owner_type, SUM(kas) as total_kas')
            ->groupBy('date', 'owner_type')
            ->get();

        // 3. Fetch Tabungan from 'detail_tabungan' table (Index-Aware WHERE Order)
        $savings = DB::table('detail_tabungan')
            ->whereNull('deleted_at')
            ->where('keterangan', 'NOT LIKE', '%finalize%')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw('DATE(tanggal) as date, owner_type, SUM(jumlah) as total_saving')
            ->groupBy('date', 'owner_type')
            ->get();

        // 4. Fetch Expenses from 'detail_kas' table
        $expenses = DB::table('detail_kas')
            ->whereNull('deleted_at')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select(['tanggal', 'keterangan', 'jumlah'])
            ->get();

        // Prepare Grid Structure
        $days = CarbonPeriod::create($startDate, $endDate);
        $recap = [
            'produsen' => [],
            'pedagang' => [],
            'summary' => [
                'kas_pedagang' => 0,
                'tab_pedagang' => 0,
                'kas_produsen' => 0,
                'tab_produsen' => 0,
                'total_pemasukan' => 0,
                'total_pengeluaran' => 0,
                'total_setor' => 0,
                'expenses' => $expenses->toArray(),
            ],
        ];

        // Format data for easier access
        $txMap = [];
        foreach ($transactions as $tx) {
            $type = strtolower($tx->owner_type);
            $txMap[$tx->date][$type] = $tx->total_kas;
        }

        $svMap = [];
        foreach ($savings as $sv) {
            $type = strtolower($sv->owner_type);
            $svMap[$sv->date][$type] = $sv->total_saving;
        }

        foreach ($days as $day) {
            $dateStr = $day->format('Y-m-d');

            // Produsen Entry
            $pKas = (float) ($txMap[$dateStr]['produsen'] ?? 0);
            $pTab = (float) ($svMap[$dateStr]['produsen'] ?? 0);
            $pSales = (float) ($sales[$dateStr] ?? 0);

            $recap['produsen'][$dateStr] = [
                'date' => $day->format('d-M-y'),
                'type' => 'Produsen',
                'sales' => $pSales,
                'kas' => $pKas,
                'tabungan' => $pTab,
            ];

            // Pedagang Entry
            $mKas = (float) ($txMap[$dateStr]['pedagang'] ?? 0);
            $mTab = (float) ($svMap[$dateStr]['pedagang'] ?? 0);
            $mSales = (float) ($sales[$dateStr] ?? 0);

            $recap['pedagang'][$dateStr] = [
                'date' => $day->format('d-M-y'),
                'type' => 'Pedagang',
                'sales' => $mSales,
                'kas' => $mKas,
                'tabungan' => $mTab,
            ];

            // Aggregating Summary
            $recap['summary']['kas_produsen'] += $pKas;
            $recap['summary']['tab_produsen'] += $pTab;
            $recap['summary']['kas_pedagang'] += $mKas;
            $recap['summary']['tab_pedagang'] += $mTab;
        }

        $recap['summary']['total_pemasukan'] =
            $recap['summary']['kas_pedagang'] +
            $recap['summary']['tab_pedagang'] +
            $recap['summary']['kas_produsen'] +
            $recap['summary']['tab_produsen'];

        $recap['summary']['total_pengeluaran'] = $expenses->sum('jumlah');
        $recap['summary']['total_setor'] = $recap['summary']['total_pemasukan'] - $recap['summary']['total_pengeluaran'];

        return $recap;
    }
}
