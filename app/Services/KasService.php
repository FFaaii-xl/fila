<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DetailKas;
use App\Models\Kas;
use Illuminate\Support\Facades\DB;

class KasService
{
    /**
     * Menambahkan jumlah kas secara manual
     */
    public function increaseKas(array $data)
    {
        return DB::transaction(function () use ($data) {
            $kas = Kas::latest()->first() ?: Kas::create(['tanggal' => now(), 'jumlah' => 0]);

            $detail = DetailKas::create([
                'kas_id' => $kas->id,
                'jumlah' => $data['jumlah'],
                'keterangan' => $data['keterangan'] ?? 'Manual Increase',
                'tanggal' => $data['tanggal'] ?? now(),
            ]);

            // Update total kas (opsional, tergantung struktur tabel kas di legacy)
            // Di legacy sepertinya kas.jumlah adalah total saldo kas saat ini
            $kas->increment('jumlah', $data['jumlah']);

            return $detail;
        });
    }

    /**
     * Mengurangi jumlah kas secara manual
     */
    public function decreaseKas(array $data)
    {
        return DB::transaction(function () use ($data) {
            $kas = Kas::latest()->first() ?: Kas::create(['tanggal' => now(), 'jumlah' => 0]);

            $detail = DetailKas::create([
                'kas_id' => $kas->id,
                'jumlah' => -$data['jumlah'],
                'keterangan' => $data['keterangan'] ?? 'Manual Decrease',
                'tanggal' => $data['tanggal'] ?? now(),
            ]);

            $kas->decrement('jumlah', $data['jumlah']);

            return $detail;
        });
    }

    /**
     * Mendapatkan log kas harian
     */
    public function getHarianLog($date = null)
    {
        $date = $date ?: now()->toDateString();

        return DetailKas::whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
            ->with('kas')
            ->latest()
            ->get();
    }
}
