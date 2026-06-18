<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\PenjualanTemplateExport;
use App\Models\Produk;
use App\Traits\CitroNumeric;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * LegacyFormatDetector
 *
 * Auto-detects legacy Excel format and converts to Kinetic format on-the-fly.
 *
 * Legacy Format Structure:
 *   - Data starts from row 4 (index 3)
 *   - Col G (index 6) = Product Name
 *   - Col I (index 8) = Titip, Col J (index 9) = SR, Col K (index 10) = SJ
 *   - Contains summary rows: TOTAL, JUMLAH, PROSENTASE, etc.
 *
 * New (Kinetic) Format Structure:
 *   - Header row 1: #, ID, Produk, T, SR, SJ, L, HJ, Keterangan
 *   - WithHeadingRow import via PenjualanImport
 */
class LegacyFormatDetector
{
    use CitroNumeric;

    /**
     * Known header patterns for the new Kinetic format.
     * If the first row contains ANY of these (case-insensitive), it's the new format.
     */
    private const KINETIC_HEADERS = ['produk', 'nama_produk', 'titip', 'sr', 'sj', 'sisa_jual', 'sisa_return'];

    /**
     * Summary keywords to skip in legacy format (inherited from LegacyConverterController).
     */
    private const SKIP_KEYWORDS = ['TOTAL', 'JUMLAH', 'PROSENTASE', 'LABA', 'IURAN', 'TABUNGAN', 'BAYAR', 'PAGE '];

    /**
     * Detect whether an uploaded file is in legacy format.
     * Returns true if the file is legacy, false if it's the new Kinetic format.
     */
    public function isLegacyFormat(UploadedFile $file): bool
    {
        try {
            $data = Excel::toArray([], $file);
            $sheet = $data[0] ?? [];

            if (empty($sheet) || count($sheet) < 2) {
                return false;
            }

            // Read first row as potential header
            $firstRow = array_map(function ($cell) {
                return strtolower(trim((string) ($cell ?? '')));
            }, $sheet[0]);

            // Check if any known Kinetic header keywords exist in the first row
            foreach (self::KINETIC_HEADERS as $keyword) {
                foreach ($firstRow as $cell) {
                    if ($cell === $keyword || str_contains($cell, $keyword)) {
                        return false; // This is the new format
                    }
                }
            }

            // Additional heuristic: Legacy format has data starting at row 4 (index 3)
            // with product names in column G (index 6)
            if (count($sheet) > 3) {
                $row4 = $sheet[3] ?? [];
                $colG = trim((string) ($row4[6] ?? ''));

                // If column G at row 4 contains a non-empty, non-numeric string → likely legacy
                if (! empty($colG) && ! is_numeric($colG)) {
                    Log::info("LegacyFormatDetector: File detected as LEGACY format (Col G Row 4: '{$colG}')");

                    return true;
                }
            }

            // Fallback: If no Kinetic headers found and we have data, assume legacy
            Log::info('LegacyFormatDetector: No Kinetic headers found, assuming legacy format');

            return true;

        } catch (\Throwable $e) {
            Log::warning("LegacyFormatDetector: Detection failed - {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Convert a legacy format file to Kinetic format.
     * Returns a temporary UploadedFile with the converted data, or null on failure.
     *
     * @return array{file: UploadedFile|null, stats: array}
     */
    public function convertToKinetic(UploadedFile $file): array
    {
        $stats = ['rows' => 0, 'missing' => 0, 'skipped' => 0];

        try {
            $data = Excel::toArray([], $file);
            $sheetData = $data[0] ?? [];

            if (empty($sheetData)) {
                return ['file' => null, 'stats' => $stats];
            }

            // NUCLEAR OPTIMIZATION: Prefetch all products into memory
            $productsMap = Produk::with('produsen')
                ->get()
                ->keyBy(fn ($p) => strtoupper(trim((string) $p->nama)));

            $processedRows = collect();

            // Loop starts from row index 3 (Row 4 in Excel) — Legacy convention
            for ($i = 3; $i < count($sheetData); $i++) {
                $row = $sheetData[$i];

                // Col G (Index 6) is Product Name in Legacy format
                $name = trim((string) ($row[6] ?? ''));
                if (empty($name)) {
                    continue;
                }

                // Skip Summary & Watermark Rows
                $upperName = strtoupper($name);
                $isSummary = false;
                foreach (self::SKIP_KEYWORDS as $kw) {
                    if (str_contains($upperName, $kw)) {
                        $isSummary = true;
                        break;
                    }
                }
                if ($isSummary) {
                    continue;
                }

                // Data Cols: I (8) = Titip, J (9) = SR, K (10) = SJ
                $titip = $this->cleanNumeric($row[8] ?? 0);
                $sr = $this->cleanNumeric($row[9] ?? 0);
                $sj = $this->cleanNumeric($row[10] ?? 0);

                // Skip zero-data rows
                if (($titip + $sr + $sj) === 0) {
                    $stats['skipped']++;

                    continue;
                }

                // Product lookup
                $product = $productsMap->get($upperName);

                if (! $product) {
                    $stats['missing']++;
                }

                $processedRows->push([
                    'id' => $product ? $product->id : '',
                    'nama' => $name,
                    'titip' => $titip,
                    'sr' => $sr,
                    'sj' => $sj,
                    'harga_jual' => $product ? $product->harga_jual : 0,
                    'keterangan' => $product && $product->produsen ? $product->produsen->nama : '-',
                ]);
            }

            if ($processedRows->isEmpty()) {
                return ['file' => null, 'stats' => $stats];
            }

            $stats['rows'] = $processedRows->count();

            // Generate temporary Kinetic-format Excel file
            $tempFilename = 'legacy_converted_'.uniqid().'.xlsx';
            $tempPath = 'temp/'.$tempFilename;

            $export = new PenjualanTemplateExport(null, $processedRows);
            Excel::store($export, $tempPath, 'local');

            // Create an UploadedFile from the stored temp file
            $fullPath = storage_path('app/'.$tempPath);
            $convertedFile = new UploadedFile(
                $fullPath,
                $file->getClientOriginalName(), // Keep original filename for merchant detection
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true // Mark as test to skip validation
            );

            Log::info("LegacyFormatDetector: Converted '{$file->getClientOriginalName()}' → {$stats['rows']} rows, {$stats['missing']} unmatched products");

            return ['file' => $convertedFile, 'stats' => $stats];

        } catch (\Throwable $e) {
            Log::error("LegacyFormatDetector: Conversion failed - {$e->getMessage()}");

            return ['file' => null, 'stats' => $stats];
        }
    }

    /**
     * Cleanup temporary converted files.
     */
    public function cleanup(): void
    {
        $tempFiles = Storage::disk('local')->files('temp');
        foreach ($tempFiles as $file) {
            if (str_starts_with(basename($file), 'legacy_converted_')) {
                Storage::disk('local')->delete($file);
            }
        }
    }
}
