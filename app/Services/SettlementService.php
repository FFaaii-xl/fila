<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DetailTabungan;
use App\Models\DetailTransaksi;
use App\Models\Kas;
use App\Models\Pembulatan;
use App\Models\Penjualan;
use App\Models\Produsen;
use App\Models\Saldo;
use App\Models\Transaksi;
use App\Services\Pipelines\Transact\ProcessPedagang;
use App\Services\Pipelines\Transact\ProcessProdusen;
use App\Services\Pipelines\Transact\TransactState;
use App\Services\Pipelines\Transact\UpdatePenjualanStatus;
use App\Traits\MerchantFinancialRules;
use Exception;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class SettlementService
{
    use MerchantFinancialRules;

    /**
     * PROCESS 1: Transact (Draft -> Pending)
     * Mengubah draf penjualan menjadi data transaksi siap bayar
     *
     * @param  bool  $requireLock  Jika true, hanya memproses draf yang keterangannya 'Locked'
     */
    public function transact(string $date, bool $requireLock = true)
    {
        return DB::transaction(function () use ($date, $requireLock) {
            $start = $date.' 00:00:00';
            $end = $date.' 23:59:59';

            $query = Penjualan::where('status', 'Draft')
                ->whereBetween('tanggal', [$start, $end]);

            if ($requireLock) {
                $query->where('keterangan', 'Locked');
            }

            $drafts = $query->with('produk')->get();

            if ($drafts->isEmpty()) {
                throw new Exception('Tidak ada draf '.($requireLock ? 'terkunci ' : '')."untuk ditransaksikan pada tanggal {$date}.");
            }

            $batch = (string) $this->getNextBatch();

            // Use the new Pipeline Architecture
            $state = new TransactState($date, $start, $end, $requireLock, $drafts, $batch);

            app(Pipeline::class)
                ->send($state)
                ->through([
                    ProcessPedagang::class,
                    ProcessProdusen::class,
                    UpdatePenjualanStatus::class,
                ])
                ->thenReturn();
        });
    }

    /**
     * Settle Transactions (Pay)
     * Changes Pending to Ok and applies financial logic
     */
    public function pay(string $date): void
    {
        // [SAFETY_SHIELD] 1. Concurrency Lock: Prevent parallel execution for the same date
        $lock = Cache::lock("settlement_pay_{$date}", 60);

        if (! $lock->get()) {
            throw new Exception("Proses pembayaran untuk tanggal {$date} sedang berlangsung di sesi lain. Silakan tunggu sebentar.");
        }

        try {
            DB::transaction(function () use ($date) {
                $transaksis = Transaksi::where('status', 'Pending')
                    ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
                    ->get();

                if ($transaksis->isEmpty()) {
                    // Check if already paid
                    $alreadyOk = Transaksi::where('status', 'Ok')
                        ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
                        ->exists();

                    if ($alreadyOk) {
                        throw new Exception("Transaksi untuk tanggal {$date} sudah berstatus 'Ok'. Lakukan Rollback jika ingin memproses ulang.");
                    }

                    throw new Exception("Tidak ada data transaksi 'Pending' untuk tanggal {$date}.");
                }

                $globalSaldo = Saldo::orderBy('id', 'desc')->first();

                foreach ($transaksis as $transaksi) {
                    // [SAFETY_SHIELD] 2. Owner Integrity Check
                    if (! $transaksi->owner) {
                        continue; // Skip or Log orphaned transactions
                    }

                    if ($transaksi->owner_type === 'Produsen') {
                        $this->processProdusenSettlement($transaksi, $globalSaldo);
                    } else {
                        $this->processPedagangSettlement($transaksi);
                    }
                }

                // Finalize status
                Transaksi::where('status', 'Pending')
                    ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
                    ->update(['status' => 'Ok']);

                Penjualan::where('status', 'Pending')
                    ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
                    ->update(['status' => 'Ok']);

                // Auto-insert ke tabel kas (total kas produsen + pedagang)
                $totalKas = Transaksi::where('status', 'Ok')
                    ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
                    ->sum('kas');

                if ($totalKas > 0) {
                    Kas::updateOrCreate(
                        ['tanggal' => $date, 'nama' => 'kas'],
                        [
                            'jumlah' => $totalKas,
                            'keterangan' => 'Settlement '.$date,
                            'status' => 'Ok',
                            'deleted_at' => null,
                        ]
                    );
                }

                // Trigger summary refresh
                app(SalesService::class)->refreshSummary($date);

                Cache::forget("ops_status_{$date}");
            });
        } finally {
            $lock->release();
        }
    }

    /**
     * Undo Settlement (Rollback)
     */
    /**
     * Preview settlement without persisting to DB
     * Used for Live Hub and Nota Preview
     */
    public function previewProdusenSettlement(float $originalAmount, float $kemarinStore, float $tabunganRate, int $transaksiId, ?string $keteranganStore = null, ?float $lainLainOverride = null): array
    {
        $settings = app(SettingsService::class);

        $kemarin = 0;
        $lainCarryOver = 0;

        // Pisahkan Kemarin (Rounding) vs Sisa <10.000 (Lain-lain)
        if ($kemarinStore !== 0) {
            if ($keteranganStore && str_contains($keteranganStore, 'Sisa')) {
                $lainCarryOver = $kemarinStore;
            } else {
                $kemarin = $kemarinStore;
            }
        }

        if ($lainLainOverride !== null) {
            $lainLain = $lainLainOverride;
        } else {
            $lainLain = (float) (DetailTransaksi::where('transaksi_id', $transaksiId)->sum('jumlah') ?? 0);
        }
        $lainLain += $lainCarryOver;

        $balance = $originalAmount;

        // URUTAN 2: KAS
        // Kas hanya dihitung jika omset >= 50.000 (batas minimum untuk aplikasi kas)
        // Formula: flat fee + sisa bagi 1000
        $flatKas = (float) $settings->get('kas_produsen_flat', 1500);
        $kasMinimumOmset = (float) $settings->get('kas_threshold', 50000); // Minimum omset untuk trigger kas calculation
        $totalKas = 0;
        if ($balance >= $kasMinimumOmset) {
            $receh = 0;
            if ($balance > $flatKas) {
                $receh = fmod(($balance - $flatKas), 1000);
            }
            $totalKas = $flatKas + $receh;
        }
        $balance -= $totalKas;

        // URUTAN 3: KEMARIN & LAIN2 (FULL DEDUCTION)
        $applied_kemarin = $kemarin;
        $applied_lain = $lainLain;

        $balance += $applied_kemarin;
        $balance += $applied_lain;

        // URUTAN 4: TABUNGAN
        $tabunganApplied = 0;
        if ($tabunganRate > 0 && ($balance - $tabunganRate) >= 0) {
            $tabunganApplied = $tabunganRate;
            $balance -= $tabunganApplied;
        }

        // URUTAN 5: <10.000 CARRY OVER / HUTANG
        $carry_over_besok = 0;

        // Threshold carry-over: saldo <= 10.000 di-carry ke besok
        // Config: config('citroroso.transaction_threshold')
        $threshold = (int) config('citroroso.transaction_threshold', 10000);
        if ($balance > 0 && $balance <= $threshold) {
            $carry_over_besok += $balance;
            $balance = 0;
        } elseif ($balance < 0) {
            // Jika minus, pindahkan sebagai carry over (hutang), set balance (Uang Hari Ini) = 0
            $carry_over_besok += $balance;
            $balance = 0;
        }

        // URUTAN 6: PEMBULATAN
        $pembulatan = 0;
        if ($balance > 0) {
            $rem = (int) $balance % 1000;
            if (abs($rem) === 100) {
                $balance -= $rem;
            } elseif (abs($rem) === 900) {
                $balance += ($rem > 0 ? 100 : -100);
            }

            if ($balance >= 50000) {
                $pembulatanService = app(PembulatanService::class);
                $pembulatan = $pembulatanService->calculateRoundingLegacy((int) $balance);
            }
            $balance += $pembulatan;
        }

        $payout = max(0, $balance);
        $carryOver = ($carry_over_besok !== 0) ? $carry_over_besok : -$pembulatan;

        return [
            'kas' => $totalKas,
            'kemarin' => $applied_kemarin,
            'lain' => $applied_lain,
            'tabungan' => $tabunganApplied,
            'pembulatan_adjustment' => $pembulatan,
            'payout' => $payout,
            'carry_over' => $carryOver,
        ];
    }

    public function rollback(string $date): void
    {
        $lock = Cache::lock("settlement_rollback_{$date}", 60);

        if (! $lock->get()) {
            throw new Exception("Proses rollback untuk tanggal {$date} sedang berlangsung. Silakan tunggu.");
        }

        try {
            DB::transaction(function () use ($date) {
                $transaksis = Transaksi::where('status', 'Ok')
                    ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
                    ->get();

                foreach ($transaksis as $transaksi) {
                    $owner = $transaksi->owner;

                    // 1. Revert Tabungan
                    $detailTabungans = DetailTabungan::where('transaksi_id', $transaksi->id)->orderBy('id', 'asc')->get();
                    if ($detailTabungans->isNotEmpty()) {
                        // Restore tabungan ke saldo awal dari record paling pertama sebelum duplikasi terjadi
                        if ($owner) {
                            $owner->tabungan = $detailTabungans->first()->awal;
                            $owner->save();
                        }
                        // Hapus SEMUA record tabungan untuk transaksi ini agar bersih (tidak menyisakan duplikat)
                        DetailTabungan::where('transaksi_id', $transaksi->id)->delete();
                    }

                    // 2. Revert Pembulatan
                    if ($transaksi->owner_type === 'Produsen') {
                        $pembulatan = $owner->pembulatan;
                        if ($pembulatan) {
                            $kemarinValue = $transaksi->kemarin;
                            $restoredKeterangan = null; // Default: clear description

                            // [TRANSPARENCY_SHIELD] Selalu cari dan hapus record carry over jika ada, 
                            // karena sekarang kemarin (rounding) dan carry terpisah
                            $carryOverRecord = DetailTransaksi::where('transaksi_id', $transaksi->id)
                                ->where('keterangan', 'like', 'Sisa %')
                                ->first();
                                
                            if ($carryOverRecord) {
                                // Kembalikan nilai ke legacy store untuk state
                                $kemarinValue = $carryOverRecord->jumlah;
                                $restoredKeterangan = $carryOverRecord->keterangan;
                                $carryOverRecord->delete(); // Otomatis terhapus, sisa manual adjustment admin
                            }

                            $pembulatan->jumlah = $kemarinValue;
                            $pembulatan->keterangan = $restoredKeterangan;
                            $pembulatan->save();
                        }

                        app(BackupFooterService::class)->record(
                            $transaksi,
                            (int) ($transaksi->pembulatan ?? 0),
                            (int) ($transaksi->kemarin ?? 0)
                        );
                    }

                    // 3. Mark back to Pending and RESET modal to raw state
                    $transaksi->status = 'Pending';

                    // Recalculate original modal to prevent cumulative balance errors
                    $penjualanIds = DB::table('penjualan_transaksi')
                        ->where('transaksi_id', $transaksi->id)
                        ->pluck('penjualan_id');

                    $items = Penjualan::whereIn('id', $penjualanIds)->get();
                    $transaksi->jumlah = $items->sum(fn ($i) => $i->laku * $i->harga_beli);

                    Transaksi::whereKey($transaksi->id)->update([
                        'status' => $transaksi->status,
                        'jumlah' => $transaksi->jumlah,
                    ]);
                }

                Penjualan::where('status', 'Ok')
                    ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"])
                    ->update(['status' => 'Pending']);

                // Soft-delete kas entry untuk tanggal ini
                Kas::where('tanggal', $date)
                    ->where('nama', 'kas')
                    ->whereNull('deleted_at')
                    ->update(['deleted_at' => now()]);

                // Refresh summary
                app(SalesService::class)->refreshSummary($date);

                Cache::forget("ops_status_{$date}");
            });
        } finally {
            $lock->release();
        }
    }

    /**
     * Clear Drafts/Pending for a date
     */
    public function reset(string $date): void
    {
        DB::transaction(function () use ($date) {
            $query = Penjualan::whereIn('status', ['Draft', 'Pending'])
                ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"]);

            $penjualans = $query->get();

            if ($penjualans->isNotEmpty()) {
                $penjualanIds = $penjualans->pluck('id');

                // 1. Revert Pending: Delete Transaksi Link & Records
                $transaksiIds = DB::table('penjualan_transaksi')
                    ->whereIn('penjualan_id', $penjualanIds)
                    ->pluck('transaksi_id');

                DB::table('penjualan_transaksi')->whereIn('penjualan_id', $penjualanIds)->delete();

                if ($transaksiIds->isNotEmpty()) {
                    // Only delete Transaksis that are still in Pending/Draft (safety check)
                    DetailTransaksi::whereIn('transaksi_id', $transaksiIds)->delete();
                    Transaksi::whereIn('id', $transaksiIds)->whereIn('status', ['Draft', 'Pending'])->delete();
                }

                // 2. Kembalikan status ke Draft, tapi PERTAHANKAN keterangan (agar tetap locked jika sebelumnya locked)
                DB::table('penjualan')
                    ->whereIn('id', $penjualanIds)
                    ->update([
                        'status' => 'Draft',
                        'updated_at' => now('Asia/Jakarta'),
                    ]);
            }

            Cache::forget("ops_status_{$date}");
            Cache::forget("dashboard_hub_{$date}");

            // Perbarui sales summaries agar UI langsung sinkron
            app(SalesService::class)->refreshSummary($date);
        });
    }

    /**
     * Delete Drafts/Pending for a specific merchant or all
     */
    public function deleteMerchantData(string $date, ?int $pedagangId = null): void
    {
        DB::transaction(function () use ($date, $pedagangId) {
            $query = Penjualan::whereIn('status', ['Draft', 'Pending'])
                ->whereBetween('tanggal', ["$date 00:00:00", "$date 23:59:59"]);

            if ($pedagangId) {
                $query->where('pedagang_id', $pedagangId);
            }

            $penjualans = $query->get();

            if ($penjualans->isNotEmpty()) {
                $penjualanIds = $penjualans->pluck('id');

                // 1. Delete Transaksi Link & Records
                $transaksiIds = DB::table('penjualan_transaksi')
                    ->whereIn('penjualan_id', $penjualanIds)
                    ->pluck('transaksi_id');

                DB::table('penjualan_transaksi')->whereIn('penjualan_id', $penjualanIds)->delete();

                if ($transaksiIds->isNotEmpty()) {
                    Transaksi::whereIn('id', $transaksiIds)->whereIn('status', ['Draft', 'Pending'])->delete();
                }

                // 2. Hard-Delete Data Penjualan (Hapus Draf secara permanen)
                DB::table('penjualan')
                    ->whereIn('id', $penjualanIds)
                    ->delete();
            }

            Cache::forget("ops_status_{$date}");
            Cache::forget("dashboard_hub_{$date}");

            // Perbarui sales summaries agar UI langsung sinkron
            app(SalesService::class)->refreshSummary($date);
        });
    }

    private function processProdusenSettlement(Transaksi $transaksi, ?Saldo $globalSaldo): void
    {
        $settings = app(SettingsService::class);
        $originalAmount = (float) $transaksi->jumlah;
        $produsen = $transaksi->owner;

        $pembulatanStore = $produsen->pembulatan ?: Pembulatan::create(['produsen_id' => $produsen->id, 'pembulatan_ke' => 5000]);
        $kemarinStoreLegacy = (float) $pembulatanStore->jumlah;
        $keteranganStoreLegacy = $pembulatanStore->keterangan;

        // [FIX] Ambil dari $lastTransaksi (sama seperti di simulasi) agar tidak bergantung pada legacy table yang hanya punya 1 slot
        $lastTransaksi = Transaksi::where('owner_type', 'Produsen')
            ->where('owner_id', $produsen->id)
            ->where('status', 'Ok')
            ->where('id', '<', $transaksi->id)
            ->orderBy('tanggal', 'desc')
            ->first();

        $kemarin = 0;
        $carryLast = 0;
        $lastSnapshot = null;

        if ($lastTransaksi) {
            // Kemarin = negate dari transaksi.pembulatan (ini akurat karena tidak tertimpa sisa carry)
            $kemarin = -((float) ($lastTransaksi->pembulatan ?? 0));
            
            if (!empty($lastTransaksi->keterangan)) {
                $lastSnapshot = json_decode((string)$lastTransaksi->keterangan, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($lastSnapshot['carry'])) {
                    $carryLast = (float) $lastSnapshot['carry'];
                }
            }
        } elseif ($kemarinStoreLegacy !== 0.0) {
            // Fallback murni ke legacy jika tidak ada $lastTransaksi yang OK
            if ($keteranganStoreLegacy && str_contains($keteranganStoreLegacy, 'Sisa')) {
                $carryLast = $kemarinStoreLegacy;
            } else {
                $kemarin = $kemarinStoreLegacy;
            }
        }

        // Proses Carry Over (Sisa) menjadi DetailTransaksi (Lain-Lain)
        if ($carryLast !== 0.0) {
            $dateStr = $lastTransaksi ? \Carbon\Carbon::parse($lastTransaksi->tanggal)->format('d/m') : \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m');
            $keteranganLain = $keteranganStoreLegacy; // Default
            
            if ($lastSnapshot) {
                // Bangun keterangan detail dari snapshot
                $parts = [];
                if (isset($lastSnapshot['bruto'])) $parts[] = 'Omset: '.number_format($lastSnapshot['bruto'], 0, ',', '.');
                if (isset($lastSnapshot['kas'])) $parts[] = 'Kas: '.number_format($lastSnapshot['kas'], 0, ',', '.');
                if (isset($lastSnapshot['tabungan'])) $parts[] = 'Tabungan: '.number_format($lastSnapshot['tabungan'], 0, ',', '.');
                if (isset($lastSnapshot['payout'])) $parts[] = 'Uang Hari Ini: '.number_format($lastSnapshot['payout'], 0, ',', '.');
                if (isset($lastSnapshot['carry'])) $parts[] = 'Sisa Carry: '.number_format($lastSnapshot['carry'], 0, ',', '.');
                $keteranganLain = "Sisa Tgl " . $dateStr . ": [" . implode('; ', $parts) . "]";
            }
            
            $existingCO = DetailTransaksi::where('transaksi_id', $transaksi->id)
                ->where('keterangan', $keteranganLain)
                ->first();
                
            if (! $existingCO) {
                DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'jumlah' => $carryLast,
                    'keterangan' => $keteranganLain,
                ]);
            }
            
            // Legacy reset (biarkan apa adanya agar tidak merusak flow lama/rollback)
            if ($keteranganStoreLegacy && str_contains($keteranganStoreLegacy, 'Sisa')) {
                $pembulatanStore->jumlah = 0;
                $pembulatanStore->keterangan = null;
                $pembulatanStore->save();
            }
        } else {
            // Legacy reset (biarkan apa adanya agar tidak merusak flow lama/rollback)
            if ($kemarinStoreLegacy !== 0.0 && (!$keteranganStoreLegacy || !str_contains($keteranganStoreLegacy, 'Sisa'))) {
                $pembulatanStore->jumlah = 0;
                $pembulatanStore->keterangan = null;
                $pembulatanStore->save();
            }
        }

        $lainLain = (float) (DetailTransaksi::where('transaksi_id', $transaksi->id)->sum('jumlah') ?? 0);

        // URUTAN 1: BAYAR
        $balance = $originalAmount;

        // URUTAN 2: KAS (JIKA DIATAS 50.000)
        // Kas hanya dihitung jika omset >= 50.000 (batas minimum untuk aplikasi kas)
        // Formula: flat fee + sisa bagi 1000
        $flatKas = (float) $settings->get('kas_produsen_flat', 1500);
        $kasMinimumOmset = (float) $settings->get('kas_threshold', 50000); // Minimum omset untuk trigger kas calculation
        $totalKas = 0;
        if ($balance >= $kasMinimumOmset) {
            $receh = 0;
            if ($balance > $flatKas) {
                $receh = fmod(($balance - $flatKas), 1000);
            }
            $totalKas = $flatKas + $receh;
        }
        $transaksi->kas = $totalKas;
        $balance -= $totalKas;

        // URUTAN 3: KEMARIN & LAIN2 (FULL DEDUCTION)
        $applied_kemarin = $kemarin;
        $applied_lain = $lainLain;

        $balance += $applied_kemarin;
        $balance += $applied_lain;

        $transaksi->kemarin = $applied_kemarin;

        // URUTAN 4: TABUNGAN JIKA CUKUP
        $tabunganPotong = 0;
        $potong = (float) ($produsen->tabungan_rate ?? 0);
        if ($potong > 0 && ($balance - $potong) >= 0) {
            $this->recordTabungan($produsen, $potong, $transaksi->id, $transaksi->tanggal, true);
            $tabunganPotong = $potong;
            $balance -= $tabunganPotong;
        }

        // URUTAN 5: <10.000 CARRY OVER / HUTANG
        $carry_over_besok = 0;
        $desc_parts = [];

        // Threshold carry-over: saldo <= 10.000 di-carry ke besok
        // Config: config('citroroso.transaction_threshold')
        $threshold = (int) config('citroroso.transaction_threshold', 10000);
        if ($balance > 0 && $balance <= $threshold) {
            // Carry over positif (≤10.000)
            $carry_over_besok += $balance;

            $productNames = DB::table('penjualan_transaksi')
                ->join('penjualan', 'penjualan.id', '=', 'penjualan_transaksi.penjualan_id')
                ->join('produk', 'produk.id', '=', 'penjualan.produk_id')
                ->where('penjualan_transaksi.transaksi_id', $transaksi->id)
                ->distinct()
                ->pluck('produk.nama')
                ->toArray();
            $prodStr = count($productNames) > 2 ? ($productNames[0].', '.$productNames[1].'...') : implode(', ', $productNames);

            $desc_parts[] = "Omset $prodStr (".number_format($originalAmount, 0, ',', '.').')';
            if ($applied_lain !== 0) {
                $desc_parts[] = 'Adj ('.number_format($applied_lain, 0, ',', '.').')';
            }
            if ($applied_kemarin !== 0) {
                $desc_parts[] = 'Kemarin ('.number_format($applied_kemarin, 0, ',', '.').')';
            }
            if ($tabunganPotong !== 0) {
                $desc_parts[] = 'Tabungan ('.number_format(-$tabunganPotong, 0, ',', '.').')';
            }

            $balance = 0; // Swept to carry over
        } elseif ($balance < 0) {
            // Carry over negatif (hutang) - Produsen berutang ke Admin
            $carry_over_besok += $balance;
            
            $productNames = DB::table('penjualan_transaksi')
                ->join('penjualan', 'penjualan.id', '=', 'penjualan_transaksi.penjualan_id')
                ->join('produk', 'produk.id', '=', 'penjualan.produk_id')
                ->where('penjualan_transaksi.transaksi_id', $transaksi->id)
                ->distinct()
                ->pluck('produk.nama')
                ->toArray();
            $prodStr = count($productNames) > 2 ? ($productNames[0].', '.$productNames[1].'...') : implode(', ', $productNames);

            $desc_parts[] = "Hutang $prodStr (".number_format(abs($balance), 0, ',', '.').')';
            if ($applied_lain !== 0) {
                $desc_parts[] = 'Adj ('.number_format($applied_lain, 0, ',', '.').')';
            }
            if ($applied_kemarin !== 0) {
                $desc_parts[] = 'Kemarin ('.number_format($applied_kemarin, 0, ',', '.').')';
            }
            if ($tabunganPotong !== 0) {
                $desc_parts[] = 'Tabungan ('.number_format(-$tabunganPotong, 0, ',', '.').')';
            }

            $balance = 0; // Reset balance after carry over
        }

        if ($carry_over_besok !== 0) {
            $dateStr = \Carbon\Carbon::parse($transaksi->tanggal)->format('d/m');
            $pembulatanStore->jumlah = $carry_over_besok;
            $pembulatanStore->keterangan = mb_strimwidth("Sisa $dateStr: [".implode('; ', $desc_parts).']', 0, 250, '...');
        }

        // URUTAN 6: PEMBULATAN
        $pembulatan = 0;
        if ($balance > 0) {
            $rem = (int) $balance % 1000;
            if (abs($rem) === 100) {
                $balance -= $rem;
            } elseif (abs($rem) === 900) {
                $balance += ($rem > 0 ? 100 : -100);
            }

            if ($balance >= 50000) {
                $pembulatanService = app(PembulatanService::class);
                $pembulatan = $pembulatanService->calculateRoundingLegacy((int) $balance);
            }
            $balance += $pembulatan;
        }

        $transaksi->jumlah = $balance;
        $transaksi->pembulatan = $pembulatan;

        // [NUCLEAR_SNAPSHOT_SHIELD] Lock the calculations permanently in JSON
        $transaksi->keterangan = json_encode([
            'bruto' => $originalAmount,
            'kas' => $totalKas,
            'kemarin' => $applied_kemarin,
            'lain' => $applied_lain,
            'tabungan' => $tabunganPotong,
            'pembulatan' => $pembulatan,
            'carry' => $carry_over_besok,
            'payout' => max(0, $balance), // Guard: prevent negative payout
            'v' => '3.3', // Version 3.3 - all values stored
        ]);

        $transaksi->save();

        // Pembulatan dibawa ke besok (Hanya jika tidak ada Sisa <10.000 / Hutang)
        if ($carry_over_besok === 0) {
            $pembulatanStore->jumlah = -$pembulatan;
            $pembulatanStore->keterangan = null;
        }

        $pembulatanStore->save();

        app(BackupFooterService::class)->record($transaksi, (int) $pembulatan, (int) $carry_over_besok);
    }

    private function processPedagangSettlement(Transaksi $transaksi): void
    {
        $pedagang = $transaksi->owner;
        $bayar = (float) $transaksi->jumlah; // Modal

        // 1. Kas (Tiered Lookup via Trait)
        $kas = $this->getTieredMerchantKas($bayar);

        // 2. Tabungan (Fixed deduction/saving)
        $tabungan = (float) ($pedagang->tabungan_rate ?? 0);
        if ($tabungan > 0 && $pedagang) {
            $this->recordTabungan($pedagang, $tabungan, $transaksi->id, \Carbon\Carbon::parse($transaksi->tanggal)->format('Y-m-d'), true);
        }

        // 3. Iuran (via Trait)
        // Count unique products
        $productCount = DB::table('penjualan_transaksi')
            ->join('penjualan', 'penjualan.id', '=', 'penjualan_transaksi.penjualan_id')
            ->where('penjualan_transaksi.transaksi_id', $transaksi->id)
            ->distinct('penjualan.produk_id')
            ->count('penjualan.produk_id');

        $proup = $this->calculateMerchantProup($bayar, $productCount, $pedagang->nama);

        // 4. Lain-lain (Penyesuaian Manual)
        $lainLain = (float) (DetailTransaksi::where('transaksi_id', $transaksi->id)->sum('jumlah') ?? 0);

        $transaksi->kas = $kas; // Only Tiered Kas
        $transaksi->jumlah = $bayar + $kas + $tabungan + $proup + $lainLain;

        // [NUCLEAR_SNAPSHOT_SHIELD] Lock the calculations permanently in JSON
        // FIX: payout = bruto - kas - tabungan - proup - lain (Uang bersih pedagang)
        $transaksi->keterangan = json_encode([
            'bruto' => $bayar,
            'kas' => $kas,
            'tabungan' => $tabungan,
            'proup' => $proup,
            'lain' => $lainLain,
            'payout' => $bayar - $kas - $tabungan - $proup - $lainLain,
            'v' => '3.3',
        ]);

        $transaksi->save();
    }

    private function recordTabungan($owner, float $amount, int $transaksiId, $date, bool $isAdding = false): void
    {
        if ($date instanceof \DateTimeInterface) {
            $date = $date->format('Y-m-d');
        }

        // [SAFETY_GUARD] Prevent duplicate tabungan records for the same transaction
        // NOTE: Use whereNull('deleted_at') because rollback uses SoftDeletes
        $exists = DB::table('detail_tabungan')
            ->where('transaksi_id', $transaksiId)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return;
        }

        $awal = (float) ($owner->tabungan ?? 0);
        $akhir = $isAdding ? ($awal + $amount) : ($awal - $amount);

        DB::table('detail_tabungan')->insert([
            'owner_type' => ($owner instanceof Produsen) ? 'Produsen' : 'Pedagang',
            'owner_id' => $owner->id,
            'transaksi_id' => $transaksiId,
            'tanggal' => $date, // Aligned with market date
            'awal' => $awal,
            'jumlah' => $isAdding ? $amount : -$amount,
            'akhir' => $akhir,
            'keterangan' => 'Tabungan dari transaksi harian',
            'created_at' => now('Asia/Jakarta'),
            'updated_at' => now('Asia/Jakarta'),
        ]);

        // Update balance in owner table
        $owner->tabungan = $akhir;
        $owner->save();
    }

    private function getNextBatch(): int
    {
        $lastBatch = DB::table('penjualan_transaksi')->max('batch');

        return (int) (($lastBatch ?? 0) + 1);
    }
}
