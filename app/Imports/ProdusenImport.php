<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Produsen;
use App\Traits\CitroNumeric;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProdusenImport implements ToCollection, WithHeadingRow
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
                'bundle_ke' => isset($row['bundle_ke']) ? $this->cleanNumeric($row['bundle_ke']) : null,
            ];

            $produsen = Produsen::where('nama', $nama)->first();

            if ($produsen) {
                // Update jika ada perubahan
                $produsen->fill($data);
                if ($produsen->isDirty()) {
                    $produsen->save();
                }
            } else {
                Produsen::create($data);
            }
        }
    }
}
