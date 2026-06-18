<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SetoranService
{
    /**
     * Build the monthly grid: pedagang rows × day columns
     * Returns [pedagangId => [day => ['keterangan' => 'Ok', 'jumlah' => 245000]], ...]
     */
    public function getMonthlyGrid(int $year, int $month, ?int $pedagangId = null): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        // 1. Get all pedagang summary data this month
        $summaryData = DB::table('sales_summaries as s')
            ->where('s.type', 'pedagang')
            ->when($pedagangId, fn ($q) => $q->where('s.type_id', $pedagangId))
            ->whereBetween('s.date', [$startDate, $endDate])
            ->select([
                's.type_id as pedagang_id',
                's.date as tgl',
                's.total_modal',
                's.kas',
                's.tabungan',
                's.kemarin',
                's.pembulatan',
                's.lain_lain',
            ])
            ->get();

        // 2. Get all setoran records this month
        $setoranData = DB::table('detail_setoran as ds')
            ->when($pedagangId, fn ($q) => $q->where('ds.pedagang_id', $pedagangId))
            ->whereBetween(DB::raw('DATE(ds.tanggal)'), [$startDate, $endDate])
            ->whereNull('ds.deleted_at')
            ->select([
                'ds.pedagang_id',
                DB::raw('DATE(ds.tanggal) as tgl'),
                'ds.keterangan',
            ])
            ->get();

        // 3. Build hash maps for O(1) lookup
        $transaksiMap = [];
        foreach ($summaryData as $row) {
            $day = (int) Carbon::parse($row->tgl)->format('j');

            // Total Setoran: Modal (inc Iuran) + Kas + Tabungan + Kemarin + Pembulatan + Lain-lain
            $total = (float) $row->total_modal +
                     (float) $row->kas +
                     (float) $row->tabungan +
                     (float) $row->kemarin +
                     (float) $row->pembulatan +
                     (float) $row->lain_lain;

            // Pedagang pay exact totals (no additional rounding here)
            $transaksiMap[$row->pedagang_id][$day] = (int) round($total);
        }

        $setoranMap = [];
        foreach ($setoranData as $row) {
            $day = (int) Carbon::parse($row->tgl)->format('j');
            $setoranMap[$row->pedagang_id][$day] = $row->keterangan;
        }

        // 4. Get all active pedagang
        $pedagangList = DB::table('pedagang')
            ->whereNull('deleted_at')
            ->when($pedagangId, fn ($q) => $q->where('id', $pedagangId))
            ->orderBy('nama')
            ->select(['id', 'nama'])
            ->get();

        // 5. Build grid
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $grid = [];

        foreach ($pedagangList as $p) {
            $row = [
                'id' => $p->id,
                'nama' => $p->nama,
                'days' => [],
            ];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $jumlah = $transaksiMap[$p->id][$d] ?? null;
                $keterangan = $setoranMap[$p->id][$d] ?? null;

                $row['days'][$d] = [
                    'jumlah' => $jumlah,
                    'keterangan' => $keterangan,
                    'has_transaksi' => $jumlah !== null,
                ];
            }

            // Only include pedagang who have at least 1 transaction this month
            $hasAny = collect($row['days'])->contains('has_transaksi', true);
            if ($hasAny) {
                $grid[] = $row;
            }
        }

        return $grid;
    }

    /**
     * Toggle setoran status for a pedagang on a specific date.
     * If status is 'auto', it determines status based on balance and timing.
     * If record exists, it deletes it (true toggle).
     */
    public function toggleSetoran(int $pedagangId, string $tanggal, int $confirmedBy, string $status): array
    {
        $dateObj = Carbon::parse($tanggal);
        $dateStr = $dateObj->toDateString();

        // Check if record exists
        $existing = DB::table('detail_setoran')
            ->where('pedagang_id', $pedagangId)
            ->where(DB::raw('DATE(tanggal)'), $dateStr)
            ->whereNull('deleted_at')
            ->first();

        // If exists and we are doing 'reset', we delete/reset
        if ($existing && $status === 'reset') {
            DB::table('detail_setoran')
                ->where('id', $existing->id)
                ->update(['deleted_at' => now('Asia/Jakarta'), 'updated_at' => now('Asia/Jakarta')]);

            return ['action' => 'deleted', 'keterangan' => null];
        }

        // --- CALC STATUS ---
        if ($status === 'auto' || $status === 'late' || $status === 'not_late') {
            $status = $this->calculateAutoStatus(
                $pedagangId,
                $tanggal,
                $status === 'late',
                $status === 'not_late'
            );
        }

        if ($existing) {
            DB::table('detail_setoran')
                ->where('id', $existing->id)
                ->update([
                    'keterangan' => $status,
                    'updated_at' => now('Asia/Jakarta'),
                ]);

            return ['action' => 'updated', 'keterangan' => $status];
        }

        // --- INSERT NEW RECORD ---
        // Find matching transaksi_id
        $transaksi = DB::table('transaksi')
            ->where('owner_type', 'Pedagang')
            ->where('owner_id', $pedagangId)
            ->where('status', 'Ok')
            ->where(DB::raw('DATE(tanggal)'), $dateStr)
            ->first();

        DB::table('detail_setoran')->insert([
            'transaksi_id' => $transaksi->id ?? null,
            'pedagang_id' => $pedagangId,
            'keterangan' => $status,
            'tanggal' => $dateStr.' '.now('Asia/Jakarta')->format('H:i:s'),
            'created_at' => now('Asia/Jakarta'),
            'updated_at' => now('Asia/Jakarta'),
        ]);

        return ['action' => 'created', 'keterangan' => $status];
    }

    /**
     * Determine status automatically based on heritage rules.
     *
     * @param  bool  $forceLate  If true, ensures result is at least T1
     * @param  bool  $forceNotLate  If true, ensures result is ONLY Ok or S
     */
    public function calculateAutoStatus(int $pedagangId, string $tanggalPenjualan, bool $forceLate = false, bool $forceNotLate = false): string
    {
        $saleDate = Carbon::parse($tanggalPenjualan)->startOfDay();
        $now = now('Asia/Jakarta');
        $today = $now->copy()->startOfDay();

        $daysDiff = (int) $saleDate->diffInDays($today);

        // Check if has balance
        $hasBalance = DB::table('saldo')
            ->where('owner_type', 'Pedagang')
            ->where('owner_id', $pedagangId)
            ->where('jumlah', '>', 0)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasBalance) {
            // Balance: T+1 morning -> Ok.
            if ($forceNotLate || ($daysDiff <= 1 && ! $forceLate)) {
                return 'Ok';
            }

            $lateDegree = $forceLate ? max(1, $daysDiff) : ($daysDiff - 1);

            return 'T'.max(1, $lateDegree);
        } else {
            // No Balance: T+0 evening -> S.
            if ($forceNotLate || ($daysDiff === 0 && ! $forceLate)) {
                return 'S';
            }

            $lateDegree = $forceLate ? max(1, $daysDiff + 1) : $daysDiff;

            return 'T'.max(1, $lateDegree);
        }
    }

    /**
     * Get summary statistics for the month
     */
    public function getMonthlySummary(int $year, int $month, ?int $pedagangId = null): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay()->toDateString();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        // Total transactions this month
        $totalTransaksi = DB::table('transaksi')
            ->where('owner_type', 'Pedagang')
            ->where('status', 'Ok')
            ->when($pedagangId, fn ($q) => $q->where('owner_id', $pedagangId))
            ->whereBetween(DB::raw('DATE(tanggal)'), [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->count();

        // Setoran records
        $setoranStats = DB::table('detail_setoran')
            ->when($pedagangId, fn ($q) => $q->where('pedagang_id', $pedagangId))
            ->whereBetween(DB::raw('DATE(tanggal)'), [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN keterangan = 'Ok' THEN 1 ELSE 0 END) as ok_count,
                SUM(CASE WHEN keterangan = 'S' THEN 1 ELSE 0 END) as s_count,
                SUM(CASE WHEN keterangan LIKE 'T%' THEN 1 ELSE 0 END) as t_count
            ")
            ->first();

        return [
            'total_transaksi' => $totalTransaksi,
            'total_setor' => (int) ($setoranStats->total ?? 0),
            'ok' => (int) ($setoranStats->ok_count ?? 0),
            's' => (int) ($setoranStats->s_count ?? 0),
            'terlambat' => (int) ($setoranStats->t_count ?? 0),
            'belum' => $totalTransaksi - (int) ($setoranStats->total ?? 0),
        ];
    }
}
