<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailTabungan;
use App\Models\Pedagang;
use App\Models\Pembulatan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Services\NotaBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * FROZEN DOMAIN - DO NOT MODIFY
 * @final This class is frozen and must not be changed without explicit permission
 */
final class NotaController extends Controller
{
    /**
     * View for clean printing in a new tab
     */
    public function print(Request $request)
    {
        $user = auth()->user();
        
        // For iframe requests, we need to handle auth differently
        // If no user but has filter_produsen, try to get session-based auth
        if (!$user && request('iframe') && request('filter_produsen')) {
            // Iframe mode - try to get user from session
            $user = auth('moonshine')->user();
        }
        
        if ($user && $user->owner_type === 'Pedagang') {
            return citro_toast('Akses Ditolak: Pedagang tidak diizinkan mencetak nota batch.', 'error');
        }

        $latestDate = Penjualan::whereNull('deleted_at')
            ->max('tanggal');

        $date = request('date', $latestDate ? substr($latestDate, 0, 10) : date('Y-m-d'));
        $start = $date.' 00:00:00';
        $end = $date.' 23:59:59';

        // 1. Ambil semua Penjualan harian (Batch) - Aligned with index
        $search = request('search');

        // OPTIMIZATION: Get product IDs for filtering instead of whereHas
        // Support both auth-based filtering and explicit filter_produsen parameter
        $produsenProdukIds = [];
        $filterProdusen = request('filter_produsen');
        
        if ($filterProdusen) {
            // Explicit filter for iframe embed (e.g., from dashboard preview)
            $produsenProdukIds = Produk::where('produsen_id', $filterProdusen)->pluck('id')->toArray();
        } elseif ($user && $user->owner_type === 'Produsen') {
            // Auth-based filtering for logged-in Produsen
            $produsenProdukIds = Produk::where('produsen_id', $user->owner_id)->pluck('id')->toArray();
        }

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
            ->when($produsenProdukIds, function ($q) use ($produsenProdukIds) {
                $q->whereIn('p.produk_id', $produsenProdukIds);
            })
            ->when($search, function ($query, $search) {
                $query->whereAny(['pdk.nama', 'prd.nama'], 'like', "%{$search}%");
            })
            ->orderBy('prd.bundle_ke', 'asc')
            ->orderBy('prd.nama', 'asc')
            ->get();

        // Filter redundant karena INNER JOIN menjamin produk & pedagang ada
        // sortBy sudah dilakukan di SQL (orderBy)

        // 2. Cegah error jika data kosong (UX: User-Friendly redirect)
        // For iframe mode, return a simple empty state instead of redirect
        if ($penjualans->isEmpty()) {
            if (request('iframe')) {
                return '<div style="padding: 20px; text-align: center; color: #999;">Tidak ada data penjualan pada tanggal ini.</div>';
            }
            return citro_toast("Maaf, tidak ada data penjualan pada tanggal {$date} untuk dicetak.", 'error')->back();
        }

        // 3. Ambil semua ID Produsen yang terlibat
        $produsenIds = $penjualans->pluck('produsen_id')->unique()->toArray();
        // FIX: Also detect single-product notes regardless of filter_produsen parameter
        $produsenProductCounts = $penjualans->groupBy('produsen_id')->map(fn($p) => $p->pluck('produk_id')->unique()->count());
        $isSingleProdusen = (count($produsenIds) === 1 && $filterProdusen) 
            || ($produsenProductCounts->count() === 1 && $produsenProductCounts->first() === 1);
        
        // 4. Ambil semua Transaksi Produsen (Batch) - OPTIMIZED
        $transaksiQuery = Transaksi::where('owner_type', 'Produsen')
            ->whereIn('owner_id', $produsenIds)
            ->whereBetween('tanggal', [$start, $end]);
        
        if ($isSingleProdusen) {
            // Single produsen - eager load details
            $transaksiQuery->with(['details']);
        }
        
        $allTransaksis = $transaksiQuery->get();
        
        // Build detailTabungans map (always need separate query)
        $transaksiIds = $allTransaksis->pluck('id')->toArray();
        $detailTabungans = DetailTabungan::whereIn('transaksi_id', $transaksiIds)->get()->keyBy('transaksi_id');
        
        $allTransaksis = $allTransaksis->groupBy('owner_id');

        // 5. Ambil semua data Pembulatan (Batch)
        $allPembulatans = Pembulatan::whereIn('produsen_id', $produsenIds)
            ->get()
            ->keyBy('produsen_id');

        // 6. MASTER LIST PEDAGANG (Yang jualan hari ini untuk penyeragaman produk)
        // FIX: Always fetch master pedagang list for single-product nota mapping
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

        // 7. PEDAGANG LIBUR - OPTIMIZED: Skip for single produsen iframe mode
        $liburPedagangs = collect();
        if (!$isSingleProdusen || !request('iframe')) {
            $allActivePedagangs = Pedagang::hanyaAktif()->orderBy('nama')->get();
            $liburPedagangs = $allActivePedagangs->filter(fn ($p) => ! in_array($p->id, $masterPedagangIds, true));
        }

        $groupedData = $penjualans->groupBy('produsen_id');

        $notads = [];
        $globalCounter = 1;

        foreach ($groupedData as $produsenId => $produsenPenjualans) {
            /** @var Collection $produsenPenjualans */
            $firstItem = $produsenPenjualans->first();
            $produsen = (object) [
                'id' => $produsenId,
                'nama' => $firstItem->produsen_nama,
                'bundle_ke' => $firstItem->bundle_ke,
                'gender' => $firstItem->produsen_gender,
                'tabungan_rate' => 5000,
            ];
            $transaksisForProdusen = $allTransaksis->get($produsenId);
            $transaksi = null;

            if ($transaksisForProdusen && $transaksisForProdusen->isNotEmpty()) {
                $transaksi = clone $transaksisForProdusen->first();
                $transaksi->jumlah = $transaksisForProdusen->sum('jumlah');
                $transaksi->kas = $transaksisForProdusen->sum('kas');
                $transaksi->kemarin = $transaksisForProdusen->sum('kemarin');
                $transaksi->pembulatan = $transaksisForProdusen->sum('pembulatan');

                // Aggregate snapshots if they exist
                $aggBruto = 0;
                $aggTabungan = 0;
                $aggLain = 0;
                $aggKemarin = 0;
                $aggRounding = 0;
                $aggCarry = 0;
                $hasValidSnapshot = true;

                foreach ($transaksisForProdusen as $t) {
                    if (! empty($t->keterangan)) {
                        $decoded = json_decode((string) $t->keterangan, true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['v'])) {
                            $aggBruto += (float) ($decoded['bruto'] ?? 0);
                            $aggTabungan += (float) ($decoded['tabungan'] ?? 0);
                            $aggLain += (float) ($decoded['lain'] ?? 0);
                            $aggKemarin += (float) ($decoded['kemarin'] ?? $t->kemarin ?? 0);
                            
                            // Fallback ke properti kolom DB jika di dalam JSON lama (v3.2) belum ada field rounding/carry
                            $aggRounding += (float) ($decoded['rounding'] ?? $decoded['pembulatan'] ?? $t->pembulatan ?? 0);
                            $aggCarry += (float) ($decoded['carry'] ?? 0);
                        } else {
                            $hasValidSnapshot = false;
                        }
                    } else {
                        $hasValidSnapshot = false;
                    }
                }

                if ($hasValidSnapshot) {
                    $transaksi->keterangan = json_encode([
                        'bruto' => $aggBruto,
                        'tabungan' => $aggTabungan,
                        'lain' => $aggLain,
                        'kemarin' => $aggKemarin,
                        'rounding' => $aggRounding,
                        'carry' => $aggCarry,
                        'v' => '3.3', // Aggregated with v3.3 structure
                    ]);
                } else {
                    $transaksi->keterangan = null;
                }

                // Lain-lain dan tabungan (legacy fallback/rekonstruksi)
                $transaksi->lain_sum = $transaksisForProdusen->sum(fn ($t) => $t->details->sum('jumlah'));
                $transaksi->tabungan_sum = $transaksisForProdusen->sum(fn ($t) => $detailTabungans->get($t->id)?->jumlah ?? 0);
            }

            // Hitung nilai Payout (Uang Hari Ini) untuk menentukan apakah nota perlu dicetak
            $simResult = null;
            $payout = 0;
            if ($transaksi && strtolower($transaksi->status) === 'ok') {
                if (!empty($transaksi->keterangan)) {
                    $decoded = json_decode((string)$transaksi->keterangan, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['payout'])) {
                        $payout = (float) $decoded['payout'];
                    } else {
                        $payout = (float) $transaksi->jumlah;
                    }
                } else {
                    $payout = (float) $transaksi->jumlah;
                }
            } else {
                // Untuk simulasi, hitung ulang (gunakan SettlementService)
                $bayar = $produsenPenjualans->sum(fn ($i) => (float) $i->laku * (float) $i->harga_beli);
                
                $lastTransaksi = Transaksi::where('owner_type', 'Produsen')
                    ->where('owner_id', $produsenId)
                    ->where('status', 'Ok')
                    ->orderBy('tanggal', 'desc')
                    ->first();
                    
                $kemarinStore = 0;
                $keteranganStore = null;
                $lainOverride = null;
                
                if ($lastTransaksi) {
                    $kemarinStore = -((float) ($lastTransaksi->pembulatan ?? 0));
                    $keteranganStore = $lastTransaksi->keterangan;
                    if (!empty($keteranganStore)) {
                        $decodedLast = json_decode((string)$keteranganStore, true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($decodedLast['carry'])) {
                            $carryLast = (float) $decodedLast['carry'];
                            $currentLainLain = $transaksi ? (float) (\App\Models\DetailTransaksi::where('transaksi_id', $transaksi->id)->sum('jumlah') ?? 0) : 0;
                            $lainOverride = $currentLainLain + $carryLast;
                        }
                    }
                }
                
                $settlementService = app(\App\Services\SettlementService::class);
                $simResult = $settlementService->previewProdusenSettlement(
                    $bayar,
                    $kemarinStore,
                    (float) ($produsen->tabungan_rate ?? 0),
                    $transaksi ? $transaksi->id : 0,
                    $keteranganStore,
                    $lainOverride
                );
                
                $payout = $simResult['payout'];
            }
            
            // Guard: Jangan cetak nota jika uang hari ini <= 0
            if ($payout <= 0) {
                continue;
            }
            $totalBayarProdusen = $produsenPenjualans->sum(fn ($i) => (float) $i->laku * (float) $i->harga_beli);

            // Group products and sort by merchant count (most merchants first)
            // CITROROSO GANDING LOGIC: Pairing Longest and Shortest products into bundles (max 45 rows)
            $perProduk = $produsenPenjualans->groupBy('produk_id')
                ->sortByDesc(fn ($items) => $items->count());

            $products = $perProduk->values()->all();
            $used = array_fill(0, count($products), false);
            $notaBundles = [];

            for ($i = 0; $i < count($products); $i++) {
                if ($used[$i]) {
                    continue;
                }

                // Start a new bundle with the longest unused product
                $bundle = [];
                $longest = $products[$i];
                $bundle[] = ['id' => $longest->first()->produk_id, 'items' => $longest];
                $used[$i] = true;

                $currentRows = $longest->count() + 2;

                // Search from the shortest remaining products (right to left) to pack more
                for ($j = count($products) - 1; $j > $i; $j--) {
                    if ($used[$j]) {
                        continue;
                    }

                    $shortest = $products[$j];
                    $shortestRows = $shortest->count() + 2;

                    if (($currentRows + $shortestRows) <= 45) {
                        // Pack this product into the current bundle
                        $bundle[] = ['id' => $shortest->first()->produk_id, 'items' => $shortest];
                        $used[$j] = true;
                        $currentRows += $shortestRows;
                    }
                }

                $notaBundles[] = $bundle;
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

                    $sumTitip = 0;
                    $sumLaku = 0;
                    $sumSisaJual = 0;
                    $sumReturn = 0;
                    $sumBayar = 0;

                    // HYBRID MAPPING:
                    // 1. Single Product Nota -> Show ALL active merchants (for completeness)
                    // 2. Multi Product Nota -> Show ONLY titipi merchants (to keep it slim)
                    // HYBRID MAPPING:
                    // 1. Single Product Nota -> Show ALL active merchants (for completeness)
                    // 2. Multi Product Nota -> Show ONLY titipi merchants (to keep it slim)
                    if ($isSingleProductNota) {
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
                        $mappedItems = collect($mappedItems);
                    } else {
                        $mappedItems = $items->sortBy(fn ($p) => $p->pedagang_nama)->map(function ($p) use (&$sumTitip, &$sumLaku, &$sumSisaJual, &$sumReturn, &$sumBayar) {
                            $p_titip = (int) $p->titip;
                            $p_laku = (int) $p->laku;
                            $p_sisa = (int) $p->sisa_jual;
                            // Rumus User: Retur = Titip - Laku - Sisa Jual
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
                    'pembulatan' => null,
                    'tanggal' => $date,
                    'is_first_produk' => $isFirstNotaForProdusen,
                    'sim_result' => $simResult,
                ];
                $isFirstNotaForProdusen = false;
            }
        }

        try {
            NotaBackupService::backup(collect($notads), $date);
        } catch (\Exception $e) {
            // Silently fail if backup fails so it doesn't interrupt printing
            Log::error('Failed to backup nota: '.$e->getMessage());
        }

        // Return iframe-friendly view if requested
        if (request('iframe')) {
            return view('admin.nota.iframe', [
                'notads' => collect($notads),
                'date' => $date,
                'liburPedagangs' => $liburPedagangs,
            ]);
        }
        
        return view('admin.nota.print', [
            'notads' => collect($notads),
            'date' => $date,
            'liburPedagangs' => $liburPedagangs,
        ]);
    }

    public function downloadBackup(Request $request)
    {
        try {
            $file = decrypt($request->query('file'));
        } catch (\Exception $e) {
            abort(400, 'Invalid file token');
        }

        if (! str_starts_with($file, 'nota_logs/')) {
            abort(403, 'Access Denied');
        }

        if (! Storage::disk('local')->exists($file)) {
            abort(404, 'File not found');
        }

        return Storage::disk('local')->download($file);
    }
}
