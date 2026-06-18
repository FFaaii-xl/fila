<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Produk;
use App\Models\Produsen;
use App\Traits\CitroNumeric;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProdukImport implements ToCollection, WithHeadingRow
{
    use CitroNumeric;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $namaProduk = trim(strval($row['nama_produk'] ?? ''));
            if (empty($namaProduk)) {
                continue;
            }

            // Temukan Produsen berdasarkan nama
            $namaProdusen = trim(strval($row['nama_produsen'] ?? ''));
            $produsen = Produsen::where('nama', $namaProdusen)->first();
            $produsenId = $produsen ? $produsen->id : null;

            $data = [
                'nama' => $namaProduk,
                'harga_beli' => $this->cleanFloat($row['harga_beli'] ?? 0),
                'harga_jual' => $this->cleanFloat($row['harga_jual'] ?? 0),
            ];

            if ($produsenId) {
                $data['produsen_id'] = $produsenId;
            }

            $produk = Produk::where('nama', $namaProduk)->first();

            if ($produk) {
                // Update jika ada perubahan (eloquent otomatis skip jika clean/sama persis)
                $produk->fill($data);
                if ($produk->isDirty()) {
                    $produk->save();
                }
            } else {
                // Produsen ID wajib jika data baru
                if ($produsenId) {
                    Produk::create($data);
                }
            }
        }
    }
}
