<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedagang;
use App\Services\SalesService;
use App\Services\SetoranService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SetoranController extends Controller
{
    public function __construct(private SetoranService $setoranService) {}

    /**
     * AJAX: Toggle setoran status (double-click)
     * POST /admin/setoran/toggle
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'pedagang_id' => 'required|integer',
            'tanggal' => 'required|date',
            'status' => 'required|string|in:Ok,S,T1,T2,T3,T4,T5,reset,auto,late,not_late',
        ]);

        $user = auth()->user();
        if (! $user || ! in_array($user->owner_type, ['Admin', 'Pengurus'], true)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $result = $this->setoranService->toggleSetoran(
            (int) $request->pedagang_id,
            $request->tanggal,
            (int) $user->id,
            $request->status
        );

        return response()->json($result);
    }

    /**
     * AJAX: Get setoran amount for tooltip (hover/single-click)
     * GET /admin/setoran/amount
     */
    public function amount(Request $request): JsonResponse
    {
        $request->validate([
            'pedagang_id' => 'required|integer',
            'tanggal' => 'required|date',
        ]);

        $date = $request->tanggal;
        $pedagang = Pedagang::find($request->pedagang_id);

        if (! $pedagang) {
            return response()->json(['jumlah' => 0, 'formatted' => '-']);
        }

        // 1. Get Base Modal from raw penjualan
        $modal = DB::table('penjualan')
            ->where('pedagang_id', $pedagang->id)
            ->where('status', 'Ok')
            ->where(DB::raw('DATE(tanggal)'), $date)
            ->whereNull('deleted_at')
            ->sum(DB::raw('laku * harga_beli'));

        if ($modal <= 0) {
            return response()->json(['jumlah' => 0, 'formatted' => '-']);
        }

        // 2. Get Product Count for Iuran
        $productCount = DB::table('penjualan')
            ->where('pedagang_id', $pedagang->id)
            ->where('status', 'Ok')
            ->where(DB::raw('DATE(tanggal)'), $date)
            ->whereNull('deleted_at')
            ->distinct('produk_id')
            ->count('produk_id');

        // 3. Components using Heritage Logic (Traits/Services)
        $settings = app(SettingsService::class);
        $kas = $settings->getKasPedagang((float) $modal);

        // Iuran Calculation (from trait logic)
        $salesService = app(SalesService::class);
        $proup = $salesService->calculateMerchantProup((float) $modal, $productCount, $pedagang->nama);

        $tabungan = (float) ($pedagang->tabungan_rate ?? 0);

        // 4. Adjustments from Transaksi (Kemarin/Pembulatan)
        $transaksi = DB::table('transaksi')
            ->where('owner_type', 'Pedagang')
            ->where('owner_id', $pedagang->id)
            ->where('status', 'Ok')
            ->where(DB::raw('DATE(tanggal)'), $date)
            ->whereNull('deleted_at')
            ->first();

        $kemarin = $transaksi ? (float) $transaksi->kemarin : 0;
        $pembulatan = $transaksi ? (float) $transaksi->pembulatan : 0;

        // 5. Total Setoran Formula: Modal + Kas + Tabungan + Iuran + Kemarin + Pembulatan
        $total = $modal + $kas + $tabungan + $proup + $kemarin + $pembulatan;

        return response()->json([
            'jumlah' => (int) $total,
            'formatted' => 'Rp '.number_format((int) $total, 0, ',', '.'),
        ]);
    }
}
