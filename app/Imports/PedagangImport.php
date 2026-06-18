<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Pedagang;
use App\Traits\CitroNumeric;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PedagangImport implements ToCollection, WithHeadingRow
{
    use CitroNumeric;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $nama = trim(strval($row['nama'] ?? ''));
            if (empty($nama)) {
                continue;
            }

            $data = [
                'nama' => $nama,
                'gender' => $row['gender'] ?? 'unknown',
                'tabungan' => $this->cleanFloat($row['tabungan'] ?? 0),
            ];

            $pedagang = Pedagang::where('nama', $nama)->first();

            if ($pedagang) {
                // Update jika ada perbedaan
                $pedagang->fill($data);
                if ($pedagang->isDirty()) {
                    $pedagang->save();
                }
            } else {
                Pedagang::create($data);
            }
        }
    }
}
