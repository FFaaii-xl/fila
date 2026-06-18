<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailTransaksi;
use App\Models\Pedagang;
use App\Models\Produsen;
use App\Models\Transaksi;
use App\Services\SalesService;
use App\Services\SettingsService;
use App\Services\SettlementService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use MoonShine\Support\Enums\ToastType;

final class DashboardController extends Controller
{
    protected SettlementService $settlement;

    protected SalesService $salesService;

    public function __construct(SettlementService $settlement, SalesService $salesService)
    {
        $this->settlement = $settlement;
        $this->salesService = $salesService;
    }

    /**
     * Get real-time metrics for the hub
     */
    public function getMetrics(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $start = $date.' 00:00:00';
        $end = $date.' 23:59:59';

        // Float Saldo: Total saldo semua pedagang (bukan hanya yang aktif)
        $floatSaldo = (float) DB::table('saldo')
            ->where('owner_type', 'Pedagang')
            ->sum('jumlah');

        // 1. Kebutuhan dari Transaksi yang sudah OK (Final) - Hanya untuk yang saldo > 0
        $kebutuhanOk = DB::table('transaksi as t')
            ->join('saldo as s', function ($join) {
                $join->on('t.owner_id', '=', 's.owner_id')
                    ->where('s.owner_type', '=', 'Pedagang');
            })
            ->where('t.owner_type', 'Pedagang')
            ->where(DB::raw('LOWER(t.status)'), 'ok')
            ->whereNull('t.deleted_at')
            ->where('s.jumlah', '>', 0)
            ->whereBetween('t.tanggal', ["$date 00:00:00", "$date 23:59:59"])
            ->sum('t.jumlah');

        // 2. Kebutuhan Proyeksi dari Penjualan yang masih Draf atau Pending - Hanya untuk yang saldo > 0
        // Kita ambil semua pedagang yang punya data penjualan hari ini tapi belum OK
        $merchantsOk = DB::table('transaksi')
            ->where('owner_type', 'Pedagang')
            ->where(DB::raw('LOWER(status)'), 'ok')
            ->whereNull('deleted_at')
            ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
            ->pluck('owner_id')
            ->toArray();

        $kebutuhanProjected = 0;

        $activeData = DB::table('penjualan as pn')
            ->join('pedagang as p', 'pn.pedagang_id', '=', 'p.id')
            ->join('saldo as s', function ($join) {
                $join->on('p.id', '=', 's.owner_id')
                    ->where('s.owner_type', '=', 'Pedagang');
            })
            ->leftJoin('transaksi as t', function ($join) use ($date) {
                $join->on('p.id', '=', 't.owner_id')
                    ->where('t.owner_type', '=', 'Pedagang')
                    ->whereBetween('t.tanggal', ["$date 00:00:00", "$date 23:59:59"])
                    ->whereNull('t.deleted_at');
            })
            ->whereBetween('pn.tanggal', ["$date 00:00:00", "$date 23:59:59"])
            ->whereNull('pn.deleted_at')
            ->whereNotIn('pn.pedagang_id', $merchantsOk)
            ->where('s.jumlah', '>', 0)
            ->select([
                'p.id',
                'p.nama',
                'p.tabungan_rate',
                DB::raw('SUM(pn.laku * pn.harga_beli) as modal'),
                DB::raw('COUNT(DISTINCT pn.produk_id) as p_count'),
                DB::raw('MAX(t.id) as trx_id'),
            ])
            ->groupBy('p.id', 'p.nama', 'p.tabungan_rate')
            ->get();

        // [NUCLEAR_OPTIMIZATION] Eliminate N+1 loop by pre-fetching all lain-lain
        $trxIds = $activeData->pluck('trx_id')->filter()->toArray();
        $lainLains = [];
        if (! empty($trxIds)) {
            $lainLains = DB::table('detail_transaksi')
                ->whereIn('transaksi_id', $trxIds)
                ->select('transaksi_id', DB::raw('SUM(jumlah) as total'))
                ->groupBy('transaksi_id')
                ->pluck('total', 'transaksi_id')
                ->toArray();
        }

        foreach ($activeData as $d) {
            $modal = (float) $d->modal;
            $proup = $this->salesService->calculateMerchantProup($modal, (int) $d->p_count, (string) $d->nama);
            $kas = $this->salesService->getTieredMerchantKas($modal);
            $tabungan = (float) ($d->tabungan_rate ?? 0);

            // Dapatkan Lain-lain dari HashMap (O(1))
            $lainLain = (float) ($lainLains[$d->trx_id] ?? 0);

            $kebutuhanProjected += ($modal + $proup + $kas + $tabungan + $lainLain);
        }

        $totalKebutuhan = (float) ($kebutuhanOk + $kebutuhanProjected);

        // Reconciliation: Audit Keseimbangan Inbound vs Outbound
        $reconciliation = $this->salesService->getNuclearReconciliation($date);

        return response()->json([
            'saldo' => (float) $floatSaldo,
            'required' => $totalKebutuhan,
            'diff' => (float) ($floatSaldo - $totalKebutuhan),
            'reconciliation' => $reconciliation,
        ]);
    }

    /**
     * Execute Hub Action (Transact, Pay, Rollback, Reset)
     */
    public function executeAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:transact,pay,rollback,reset,unlock,unlock_all,lock,lock_all,delete_merchant,delete_all,delete_all_lain,toggle_public_access',
            'date' => 'required|date',
        ]);

        $action = $request->action;
        $date = $request->date;

        Context::add('admin_id', auth()->id());
        Context::add('action_mode', $action);
        Context::add('action_date', $date);

        try {
            switch ($action) {
                case 'transact':
                    $requireLock = $request->get('require_lock') === 'true' || $request->get('require_lock') === true;
                    $this->settlement->transact($date, $requireLock);
                    $msg = 'Transaksi (Draft -> Pending) berhasil.';
                    break;
                case 'pay':
                    $this->settlement->pay($date);
                    $msg = 'Pencairan (Pay) berhasil diselesaikan.';
                    break;
                case 'rollback':
                    $this->settlement->rollback($date);
                    $msg = 'Rollback berhasil dilakukan.';
                    break;
                case 'reset':
                    $this->settlement->reset($date); // Tidak pakai pedagangId lagi
                    $msg = 'Data berhasil dikembalikan ke status Draf.';
                    break;
                case 'delete_merchant':
                    $this->settlement->deleteMerchantData($date, (int) $request->get('pedagang_id'));
                    $msg = 'Data penjualan pedagang berhasil dihapus.';
                    break;
                case 'delete_all':
                    $this->settlement->deleteMerchantData($date, null);
                    $msg = 'Semua draf/pending hari ini berhasil dihapus.';
                    break;
                case 'delete_all_lain':
                    $transaksiIds = DB::table('transaksi')
                        ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
                        ->whereNull('deleted_at')
                        ->where(DB::raw('LOWER(status)'), '!=', 'ok')
                        ->pluck('id');

                    DetailTransaksi::whereIn('transaksi_id', $transaksiIds)->delete();
                    $msg = 'Semua Nota Tambahan hari ini berhasil dihapus.';
                    break;
                case 'unlock':
                    $pedagangId = (int) $request->get('pedagang_id');
                    DB::table('penjualan')
                        ->where('pedagang_id', $pedagangId)
                        ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
                        ->where('status', 'Draft')
                        ->update(['keterangan' => null]);
                    $msg = 'Laporan berhasil dibuka kembali (Unlocked).';
                    break;
                case 'lock':
                    $pedagangId = (int) $request->get('pedagang_id');
                    DB::table('penjualan')
                        ->where('pedagang_id', $pedagangId)
                        ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
                        ->where('status', 'Draft')
                        ->update(['keterangan' => 'Locked']);
                    $msg = 'Laporan berhasil dikunci (Locked).';
                    break;
                case 'lock_all':
                    DB::table('penjualan')
                        ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
                        ->where('status', 'Draft')
                        ->update(['keterangan' => 'Locked']);
                    $msg = 'Semua laporan berhasil dikunci.';
                    break;
                case 'unlock_all':
                    DB::table('penjualan')
                        ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
                        ->where('status', 'Draft')
                        ->where('keterangan', 'Locked')
                        ->update(['keterangan' => null]);
                    $msg = 'Semua laporan berhasil dibuka kembali.';
                    break;
                case 'toggle_public_access':
                    $settingsService = app(SettingsService::class);
                    $allowedDates = $settingsService->get('public_nota_dates', []);

                    if (in_array($date, $allowedDates, true)) {
                        $allowedDates = array_values(array_diff($allowedDates, [$date]));
                        $msg = "Akses Publik untuk tanggal {$date} telah DICABUT.";
                    } else {
                        $allowedDates[] = $date;
                        $msg = "Akses Publik untuk tanggal {$date} telah DIIZINKAN.";
                    }

                    $settingsService->save(['public_nota_dates' => $allowedDates]);
                    break;
            }

            Cache::forget("dashboard_hub_{$date}");
            toast($msg, ToastType::SUCCESS);

            return response()->json(['status' => 'success', 'message' => $msg]);
        } catch (Exception $e) {
            // [ERROR_FORMAT_FIX] Always include 'status' field for consistent frontend parsing
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add "Lain-lain" (Misc Transaction)
     * [KAIZEN] Poka-Yoke: Cegah penambahan jika status sudah Ok (harus rollback dulu)
     */
    public function addLainLain(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'owner_type' => 'required|in:Pedagang,Produsen',
            'owner_id' => 'required|integer',
            'keterangan' => 'required|string',
            'jumlah' => 'required|numeric',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $this->saveLainLain(
                    $request->date,
                    $request->owner_type,
                    (int) $request->owner_id,
                    $request->keterangan,
                    (float) $request->jumlah
                );
            });

            return response()->json(['status' => 'success', 'message' => 'Lain-lain berhasil ditambahkan ke Nota.']);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Process Bulk "Lain-lain" entries
     */
    public function bulkLainLain(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'data' => 'required|string',
        ]);

        $lines = explode("\n", $request->data);
        $results = ['success' => 0, 'failed' => 0, 'errors' => [], 'failed_data' => []];

        try {
            DB::transaction(function () use ($lines, $request, &$results) {
                foreach ($lines as $index => $line) {
                    $line = trim($line);
                    if (empty($line)) {
                        continue;
                    }

                    // Standard formats: "Name | Desc | Amount" or "Name\tDesc\tAmount" or "Name;Desc;Amount"
                    $parts = preg_split('/[|\t;]+/', $line);

                    if (count($parts) < 3) {
                        $results['failed']++;
                        $results['errors'][] = 'Baris '.($index + 1).": Format salah. Gunakan 'Nama | Ket | Jumlah'.";

                        continue;
                    }

                    $name = trim($parts[0]);
                    $desc = trim($parts[1]);
                    $amount = (float) filter_var(trim($parts[2]), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                    // Resolve Name (Priority: Produsen, then Pedagang)
                    // [HARDENING] Using trim and flexible LIKE to handle trailing spaces in DB
                    $owner = Produsen::where('nama', 'LIKE', trim($name).'%')->first();
                    $type = 'Produsen';

                    if (! $owner) {
                        $owner = Pedagang::where('nama', 'LIKE', trim($name).'%')->first();
                        $type = 'Pedagang';
                    }

                    if (! $owner) {
                        $results['failed']++;
                        $results['errors'][] = 'Baris '.($index + 1).": Nama '{$name}' tidak ditemukan.";
                        $results['failed_data'][] = $line;
                        continue;
                    }

                    try {
                        $this->saveLainLain($request->date, $type, $owner->id, $desc, $amount);
                        $results['success']++;
                    } catch (Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = 'Baris '.($index + 1).': '.$e->getMessage();
                        $results['failed_data'][] = $line;
                    }
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => "Bulk Selesai: {$results['success']} Berhasil, {$results['failed']} Gagal.",
                'details' => $results,
            ]);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Shared logic to save a single Lain-Lain entry with safety checks
     */
    private function saveLainLain(string $date, string $type, int $id, string $desc, float $amount)
    {
        $transaksi = Transaksi::where('owner_type', $type)
            ->where('owner_id', $id)
            ->whereBetween('tanggal', ["{$date} 00:00:00", "{$date} 23:59:59"])
            ->whereNull('deleted_at')
            ->first();

        if (! $transaksi) {
            throw new Exception("Transaksi untuk '{$type}:{$id}' tidak ditemukan. Pastikan sudah TRANSACT DATA.");
        }

        if (strtolower($transaksi->status) === 'ok') {
            throw new Exception("Transaksi '{$type}:{$id}' sudah OK. Rollback dulu.");
        }

        DetailTransaksi::create([
            'transaksi_id' => $transaksi->id,
            'keterangan' => $desc,
            'jumlah' => $amount,
        ]);

        Cache::forget("dashboard_hub_{$date}");
    }

    /**
     * Delete "Lain-lain" (Misc Transaction)
     * [KAIZEN] Poka-Yoke: Cegah penghapusan jika status sudah Ok (harus rollback dulu)
     */
    public function deleteLainLain($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $detail = DetailTransaksi::with('transaksi')->findOrFail($id);

                if ($detail->transaksi && strtolower($detail->transaksi->status) === 'ok') {
                    throw new Exception('Sistem menolak: Transaksi sudah OK (Selesai). Silakan klik ROLLBACK PAY dulu sebelum menghapus Lain-lain.');
                }

                Cache::forget('dashboard_hub_'.($detail->transaksi->tanggal instanceof Carbon ? $detail->transaksi->tanggal->toDateString() : substr((string) $detail->transaksi->tanggal, 0, 10)));
                $detail->delete();
            });

            return response()->json(['status' => 'success', 'message' => 'Data Lain-lain berhasil dihapus.']);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    public function getTables(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        // [SELF-HEALING]: Check for legacy updates before loading
        defer(fn () => $this->salesService->sentinelRepair());

        // [UNIFIED_ACCELERATION]: Using the centralized SalesService raw query engine
        $data = $this->salesService->getDashboardHubData($date);

        return response()->streamJson($data);
    }
}
