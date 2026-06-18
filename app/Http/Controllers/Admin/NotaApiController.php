<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedagang;
use App\Models\Pembulatan;
use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class NotaApiController extends Controller
{
    /**
     * Get nota data as JSON for direct rendering
     */
    public function index(Request $request): JsonResponse
    {
        // Add CORS headers for same-origin requests
        // Laravel handles this via session/auth, no special headers needed for same-origin
        
        $user = auth('moonshine')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $produsenId = $request->input('produsen_id', $user->owner_id);
        
        // Verify access
        if ($user->owner_type === 'Produsen' && $user->owner_id != $produsenId) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $date = $request->input('date', now()->toDateString());
        $start = $date . ' 00:00:00';
        $end = $date . ' 23:59:59';

        // Get produk IDs for this produsen
        $produsenProdukIds = Produk::where('produsen_id', $produsenId)->pluck('id')->toArray();

        $penjualans = DB::table('penjualan as p')
            ->join('produk as pdk', 'p.produk_id', '=', 'pdk.id')
            ->join('produsen as prd', 'pdk.produsen_id', '=', 'prd.id')
            ->join('pedagang as ped', 'p.pedagang_id', '=', 'ped.id')
            ->whereNull('p.deleted_at')
            ->whereIn('p.status', ['Draft', 'Pending', 'Ok'])
            ->whereBetween('p.tanggal', [$start, $end])
            ->whereIn('p.produk_id', $produsenProdukIds)
            ->select([
                'p.id',
                'p.produk_id',
                'p.pedagang_id',
                'p.titip',
                'p.laku',
                'p.sisa_jual',
                'p.harga_jual',
                'p.harga_beli',
                'p.tanggal',
                'pdk.nama as produk_nama',
                'pdk.produsen_id',
                'prd.nama as produsen_nama',
                'prd.bundle_ke',
                'prd.gender as produsen_gender',
                'ped.nama as pedagang_nama',
                'ped.gender as pedagang_gender',
            ])
            ->orderBy('prd.nama', 'asc')
            ->get();

        if ($penjualans->isEmpty()) {
            return response()->json([
                'success' => true,
                'has_data' => false,
                'date' => $date,
                'notads' => [],
            ]);
        }

        // Get master pedagang list
        $masterPedagangsFormatted = DB::table('penjualan as p')
            ->join('pedagang as ped', 'p.pedagang_id', '=', 'ped.id')
            ->whereNull('p.deleted_at')
            ->whereIn('p.status', ['Draft', 'Pending', 'Ok'])
            ->whereBetween('p.tanggal', [$start, $end])
            ->whereIn('p.produk_id', $produsenProdukIds)
            ->select('ped.id', 'ped.nama', 'ped.gender')
            ->distinct()
            ->orderBy('ped.nama')
            ->get()
            ->map(function ($p) {
                return (object) [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'gender' => $p->gender,
                    'display_name' => ($p->gender === 'female' ? 'B. ' : 'P. ') . $p->nama,
                ];
            });

        // Get transaksi data
        $transaksi = Transaksi::where('owner_type', 'Produsen')
            ->where('owner_id', $produsenId)
            ->whereBetween('tanggal', [$start, $end])
            ->with(['details'])
            ->first();

        // Get pembulatan
        $pembulatan = Pembulatan::where('produsen_id', $produsenId)->first();

        // Get pedagang libur
        $masterPedagangIds = $masterPedagangsFormatted->pluck('id')->toArray();
        $liburPedagangs = DB::table('pedagang')
            ->whereNull('deleted_at')
            ->whereNotIn('id', $masterPedagangIds)
            ->orderBy('nama')
            ->get(['id', 'nama', 'gender'])
            ->map(function ($p) {
                return (object) [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'gender' => $p->gender,
                    'display_name' => ($p->gender === 'female' ? 'B. ' : 'P. ') . $p->nama,
                ];
            });

        // Build nota data
        $firstItem = $penjualans->first();
        $produsen = (object) [
            'id' => $produsenId,
            'nama' => $firstItem->produsen_nama,
            'bundle_ke' => $firstItem->bundle_ke,
            'gender' => $firstItem->produsen_gender,
            'tabungan_rate' => 5000,
        ];

        $totalBayarProdusen = $penjualans->sum(fn ($i) => (float) $i->laku * (float) $i->harga_beli);

        // Group by produk
        $perProduk = $penjualans->groupBy('produsen_id');

        $notads = [];
        $globalCounter = 1;

        foreach ($perProduk as $pid => $produsenPenjualans) {
            $firstProdItem = $produsenPenjualans->first();
            $produsenObj = (object) [
                'id' => $pid,
                'nama' => $firstProdItem->produsen_nama,
                'bundle_ke' => $firstProdItem->bundle_ke,
                'gender' => $firstProdItem->produsen_gender,
                'tabungan_rate' => 5000,
            ];

            $perProdukGroup = $produsenPenjualans->groupBy('produk_id')
                ->sortByDesc(fn ($items) => $items->count());

            foreach ($perProdukGroup as $produkId => $items) {
                $firstItem = $items->first();
                $produk = (object) [
                    'id' => $produkId,
                    'nama' => $firstItem->produk_nama,
                    'harga_beli' => $firstItem->harga_beli,
                ];

                $sumTitip = 0;
                $sumLaku = 0;
                $sumSisaJual = 0;
                $sumReturn = 0;
                $sumBayar = 0;

                // Map all pedagang
                $itemsByPedagang = $items->keyBy('pedagang_id');
                $mappedItems = [];
                foreach ($masterPedagangsFormatted as $pedagang) {
                    $p = $itemsByPedagang->get($pedagang->id);

                    $p_titip = $p ? (int) $p->titip : 0;
                    $p_laku = $p ? (int) $p->laku : 0;
                    $p_sisa = $p ? (int) $p->sisa_jual : 0;
                    $p_ret = $p_titip - $p_laku - $p_sisa;
                    $p_bayar = $p_laku * (float) ($p->harga_beli ?? 0);

                    $sumTitip += $p_titip;
                    $sumLaku += $p_laku;
                    $sumSisaJual += $p_sisa;
                    $sumReturn += $p_ret;
                    $sumBayar += $p_bayar;

                    $mappedItems[] = (object) [
                        'p_display_name' => $pedagang->display_name,
                        'titip' => $p_titip,
                        'laku' => $p_laku,
                        'sisa_jual' => $p_sisa,
                        'ret' => $p_ret,
                        'f_bayar' => number_format($p_bayar, 0, ',', '.'),
                        'c_titip' => ($p_titip !== 0 ? 'b' : ''),
                        'c_sisa' => ($p_sisa !== 0 ? 'b' : ''),
                        'c_ret' => ($p_ret !== 0 ? 'b' : ''),
                        'c_laku' => ($p_laku !== 0 ? 'b' : ''),
                        'c_bayar' => ($p_bayar !== 0 ? 'b' : ''),
                        'is_r' => ($p_titip === 0),
                    ];
                }

                $avgLaku = $sumTitip > 0 ? round(($sumLaku / $sumTitip) * 100) : 0;

                $notads[] = [
                    'no_nota' => $globalCounter++,
                    'produsen' => $produsenObj,
                    'produk' => $produk,
                    'items' => $mappedItems,
                    'sumTitip' => $sumTitip,
                    'sumLaku' => $sumLaku,
                    'sumSisaJual' => $sumSisaJual,
                    'sumReturn' => $sumReturn,
                    'sumBayar' => $sumBayar,
                    'avgLaku' => $avgLaku,
                    'totalBayarProdusen' => $totalBayarProdusen,
                    'transaksi' => $transaksi,
                    'pembulatan' => $pembulatan,
                    'tanggal' => $date,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'has_data' => true,
            'date' => $date,
            'produsen' => $produsen,
            'notads' => $notads,
            'libur_pedagangs' => $liburPedagangs,
        ]);
    }
}
