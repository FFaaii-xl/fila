<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Produk;
use App\Traits\CitroNumeric;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PenjualanTemplateExport implements FromCollection, WithColumnFormatting, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use CitroNumeric;

    private int $rowCount = 0;

    private array $latestEntry;

    private array $addedNames = [];

    private array $editedNames = [];

    private array $highlightRows = [];

    private int $finalRow = 0;

    private $dataCollection = null;

    public function __construct(?array $latestEntry = null, ?Collection $data = null)
    {
        $this->latestEntry = $latestEntry ?? [];

        // Extract names for highlighting
        $this->addedNames = $this->latestEntry['added'] ?? [];
        $this->editedNames = $this->latestEntry['edits'] ?? [];

        // Pre-fetch collection to determine dimensions (Universal Support)
        $this->dataCollection = $data ?? $this->fetchData();
        $this->finalRow = $this->dataCollection->count() + 1; // +1 for Header
    }

    public function collection()
    {
        return $this->dataCollection;
    }

    private function fetchData()
    {
        $latest60Dates = cache()->remember('latest_60_sale_dates', 3600, function () {
            return DB::table('penjualan')->select('tanggal')->distinct()->orderBy('tanggal', 'desc')->limit(60)->pluck('tanggal')->toArray();
        });

        $soldProductIds = DB::table('penjualan')->whereIn('tanggal', $latest60Dates)->pluck('produk_id');
        $newProductIds = Produk::where('created_at', '>=', now()->subWeeks(2))->pluck('id');
        $allIds = $soldProductIds->merge($newProductIds)->unique();

        return Produk::with('produsen')->whereIn('id', $allIds)->orderBy('nama', 'asc')->get();
    }

    public function headings(): array
    {
        return ['#', 'ID', 'Produk', 'TTP', 'S.R', 'S.J', 'LK', 'HJ', 'Keterangan'];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 4,
            'B' => 10, // Hidden ID
            'C' => 25, // Produk
            'D' => 7,  // Titip (T)
            'E' => 7,  // SR
            'F' => 7,  // SJ
            'G' => 6,  // Laku (L)
            'H' => 6,  // HJ
            'I' => 20, // Keterangan
        ];
    }

    public function map($item): array
    {
        $this->rowCount++;
        $row = $this->rowCount + 1;

        $name = data_get($item, 'nama') ?? data_get($item, 'produk') ?? '';
        $produsenName = data_get($item, 'produsen.nama') ?? data_get($item, 'keterangan') ?? '-';

        // Color highlighting logic (Only relevant for standard template downloads)
        if (in_array($name, $this->addedNames, true)) {
            $this->highlightRows[$row] = 'FFC6EFCE';
        } elseif (in_array($name, $this->editedNames, true)) {
            $this->highlightRows[$row] = 'FFFFEB9C';
        }

        return [
            $this->rowCount,
            data_get($item, 'id') ?? data_get($item, 'produk_id'), // Kolom B (Hidden ID)
            strtoupper($name), // Kolom C (Produk)
            $this->cleanNumeric(data_get($item, 'titip', 0)), // Kolom D (T)
            $this->cleanNumeric(data_get($item, 'sr', 0)),    // Kolom E (SR)
            $this->cleanNumeric(data_get($item, 'sj', 0)),    // Kolom F (SJ)
            "=MAX(0, D{$row}-(E{$row}+F{$row}))",            // Kolom G (L)
            $this->cleanFloat(data_get($item, 'harga_jual', 0)), // Kolom H (HJ)
            strtoupper($produsenName),                       // Kolom I (Keterangan)
        ];
    }

    public function columnFormats(): array
    {
        return ['G' => '#,##0', 'H' => '#,##0'];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->finalRow;

        $styles = [
            // Header: Citroroso Emerald/Onyx Theme
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2D3748'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            // Default font dan border halus UNTUK AREA AKTIF SAJA
            "A1:I{$lastRow}" => [
                'font' => ['name' => 'Arial', 'size' => 10],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FFE2E8F0'],
                    ],
                ],
            ],
            // Column D - Titip (T) - Emerald
            "D2:D{$lastRow}" => [
                'protection' => ['locked' => Protection::PROTECTION_UNPROTECTED],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8F5E9']],
            ],
            // Column E - Sisa Return (SR) - Amber
            "E2:E{$lastRow}" => [
                'protection' => ['locked' => Protection::PROTECTION_UNPROTECTED],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'xFFFFF8E1']],
            ],
            // Column F - Sisa Jual (SJ) - Blue
            "F2:F{$lastRow}" => [
                'protection' => ['locked' => Protection::PROTECTION_UNPROTECTED],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'xFFE1F5FE']],
            ],
            // Hide L Formula
            "G2:G{$lastRow}" => [
                'protection' => ['hidden' => Protection::PROTECTION_PROTECTED],
            ],
        ];

        // Highlight untuk item baru/edit (Manual logic remains for specific rows)
        foreach ($this->highlightRows as $row => $color) {
            $styles["C{$row}"] = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $color],
                ],
                'font' => ['bold' => true],
            ];
        }

        return $styles;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->finalRow;

                // Hide standard row labels (1, 2, 3...) and column labels (A, B, C...)
                $sheet->setShowRowColHeaders(false);

                // Sembunyikan Kolom ID (B)
                $sheet->getColumnDimension('B')->setVisible(false);

                // Freeze first row
                $sheet->freezePane('A2');

                // Enable Sheet Protection
                $sheet->getProtection()->setPassword('qwe123');
                $sheet->getProtection()->setSheet(true);

                // Apply Zebra Stripes (Conditional Formatting)
                $this->applyZebraStripes($sheet, "A2:I{$lastRow}");

                // Apply Data Validation for input cells
                $this->applyCustomValidation($sheet, "D2:D{$lastRow}", 'TITIP TIDAK BOLEH KURANG DARI SISA', 2, 'D');
                $this->applyCustomValidation($sheet, "E2:E{$lastRow}", 'SISA TIDAK BOLEH MELEBIHI TITIP', 2, 'E');
                $this->applyCustomValidation($sheet, "F2:F{$lastRow}", 'SISA TIDAK BOLEH MELEBIHI TITIP', 2, 'F');

                // RED ALERT: Entire row turns BRIGHT RED if SR + SJ > T
                $this->applyRedAlert($sheet, "A2:I{$lastRow}", 2);

                // BOLD NON-ZERO: Highlight where user has input data (D, E, F) and Laku result (G)
                $this->applyBoldNonZero($sheet, "D2:G{$lastRow}");
            },
        ];
    }

    private function applyZebraStripes(Worksheet $sheet, string $range)
    {
        $conditional = new Conditional;
        $conditional->setConditionType(Conditional::CONDITION_EXPRESSION);
        $conditional->setOperatorType(Conditional::OPERATOR_NONE);
        // Formula: ISODD(ROW())
        $conditional->addCondition('=ISODD(ROW())');
        $conditional->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF9FAFB'); // Light Zebra

        $conditionalStyles = $sheet->getConditionalStyles($range);
        $conditionalStyles[] = $conditional;
        $sheet->setConditionalStyles($range, $conditionalStyles);
    }

    private function applyBoldNonZero(Worksheet $sheet, string $range)
    {
        $conditional = new Conditional;
        $conditional->setConditionType(Conditional::CONDITION_CELLIS);
        $conditional->setOperatorType(Conditional::OPERATOR_GREATERTHAN);
        $conditional->addCondition('0');
        $conditional->getStyle()->getFont()->setBold(true);

        $conditionalStyles = $sheet->getConditionalStyles($range);
        $conditionalStyles[] = $conditional;
        $sheet->setConditionalStyles($range, $conditionalStyles);
    }

    private function applyRedAlert(Worksheet $sheet, string $range, int $firstRow)
    {
        $conditional = new Conditional;
        $conditional->setConditionType(Conditional::CONDITION_EXPRESSION);
        $conditional->setOperatorType(Conditional::OPERATOR_NONE);
        // Formula: ($E + $F > $D) -> Use absolute column but relative row for the rule
        $conditional->addCondition("=(\$E{$firstRow}+\$F{$firstRow})>\$D{$firstRow}");
        $conditional->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000'); // BRIGHT RED
        $conditional->getStyle()->getFont()->getColor()->setARGB('FFFFFFFF'); // White text for readability on red

        $conditionalStyles = $sheet->getConditionalStyles($range);
        $conditionalStyles[] = $conditional;
        $sheet->setConditionalStyles($range, $conditionalStyles);
    }

    private function applyValidation(Worksheet $sheet, string $range, string $error)
    {
        $validation = $sheet->getDataValidation($range);
        $validation->setType(DataValidation::TYPE_WHOLE);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Input Error (Nilai Tidak Valid)');
        $validation->setError($error);
        $validation->setFormula1('0');
        $validation->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
    }

    private function applyCustomValidation(Worksheet $sheet, string $range, string $error, int $firstRow, string $column)
    {
        $validation = $sheet->getDataValidation($range);
        $validation->setType(DataValidation::TYPE_CUSTOM);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false); // CRITICAL: Disable 'Ignore Blank' to prevent validation bypass
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Input Error (Nilai Tidak Logis)');
        $validation->setError($error);
        // Formula: E + F <= D (AND ensure E, F and D are >= 0)
        // For column D, the check is also the same: D >= E + F
        // Use $ symbols for absolute column reference to prevent "geser" (shifting).
        $validation->setFormula1("=AND(\$D{$firstRow}>=0, \$E{$firstRow}>=0, \$F{$firstRow}>=0, (\$E{$firstRow}+\$F{$firstRow})<=\$D{$firstRow})");
    }
}
