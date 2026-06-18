<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DetailTabungan;
use App\Models\Pedagang;
use App\Models\Pembulatan;
use App\Models\Transaksi;
use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;

class PublicNotaController extends Controller
{
    /**
     * Public view for printing nota without authentication
     * Hardcoded for specific date by default as requested
     */
    public function show($date = '2026-04-25')
    {
        $settings = app(SettingsService::class);
        $allowedDates = $settings->get('public_nota_dates', []);

        if (! in_array($date, $allowedDates, true)) {
            abort(403, 'Akses Publik Ditolak. Tanggal ini belum diizinkan oleh Admin.');
        }

        $start = $date.' 00:00:00';
        $end = $date.' 23:59:59';

        // 1. Fetch all sales for the date
        $penjualans = DB::table('penjualan as p')
            ->join('produk as pdk', 'p.produk_id', '=', 'pdk.id')
            ->join('produsen as prd', 'pdk.produsen_id', '=', 'prd.id')
            ->join('pedagang as ped', 'p.pedagang_id', '=', 'ped.id')
            ->whereNull('p.deleted_at')
            ->whereIn('p.status', ['Draft', 'Pending', 'Ok'])
            ->whereBetween('p.tanggal', [$start, $end])
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
            ->orderBy('prd.bundle_ke', 'asc')
            ->orderBy('prd.nama', 'asc')
            ->get();

        if ($penjualans->isEmpty()) {
            return "Maaf, tidak ada data penjualan pada tanggal {$date}.";
        }

        $produsenIds = $penjualans->pluck('produsen_id')->unique()->toArray();

        // 2. Fetch Transactions
        $allTransaksis = Transaksi::where('owner_type', 'Produsen')
            ->whereIn('owner_id', $produsenIds)
            ->whereBetween('tanggal', [$start, $end])
            ->with(['details'])
            ->get();

        $transaksiIds = $allTransaksis->pluck('id')->toArray();
        $detailTabungans = DetailTabungan::whereIn('transaksi_id', $transaksiIds)
            ->get()
            ->keyBy('transaksi_id');

        $allTransaksisGrouped = $allTransaksis->groupBy('owner_id');

        // 3. Fetch Rounding data
        $allPembulatans = Pembulatan::whereIn('produsen_id', $produsenIds)
            ->get()
            ->keyBy('produsen_id');

        // 4. Master Merchant List (for single-product nota alignment)
        $masterPedagangsFormatted = DB::table('penjualan as p')
            ->join('pedagang as ped', 'p.pedagang_id', '=', 'ped.id')
            ->whereNull('p.deleted_at')
            ->whereIn('p.status', ['Draft', 'Pending', 'Ok'])
            ->whereBetween('p.tanggal', [$start, $end])
            ->select('ped.id', 'ped.nama', 'ped.gender')
            ->distinct()
            ->orderBy('ped.nama')
            ->get()
            ->map(function ($p) {
                return (object) [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'gender' => $p->gender,
                    'display_name' => ($p->gender === 'female' ? 'B. ' : 'P. ').$p->nama,
                ];
            });

        $masterPedagangIds = $masterPedagangsFormatted->pluck('id')->toArray();
        $allActivePedagangs = Pedagang::hanyaAktif()->orderBy('nama')->get();
        $liburPedagangs = $allActivePedagangs->filter(fn ($p) => ! in_array($p->id, $masterPedagangIds, true));

        $groupedData = $penjualans->groupBy('produsen_id');

        $notads = [];
        $globalCounter = 1;

        foreach ($groupedData as $produsenId => $produsenPenjualans) {
            $firstItem = $produsenPenjualans->first();
            $produsen = (object) [
                'id' => $produsenId,
                'nama' => $firstItem->produsen_nama,
                'bundle_ke' => $firstItem->bundle_ke,
                'gender' => $firstItem->produsen_gender,
                'tabungan_rate' => 5000,
            ];

            $transaksi = $allTransaksisGrouped->get($produsenId)?->first();

            if ($transaksi) {
                $transaksi->lain_sum = $transaksi->details->sum('jumlah');
                $transaksi->tabungan_sum = $detailTabungans->get($transaksi->id)?->jumlah ?? 0;
            }

            if (! $transaksi || $transaksi->jumlah <= 0) {
                continue;
            }

            $pembulatanData = $allPembulatans->get($produsenId);
            $totalBayarProdusen = $produsenPenjualans->sum(fn ($i) => (float) $i->laku * (float) $i->harga_beli);

            $perProduk = $produsenPenjualans->groupBy('produk_id')
                ->sortByDesc(fn ($items) => $items->count());

            // CITROROSO GANDING LOGIC: Pairing Longest and Shortest products into bundles (max 45 rows)
            $products = $perProduk->values();
            $notaBundles = [];
            $left = 0;
            $right = $products->count() - 1;

            while ($left <= $right) {
                // If only one product remains
                if ($left === $right) {
                    $items = $products[$left];
                    $notaBundles[] = [['id' => $items->first()->produk_id, 'items' => $items]];
                    break;
                }

                $longest = $products[$left];
                $shortest = $products[$right];

                // Heuristic: merchantCount + 2 per product section (Header/Footer padding)
                $longestRows = $longest->count() + 2;
                $shortestRows = $shortest->count() + 2;

                if (($longestRows + $shortestRows) <= 45) {
                    // Fits together: Pair Longest with Shortest
                    $notaBundles[] = [
                        ['id' => $longest->first()->produk_id, 'items' => $longest],
                        ['id' => $shortest->first()->produk_id, 'items' => $shortest],
                    ];
                    $left++;
                    $right--;
                } else {
                    // Doesn't fit: Longest goes alone, Shortest remains for next attempt
                    $notaBundles[] = [['id' => $longest->first()->produk_id, 'items' => $longest]];
                    $left++;
                }
            }

            $isFirstNotaForProdusen = true;

            foreach ($notaBundles as $bundleProducts) {
                $sections = [];
                $isSingleProductNota = (count($bundleProducts) === 1);

                foreach ($bundleProducts as $pData) {
                    $items = $pData['items'];
                    $firstProdItem = $items->first();
                    $produk = (object) [
                        'id' => $pData['id'],
                        'nama' => $firstProdItem->produk_nama,
                        'harga_beli' => $firstProdItem->harga_beli,
                    ];

                    $sumTitip = $sumLaku = $sumSisaJual = $sumReturn = $sumBayar = 0;

                    if ($isSingleProductNota) {
                        $itemsByPedagang = $items->keyBy('pedagang_id');
                        $mappedItems = $masterPedagangsFormatted->map(function ($pedagang) use ($itemsByPedagang, &$sumTitip, &$sumLaku, &$sumSisaJual, &$sumReturn, &$sumBayar) {
                            $p = $itemsByPedagang->get($pedagang->id);
                            $p_titip = $p ? (int) $p->titip : 0;
                            $p_laku = $p ? (int) $p->laku : 0;
                            $p_sisa = $p ? (int) $p->sisa_jual : 0;
                            // Rumus: Retur = Titip - Laku - Sisa Jual
                            $p_ret = $p_titip - $p_laku - $p_sisa;
                            $p_bayar = $p_laku * (float) ($p->harga_beli ?? 0);

                            $sumTitip += $p_titip;
                            $sumLaku += $p_laku;
                            $sumSisaJual += $p_sisa;
                            $sumReturn += $p_ret;
                            $sumBayar += $p_bayar;

                            return (object) [
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
                        });
                    } else {
                        $mappedItems = $items->sortBy(fn ($p) => $p->pedagang_nama)->map(function ($p) use (&$sumTitip, &$sumLaku, &$sumSisaJual, &$sumReturn, &$sumBayar) {
                            $p_titip = (int) $p->titip;
                            $p_laku = (int) $p->laku;
                            $p_sisa = (int) $p->sisa_jual;
                            // Rumus: Retur = Titip - Laku - Sisa Jual
                            $p_ret = $p_titip - $p_laku - $p_sisa;
                            $p_bayar = $p_laku * (float) $p->harga_beli;

                            $sumTitip += $p_titip;
                            $sumLaku += $p_laku;
                            $sumSisaJual += $p_sisa;
                            $sumReturn += $p_ret;
                            $sumBayar += $p_bayar;

                            return (object) [
                                'p_display_name' => ($p->pedagang_gender === 'female' ? 'B. ' : 'P. ').$p->pedagang_nama,
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
                        });
                    }

                    $sections[] = [
                        'produk' => $produk,
                        'items' => $mappedItems,
                        'sumTitip' => $sumTitip,
                        'sumLaku' => $sumLaku,
                        'sumSisaJual' => $sumSisaJual,
                        'sumReturn' => $sumReturn,
                        'sumBayar' => $sumBayar,
                    ];
                }

                $notads[] = [
                    'no_nota' => $globalCounter++,
                    'produsen' => $produsen,
                    'sections' => $sections,
                    'totalBayarProdusen' => $totalBayarProdusen,
                    'transaksi' => $transaksi,
                    'pembulatan' => $pembulatanData,
                    'tanggal' => $date,
                    'is_first_produk' => $isFirstNotaForProdusen,
                ];
                $isFirstNotaForProdusen = false;
            }
        }

        return view('admin.nota.print', [
            'notads' => collect($notads),
            'date' => $date,
            'liburPedagangs' => $liburPedagangs,
        ]);
    }
}
