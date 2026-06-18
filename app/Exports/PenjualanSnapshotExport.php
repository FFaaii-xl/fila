<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PenjualanSnapshotExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping, WithStyles
{
    protected $collection;

    private int $rowCount = 0;

    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return ['#', 'ID', 'Produk', 'T', 'SR', 'SJ', 'L', 'HJ', 'Keterangan'];
    }

    public function map($item): array
    {
        $this->rowCount++;
        $titip = (int) $item['titip'];
        $sr = (int) $item['sr'];
        $sj = (int) $item['sj'];
        $laku = $titip - $sr - $sj;

        return [
            $this->rowCount,
            $item['id'],
            strtoupper($item['nama']),
            $titip,
            $sr,
            $sj,
            $laku,
            (float) $item['harga_jual'],
            strtoupper($item['produsen']),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 4,
            'B' => 10,
            'C' => 25,
            'D' => 7,
            'E' => 7,
            'F' => 7,
            'G' => 7,
            'H' => 10,
            'I' => 25,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2D3748'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
        ];
    }
}
