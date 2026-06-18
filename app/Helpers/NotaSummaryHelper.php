<?php

namespace App\Helpers;

use App\Models\DetailTransaksi;
use App\Models\DetailTabungan;

/**
 * Centralized Nota Summary Calculator
 * DRY: Satu logic untuk semua sumber data (SNAPSHOT, LEGACY, SIMULASI)
 */
class NotaSummaryHelper
{
    /**
     * Hitung summary nota dari data mentah
     * 
     * @param float $bayar Omset bruto (Harga Beli)
     * @param float $kas Kas (jika 0, akan dihitung dengan threshold)
     * @param float $kemarin Nilai kemarin (pembulatan carry over)
     * @param float $lain Nilai lain-lain (adjustment)
     * @param float $tabungan Tabungan yang diapply
     * @param float $pembulatan Pembulatan adjustment
     * @param int|null $transaksiId Optional, untuk legacy fallback
     * @param bool $recalculateKas Jika true, kas dihitung ulang dari formula
     * @return array
     */
    public static function calculate(
        float $bayar,
        float $kas,
        float $kemarin,
        float $lain,
        float $tabungan,
        float $pembulatan,
        ?int $transaksiId = null,
        bool $recalculateKas = false
    ): array {
        // [KAS_THRESHOLD] Baca dari config system
        $kasFlat = (float) config('citroroso.kas_produsen_flat', 1500);
        $kasThreshold = (float) config('citroroso.kas_threshold', 50000);
        $transactionThreshold = (int) config('citroroso.transaction_threshold', 10000);
        $denomination = (float) config('citroroso.kas_produsen.denomination', 1000);
        
        // Hitung kas dengan threshold dari config
        $effectiveKas = $kas;
        if ($recalculateKas || $kas === 0.0) {
            $rawKas = $bayar >= $kasThreshold 
                ? $kasFlat + (fmod($bayar - $kasFlat, $denomination)) 
                : 0;
            $effectiveKas = round($rawKas);
        }
        
        // [LAIN_CARRY_OVER] Logika settlement
        // Balance = Bayar - Kas
        $balance = $bayar - $effectiveKas;
        
        // Apply kemarin positif ke balance dulu
        $appliedKemarin = $kemarin;
        if ($kemarin > 0) {
            $balance += $kemarin;
        }
        
        // Apply lain positif ke balance
        $appliedLain = $lain;
        if ($lain > 0) {
            $balance += $lain;
        }
        
        // [CRITICAL] Handle Hutang Lain (lain negatif)
        // Jika adjustment/lain negatif dan balance tidak cukup, maka:
        // - Applied Lain = -balance (sebesar yang bisa diapply)
        // - Sisa hutang di-carry ke besok
        $sisaHutangLain = 0;
        if ($lain < 0) {
            $amount = abs($lain);
            if ($balance >= $amount) {
                $balance -= $amount;
                $appliedLain = $lain;
            } else {
                $appliedLain = -$balance;
                $sisaHutangLain = $amount - $balance;
                $balance = 0;
            }
        }
        
        // [HUTANG KEMARIN] Handle kemarin negatif juga
        $sisaHutangKemarin = 0;
        if ($kemarin < 0) {
            $amount = abs($kemarin);
            if ($balance >= $amount) {
                $balance -= $amount;
                $appliedKemarin = $kemarin;
            } else {
                $appliedKemarin = -$balance;
                $sisaHutangKemarin = $amount - $balance;
                $balance = 0;
            }
        }
        
        // Apply tabungan
        $effectiveTabungan = min($tabungan, $balance);
        $balance -= $effectiveTabungan;
        
        // Hitung payout = balance + pembulatan
        $payout = max(0, $balance + $pembulatan);
        
        // Total carry over besok (hutang kemarin + hutang lain + sisa <=threshold)
        $carryOverBesok = 0;
        if ($sisaHutangKemarin > 0) {
            $carryOverBesok -= $sisaHutangKemarin;
        }
        if ($sisaHutangLain > 0) {
            $carryOverBesok -= $sisaHutangLain;
        }
        // Threshold carry-over: saldo <= threshold di-carry ke besok
        if ($balance > 0 && $balance <= $transactionThreshold) {
            $carryOverBesok += $balance;
            $balance = 0;
            $payout = 0;
        }
        
        return [
            'bayar' => $bayar,
            'kas' => $effectiveKas,
            'kemarin' => $appliedKemarin,
            'lain' => $appliedLain,
            'tabungan' => $effectiveTabungan,
            'pembulatan' => $pembulatan,
            'payout' => $payout,
            'sisa_hutang_kemarin' => $sisaHutangKemarin,
            'sisa_hutang_lain' => $sisaHutangLain,
            'carry_over_besok' => $carryOverBesok,
        ];
    }
    
    /**
     * Get summary dari SNAPSHOT data
     * kemarin = negatif dari snapshot.kemarin (carry over dari pembulatan.jumlah)
     */
    public static function fromSnapshot(array $snapshot, ?int $transaksiId = null): array
    {
        // Kemarin = nilai kemarin dari snapshot (sudah ada tanda minus/plus yang benar)
        $kemarin = (float) ($snapshot['kemarin'] ?? 0);
        
        return self::calculate(
            bayar: (float) ($snapshot['bruto'] ?? 0),
            kas: 0, // Recalculate dari formula
            kemarin: $kemarin,
            lain: (float) ($snapshot['lain'] ?? 0),
            tabungan: (float) ($snapshot['tabungan'] ?? 0),
            pembulatan: (float) ($snapshot['pembulatan'] ?? $snapshot['rounding'] ?? 0),
            transaksiId: $transaksiId,
            recalculateKas: true
        );
    }
    
    /**
     * Get summary dari LEGACY (transaksi DB)
     */
    public static function fromLegacy(float $bayar, object $transaksi): array
    {
        $lain = (float) \App\Models\DetailTransaksi::where('transaksi_id', $transaksi->id)->sum('jumlah');
        $tabunganRecord = \App\Models\DetailTabungan::where('transaksi_id', $transaksi->id)->first();
        $tabungan = $tabunganRecord ? abs((float) $tabunganRecord->jumlah) : 0;
        
        // Kemarin = nilai dari transaksi.kemarin
        $kemarin = (float) ($transaksi->kemarin ?? 0);
        
        return self::calculate(
            bayar: $bayar,
            kas: (float) ($transaksi->kas ?? 0),
            kemarin: $kemarin,
            lain: $lain,
            tabungan: $tabungan,
            pembulatan: (float) ($transaksi->pembulatan ?? 0),
            transaksiId: $transaksi->id,
            recalculateKas: false // Kas dari DB
        );
    }
}
