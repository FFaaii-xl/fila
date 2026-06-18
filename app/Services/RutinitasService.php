<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rutinitas;
use App\Models\Transaksi;
use Illuminate\Support\Collection;

class RutinitasService
{
    /**
     * Terapkan rutinitas pada koleksi transaksi
     */
    public function applyToTransactions(Collection $transaksis)
    {
        foreach ($transaksis as $transaksi) {
            $this->applyToTransaction($transaksi);
        }
    }

    /**
     * Terapkan rutinitas pada satu transaksi
     */
    public function applyToTransaction(Transaksi $transaksi)
    {
        if (! $transaksi->owner || ! $transaksi->owner->rutinitas) {
            return;
        }

        foreach ($transaksi->owner->rutinitas as $routine) {
            // Logika 'tambah' dari legacy: menambahkan detail transaksi
            // Kita asumsikan Transaksi model punya method addDetail atau kita buat di sini
            $this->addRoutineToTransaction($transaksi, $routine);
        }
    }

    /**
     * Menambahkan baris detail transaksi berdasarkan rutinitas
     */
    protected function addRoutineToTransaction(Transaksi $transaksi, Rutinitas $routine)
    {
        // Menambah jumlah total transaksi
        $transaksi->jumlah += $routine->jumlah;
        $transaksi->save();

        // Menambah detail transaksi (biaya tambahan/potongan)
        $transaksi->details()->create([
            'jumlah' => $routine->jumlah,
            'keterangan' => $routine->keterangan ?? 'Rutinitas: '.($routine->kode ?? 'N/A'),
        ]);
    }
}
