<?php

declare(strict_types=1);

namespace App\Services;

use App\Exports\PenjualanSnapshotExport;
use App\Models\Pedagang;
use App\Models\Penjualan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class BackupService
{
    /**
     * Backup an uploaded file
     */
    public function saveUpload(UploadedFile $file, $pedagangId, string $date)
    {
        $pedagang = Pedagang::find($pedagangId);
        $merchantName = $pedagang ? str_replace(' ', '_', $pedagang->nama) : 'Unknown';
        $filename = "{$merchantName}_UPLOAD.xlsx";

        $path = "history/upload/{$date}";

        // Ensure directory exists in public disk
        Storage::disk('public')->makeDirectory($path);

        // Copy the file
        Storage::disk('public')->putFileAs($path, $file, $filename);
    }

    /**
     * Create an Excel snapshot from manual input (DB data)
     */
    public function saveManual(int $pedagangId, string $date)
    {
        $pedagang = Pedagang::find($pedagangId);
        $merchantName = $pedagang ? str_replace(' ', '_', $pedagang->nama) : 'Unknown';
        $filename = "{$merchantName}_MANUAL.xlsx";

        $path = "history/manual/{$date}";

        // Fetch current data for snapshot
        $data = Penjualan::with('produk.produsen')
            ->where('pedagang_id', $pedagangId)
            ->where('tanggal', $date)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->produk_id,
                    'nama' => $p->produk->nama ?? 'Unknown',
                    'titip' => $p->titip,
                    'sr' => $p->titip - $p->laku - $p->sisa_jual, // Calculate SR
                    'sj' => $p->sisa_jual,
                    'harga_jual' => $p->harga_jual,
                    'produsen' => $p->produk->produsen->nama ?? '-',
                ];
            });

        if ($data->isEmpty()) {
            return;
        }

        // Store to public history
        Excel::store(new PenjualanSnapshotExport($data), "{$path}/{$filename}", 'public');
    }
}
