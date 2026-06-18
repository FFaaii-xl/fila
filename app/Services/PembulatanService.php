<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Pembulatan;
use App\Models\Transaksi;
use Illuminate\Support\Collection;

final class PembulatanService
{
    protected int $limit;

    public function __construct(int $limit = 30000)
    {
        $this->limit = $limit;
    }

    /**
     * Public helper for simple step-rounding (Legacy rounding style)
     */
    public function roundTo(float $amount, int $step = 5000): float
    {
        if ($amount <= 0) {
            return 0;
        }

        return ceil($amount / $step) * $step;
    }

    /**
     * Handle pembulatan untuk banyak transaksi
     */
    public function handleTransactions(Collection $transaksis)
    {
        foreach ($transaksis as $transaksi) {
            $this->handleTransaction($transaksi);
        }
    }

    /**
     * Handle pembulatan untuk satu transaksi (khusus Produsen)
     */
    public function handleTransaction(Transaksi $transaksi)
    {
        // Hanya untuk Produsen dan memenuhi limit atau threshold
        if ($transaksi->owner_type !== 'Produsen') {
            return;
        }

        $threshold = app(SettingsService::class)->get('transaction_threshold', 10000);

        if ($transaksi->jumlah > $this->limit || $transaksi->jumlah <= $threshold) {

            $pembulatanRecord = $this->getOrCreatePembulatan($transaksi->owner);

            if ($pembulatanRecord->pembulatan_ke > 0) {
                $this->applyRounding($transaksi, $pembulatanRecord);
            }
        }
    }

    /**
     * Ambil atau buat record pembulatan untuk produsen
     */
    protected function getOrCreatePembulatan($owner): Pembulatan
    {
        return $owner->pembulatan ?: Pembulatan::create([
            'produsen_id' => $owner->id,
            'pembulatan_ke' => 5000,
            'jumlah' => 0, // saldo kemarin
        ]);
    }

    /**
     * Terapkan logika pembulatan
     */
    protected function applyRounding(Transaksi $transaksi, Pembulatan $pembulatanRecord)
    {
        // 1. Ambil sisa/kemarin dari tabel pembulatan
        $kemarin = $pembulatanRecord->jumlah ?? 0;

        // 2. Tambahkan saldo kemarin (Opposite Sign Strategy: Addition)
        $transaksi->jumlah += $kemarin;
        $transaksi->kemarin = $kemarin;

        // 3. Hitung pembulatan baru
        $threshold = app(SettingsService::class)->get('transaction_threshold', 10000);

        if ($transaksi->jumlah <= $threshold) {
            // Jika di bawah threshold, semua dipulangkan ke saldo 'pembulatan'
            $adjustment = -$transaksi->jumlah;
        } else {
            // Gunakan logika legacy 'bulatv2'
            $adjustment = $this->calculateRoundingLegacy($transaksi->jumlah);
        }

        // 4. Update transaksi dan record pembulatan dengan proteksi Underflow (Signed vs Unsigned safety)
        $newAmount = $transaksi->jumlah + $adjustment;

        if ($newAmount < 0) {
            $transaksi->pembulatan = abs($newAmount); // Round up to 0 (Addition)
            $pembulatanRecord->jumlah = -$transaksi->pembulatan; // Deduct tomorrow
            $transaksi->jumlah = 0;
        } else {
            $transaksi->jumlah = $newAmount;
            $transaksi->pembulatan = $adjustment;
            $pembulatanRecord->jumlah = -$adjustment; // Inverse sign for tomorrow
        }

        $transaksi->save();
        $pembulatanRecord->save();
    }

    /**
     * Logika 'bulatv2' (Configurable Uang Nota Rules)
     * Aturan digit1, 4, 6, 9.
     */
    public function calculateRoundingLegacy(int $number): int
    {
        $uangNota = config('citroroso.uang_nota', [
            'enabled' => true,
            'threshold_1' => 40000,
            'target_1' => 50000,
            'threshold_2' => 80000,
            'target_2' => 100000,
            'step' => 50000,
            'remainder_threshold' => 35000,
            'remainder_min' => 20000,
        ]);

        if (! ($uangNota['enabled'] ?? true)) {
            return 0;
        }

        $threshold1 = (int) ($uangNota['threshold_1'] ?? 40000);
        $target1 = (int) ($uangNota['target_1'] ?? 50000);
        $threshold2 = (int) ($uangNota['threshold_2'] ?? 80000);
        $target2 = (int) ($uangNota['target_2'] ?? 100000);
        $step = (int) ($uangNota['step'] ?? 50000);
        $remainderThreshold = (int) ($uangNota['remainder_threshold'] ?? 35000);
        $remainderMin = (int) ($uangNota['remainder_min'] ?? 20000);

        // 1. Skip rounding reduction if < threshold_1
        if ($number < $threshold1) {
            return 0; // Keep as is
        }

        if ($number >= $threshold1 && $number < $target1) {
            return $target1 - $number; // Round UP to target_1
        }

        if ($number < $threshold2) {
            return 0; // Keep as is (threshold_1 - threshold_2)
        }

        if ($number >= $threshold2 && $number < $target2) {
            return $target2 - $number; // Round UP to target_2
        }

        // 2. Multi-tier rounding for >= target_2 (Step based)
        $base = floor($number / $step) * $step;
        $remainder = $number % $step;

        if ($remainder <= $remainderMin) {
            $result = $base; // Bulat ke bawah
        } elseif ($remainder >= $remainderThreshold) {
            $result = $base + $step; // Bulat ke atas
        } else {
            // Zona Bebas: Tidak ada pembulatan
            return 0;
        }

        $adjustment = (int) $result - $number;

        return $adjustment;
    }
}
