<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\PenjualanTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Traits\CitroNumeric;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class LegacyConverterController extends Controller
{
    use CitroNumeric;

    public function convert(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        $file = $request->file('file');
        $dateFolder = date('Y-m-d');
        $storagePath = "public/converted/{$dateFolder}";

        if (! Storage::exists($storagePath)) {
            Storage::makeDirectory($storagePath);
        }

        $filename = $file->getClientOriginalName();

        try {
            // Read legacy data (Array mode)
            $data = Excel::toArray([], $file);
            $sheetData = $data[0] ?? [];

            if (empty($sheetData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sheet kosong',
                ], 422);
            }

            $processedRows = collect();
            $missingIds = 0;
            $skippedRows = 0;

            // NUCLEAR OPTIMIZATION: Prefetch all products into memory
            $productsMap = Produk::with('produsen')
                ->get()
                ->keyBy(fn ($p) => strtoupper(trim((string) $p->nama)));

            // Loop starts from row index 3 (Row 4 in Excel based on Gambar 1)
            for ($i = 3; $i < count($sheetData); $i++) {
                $row = $sheetData[$i];

                // Col G (Index 6) is Product Name
                $name = trim((string) ($row[6] ?? ''));
                if (empty($name)) {
                    continue;
                }

                // Skip Summary & Watermark Rows (TOTAL, JUMLAH, etc)
                $skipKeywords = ['TOTAL', 'JUMLAH', 'PROSENTASE', 'LABA', 'IURAN', 'TABUNGAN', 'BAYAR', 'PAGE '];
                $isSummary = false;
                $upperName = strtoupper($name);
                foreach ($skipKeywords as $kw) {
                    if (str_contains($upperName, $kw)) {
                        $isSummary = true;
                        break;
                    }
                }
                if ($isSummary) {
                    continue;
                }

                // Data Cols: I (8), J (9), K (10)
                $titip = $this->cleanNumeric($row[8] ?? 0);
                $sr = $this->cleanNumeric($row[9] ?? 0);
                $sj = $this->cleanNumeric($row[10] ?? 0);

                // Skip rule: if all are zero
                if (($titip + $sr + $sj) === 0) {
                    $skippedRows++;

                    continue;
                }

                // NUCLEAR LOOKUP: Zero-DB overhead memory check
                $product = $productsMap->get($upperName);

                if (! $product) {
                    $missingIds++;
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
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data valid ditemukan',
                ], 422);
            }

            // Export to Kinetic Format
            $export = new PenjualanTemplateExport(null, $processedRows);
            $savePath = "converted/{$dateFolder}/{$filename}";
            Excel::store($export, $savePath, 'public');

            return response()->json([
                'success' => true,
                'filename' => $filename,
                'rows' => $processedRows->count(),
                'missing' => $missingIds,
                'skipped' => $skippedRows,
                'download_url' => Storage::url($savePath),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error: {$e->getMessage()}",
            ], 500);
        }
    }

    public function merge(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:2',
            'files.*' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        $files = $request->file('files');
        $dateFolder = date('Y-m-d');
        $storagePath = "public/converted/{$dateFolder}";

        if (! Storage::exists($storagePath)) {
            Storage::makeDirectory($storagePath);
        }

        try {
            $merged = [];
            $totalParsedFiles = 0;
            $totalRowsBeforeMerge = 0;

            foreach ($files as $file) {
                $rows = $this->parseExcelFile($file);
                if (empty($rows)) {
                    continue;
                }

                $totalParsedFiles++;
                $totalRowsBeforeMerge += count($rows);

                foreach ($rows as $row) {
                    $name = $row['produk'];
                    $upperName = strtoupper(trim($name));

                    if (! isset($merged[$upperName])) {
                        $merged[$upperName] = [
                            'produk' => $name,
                            'titip' => 0,
                            'sr' => 0,
                            'sj' => 0,
                            'harga_jual' => 0,
                            'keterangan' => '',
                        ];
                    }

                    $merged[$upperName]['titip'] += $row['titip'];
                    $merged[$upperName]['sr'] += $row['sr'];
                    $merged[$upperName]['sj'] += $row['sj'];
                    if ($row['harga_jual'] > 0) {
                        $merged[$upperName]['harga_jual'] = $row['harga_jual'];
                    }
                    if (! empty($row['keterangan'])) {
                        $merged[$upperName]['keterangan'] = $row['keterangan'];
                    }
                }
            }

            if (empty($merged)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data valid yang bisa digabungkan dari file yang diupload.',
                ], 422);
            }

            // NUCLEAR LOOKUP: Zero-DB overhead memory check
            $productsMap = Produk::with('produsen')
                ->get()
                ->keyBy(fn ($p) => strtoupper(trim((string) $p->nama)));

            $finalRows = collect();
            $missingIds = 0;

            foreach ($merged as $upperName => $item) {
                $product = $productsMap->get($upperName);

                if (! $product) {
                    $missingIds++;
                }

                $finalRows->push([
                    'id' => $product ? $product->id : '',
                    'produk' => $item['produk'],
                    'titip' => $item['titip'],
                    'sr' => $item['sr'],
                    'sj' => $item['sj'],
                    'harga_jual' => $product ? $product->harga_jual : $item['harga_jual'],
                    'keterangan' => $product && $product->produsen ? $product->produsen->nama : ($item['keterangan'] ?: '-'),
                ]);
            }

            // Sort alphabetical by product name
            $finalRows = $finalRows->sortBy('produk')->values();

            // Export to Kinetic Format
            $export = new PenjualanTemplateExport(null, $finalRows);
            $filename = 'merged_laporan_'.date('His').'.xlsx';
            $savePath = "converted/{$dateFolder}/{$filename}";
            Excel::store($export, $savePath, 'public');

            return response()->json([
                'success' => true,
                'filename' => $filename,
                'files_merged' => $totalParsedFiles,
                'rows_before' => $totalRowsBeforeMerge,
                'rows_after' => $finalRows->count(),
                'missing' => $missingIds,
                'download_url' => Storage::url($savePath),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error: {$e->getMessage()}",
            ], 500);
        }
    }

    private function parseExcelFile($file)
    {
        $data = Excel::toArray([], $file);
        $sheetData = $data[0] ?? [];
        if (empty($sheetData)) {
            return [];
        }

        $rows = [];
        $headerRowIndex = -1;
        $colMap = [
            'produk' => -1,
            'titip' => -1,
            'sr' => -1,
            'sj' => -1,
            'harga_jual' => -1,
            'keterangan' => -1,
        ];

        // Attempt to detect standard/new format header in the first 5 rows
        for ($i = 0; $i < min(5, count($sheetData)); $i++) {
            $row = $sheetData[$i];
            $isHeader = false;
            foreach ($row as $colIdx => $val) {
                $valClean = strtoupper(trim((string) $val));
                if (in_array($valClean, ['PRODUK', 'NAMA PRODUK', 'NAMA_PRODUK', 'PRODUCT', 'TTP', 'TITIP', 'S.R', 'S.J', 'LK', 'LAKU'], true)) {
                    $isHeader = true;
                }
            }
            if ($isHeader) {
                $headerRowIndex = $i;
                // Map headers
                foreach ($row as $colIdx => $val) {
                    $valClean = strtoupper(trim((string) $val));
                    if (in_array($valClean, ['PRODUK', 'NAMA PRODUK', 'NAMA_PRODUK', 'PRODUCT'], true)) {
                        $colMap['produk'] = $colIdx;
                    } elseif (in_array($valClean, ['TTP', 'TITIP', 'T', 'TITIPAN'], true)) {
                        $colMap['titip'] = $colIdx;
                    } elseif (in_array($valClean, ['S.R', 'SR', 'SISA RETURN', 'SISA_RETURN', 'RETUR', 'RETURN', 'RTN'], true)) {
                        $colMap['sr'] = $colIdx;
                    } elseif (in_array($valClean, ['S.J', 'SJ', 'SISA JUAL', 'SISA_JUAL', 'SISAJUAL'], true)) {
                        $colMap['sj'] = $colIdx;
                    } elseif (in_array($valClean, ['HJ', 'HARGA JUAL', 'HARGA_JUAL', 'HARGA'], true)) {
                        $colMap['harga_jual'] = $colIdx;
                    } elseif (in_array($valClean, ['KETERANGAN', 'KET', 'PRODUSEN'], true)) {
                        $colMap['keterangan'] = $colIdx;
                    }
                }
                break;
            }
        }

        if ($headerRowIndex !== -1 && $colMap['produk'] !== -1) {
            // Standard / New Format found
            for ($i = $headerRowIndex + 1; $i < count($sheetData); $i++) {
                $row = $sheetData[$i];
                $name = trim((string) ($row[$colMap['produk']] ?? ''));
                if (empty($name)) {
                    continue;
                }

                // Skip summary rows
                $skipKeywords = ['TOTAL', 'JUMLAH', 'PROSENTASE', 'LABA', 'IURAN', 'TABUNGAN', 'BAYAR', 'PAGE '];
                $isSummary = false;
                $upperName = strtoupper($name);
                foreach ($skipKeywords as $kw) {
                    if (str_contains($upperName, $kw)) {
                        $isSummary = true;
                        break;
                    }
                }
                if ($isSummary) {
                    continue;
                }

                $titip = $colMap['titip'] !== -1 ? $this->cleanNumeric($row[$colMap['titip']] ?? 0) : 0;
                $sr = $colMap['sr'] !== -1 ? $this->cleanNumeric($row[$colMap['sr']] ?? 0) : 0;
                $sj = $colMap['sj'] !== -1 ? $this->cleanNumeric($row[$colMap['sj']] ?? 0) : 0;
                $hj = $colMap['harga_jual'] !== -1 ? $this->cleanFloat($row[$colMap['harga_jual']] ?? 0) : 0;
                $keterangan = $colMap['keterangan'] !== -1 ? trim((string) ($row[$colMap['keterangan']] ?? '')) : '';

                $rows[] = [
                    'produk' => $name,
                    'titip' => $titip,
                    'sr' => $sr,
                    'sj' => $sj,
                    'harga_jual' => $hj,
                    'keterangan' => $keterangan,
                ];
            }
        } else {
            // Fallback: Check if it's Legacy Format (Old Format)
            // Legacy Format starts at row index 3, Product is at index 6, Titip is index 8, SR is index 9, SJ is index 10.
            for ($i = 3; $i < count($sheetData); $i++) {
                $row = $sheetData[$i];
                $name = trim((string) ($row[6] ?? ''));
                if (empty($name)) {
                    continue;
                }

                // Skip summary rows
                $skipKeywords = ['TOTAL', 'JUMLAH', 'PROSENTASE', 'LABA', 'IURAN', 'TABUNGAN', 'BAYAR', 'PAGE '];
                $isSummary = false;
                $upperName = strtoupper($name);
                foreach ($skipKeywords as $kw) {
                    if (str_contains($upperName, $kw)) {
                        $isSummary = true;
                        break;
                    }
                }
                if ($isSummary) {
                    continue;
                }

                $titip = $this->cleanNumeric($row[8] ?? 0);
                $sr = $this->cleanNumeric($row[9] ?? 0);
                $sj = $this->cleanNumeric($row[10] ?? 0);

                $rows[] = [
                    'produk' => $name,
                    'titip' => $titip,
                    'sr' => $sr,
                    'sj' => $sj,
                    'harga_jual' => 0,
                    'keterangan' => '',
                ];
            }
        }

        return $rows;
    }
}
