<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailTabungan;
use App\Models\Pedagang;
use App\Models\Produsen;
use App\Traits\FinancialPeriodDetection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class TabunganController extends Controller
{
    use FinancialPeriodDetection;

    /**
     * Finalize tabungan untuk periode tertentu.
     * Mengikuti logika legacy: TabunganController@finalize
     *
     * Untuk setiap pedagang/produsen yang memiliki tabungan di periode tsb:
     * - Insert detail_tabungan: jumlah = -sum, awal = owner->tabungan, akhir = owner->tabungan - sum
     * - Update owner->tabungan -= sum
     * - Keterangan = "finalize Tabungan Periode DD MMM YYYY - DD MMM YYYY"
     */
    public function finalize(Request $request)
    {
        $validated = $request->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $startDate = $validated['startDate'];
        $endDate = $validated['endDate'];

        $startStr = date('d M Y', strtotime($startDate));
        $endStr = date('d M Y', strtotime($endDate));
        $description = "finalize Tabungan Periode {$startStr} - {$endStr}";

        DB::transaction(function () use ($startDate, $endDate, $description) {
            // Process Produsen
            $produsens = Produsen::whereNull('deleted_at')->get();
            foreach ($produsens as $produsen) {
                $sum = DetailTabungan::where('owner_type', 'Produsen')
                    ->where('owner_id', $produsen->id)
                    ->whereNull('deleted_at')
                    ->whereBetween('tanggal', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
                    ->where('keterangan', 'not like', '%finalize%')
                    ->sum('jumlah');

                if ($sum > 0) {
                    DetailTabungan::create([
                        'owner_type' => 'Produsen',
                        'owner_id' => $produsen->id,
                        'awal' => $produsen->tabungan,
                        'akhir' => $produsen->tabungan - $sum,
                        'jumlah' => -$sum,
                        'keterangan' => $description,
                        'tanggal' => $endDate,
                    ]);
                    $produsen->tabungan -= $sum;
                    $produsen->save();
                }
            }

            // Process Pedagang
            $pedagangs = Pedagang::whereNull('deleted_at')->get();
            foreach ($pedagangs as $pedagang) {
                $sum = DetailTabungan::where('owner_type', 'Pedagang')
                    ->where('owner_id', $pedagang->id)
                    ->whereNull('deleted_at')
                    ->whereBetween('tanggal', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
                    ->where('keterangan', 'not like', '%finalize%')
                    ->sum('jumlah');

                if ($sum > 0) {
                    DetailTabungan::create([
                        'owner_type' => 'Pedagang',
                        'owner_id' => $pedagang->id,
                        'awal' => $pedagang->tabungan,
                        'akhir' => $pedagang->tabungan - $sum,
                        'jumlah' => -$sum,
                        'keterangan' => $description,
                        'tanggal' => $endDate,
                    ]);
                    $pedagang->tabungan -= $sum;
                    $pedagang->save();
                }
            }

            // Clear cache agar periode baru terdeteksi
            Cache::forget('tabungan_periods_finalize_global');
            Cache::forget('tabungan_boundaries_global');
        });

        return citro_toast('✅ Finalize berhasil! Periode baru telah dibuat.', 'success')->back();
    }

    /**
     * Get preview data for finalize (AJAX)
     */
    public function previewFinalize(Request $request)
    {
        $validated = $request->validate([
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ]);

        $startDate = $validated['startDate'];
        $endDate = $validated['endDate'];

        $preview = [];
        foreach (['Produsen', 'Pedagang'] as $type) {
            $model = $type === 'Produsen' ? Produsen::class : Pedagang::class;
            $owners = $model::whereNull('deleted_at')->get();

            foreach ($owners as $owner) {
                $sum = DetailTabungan::where('owner_type', $type)
                    ->where('owner_id', $owner->id)
                    ->whereNull('deleted_at')
                    ->whereBetween('tanggal', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"])
                    ->where('keterangan', 'not like', '%finalize%')
                    ->sum('jumlah');

                if ($sum > 0) {
                    $preview[] = [
                        'type' => $type,
                        'nama' => $owner->nama,
                        'tabungan' => $owner->tabungan,
                        'sum' => $sum,
                        'akhir' => $owner->tabungan - $sum,
                    ];
                }
            }
        }

        return response()->json([
            'preview' => $preview,
            'count' => count($preview),
        ]);
    }
}
