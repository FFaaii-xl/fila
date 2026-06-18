<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaldoService
{
    /**
     * Mengambil data perbandingan Tagihan vs Saldo Pedagang (Nalangi Tracker).
     *
     * @return Collection
     */
    public function getMerchantBailoutLogs(?string $startDate = null, ?string $endDate = null)
    {
        $tanggal = $startDate ?: now()->toDateString();

        $query = DB::table('pedagang as p')
            ->leftJoin('saldo as s', function ($join) {
                $join->on('p.id', '=', 's.owner_id')
                    ->where('s.owner_type', '=', 'Pedagang');
            })
            ->leftJoin('transaksi as t', function ($join) use ($tanggal) {
                $join->on('p.id', '=', 't.owner_id')
                    ->where('t.owner_type', '=', 'Pedagang')
                    ->where('t.status', '=', 'Ok')
                    ->whereNull('t.deleted_at')
                    ->whereBetween('t.tanggal', ["$tanggal 00:00:00", "$tanggal 23:59:59"]);
            })
            ->select([
                DB::raw('COALESCE(t.id, 0) as id'),
                'p.nama as pedagang_nama',
                DB::raw('IFNULL(t.jumlah, 0) as tagihan_sistem'),
                DB::raw('IFNULL(s.jumlah, 0) as saldo_tersedia'),
                DB::raw('(IFNULL(s.jumlah, 0) - IFNULL(t.jumlah, 0)) as selisih'),
            ])
            ->selectRaw('COALESCE(t.tanggal, ?) as tanggal', [$tanggal])
            ->whereNull('p.deleted_at')
            ->where(function ($q) {
                $q->where('s.jumlah', '>', 0)
                    ->orWhere('t.jumlah', '>', 0);
            });

        return $query->orderBy('p.nama', 'asc')
            ->get();
    }
}
