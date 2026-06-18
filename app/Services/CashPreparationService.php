<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaksi;

class CashPreparationService
{
    protected SettlementService $settlementService;

    public function __construct(SettlementService $settlementService)
    {
        $this->settlementService = $settlementService;
    }

    /**
     * Calculate total cash needed and denomination breakdown
     */
    public function calculate(string $date): array
    {
        $start = $date.' 00:00:00';
        $end = $date.' 23:59:59';

        $transaksis = Transaksi::where('owner_type', 'Produsen')
            ->whereBetween('tanggal', [$start, $end])
            ->whereNull('deleted_at')
            ->with(['owner.pembulatan', 'details'])
            ->get();

        $totalPayout = 0;
        $producerDetails = [];
        $aggregatedBreakdown = [];

        // Initialize aggregated breakdown with 0
        $denominations = [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 200, 100];
        foreach ($denominations as $val) {
            $aggregatedBreakdown[$val] = 0;
        }

        foreach ($transaksis as $transaksi) {
            $payout = 0;
            if ($transaksi->status === 'Ok') {
                $payout = (float) $transaksi->jumlah;
            } else {
                $produsen = $transaksi->owner;
                if ($produsen) {
                    // Pre-calculate lainLain from eager-loaded details to avoid N+1
                    $lainLainSum = (float) ($transaksi->details->sum('jumlah') ?? 0);

                    $preview = $this->settlementService->previewProdusenSettlement(
                        (float) $transaksi->jumlah,
                        (float) ($produsen->pembulatan->jumlah ?? 0),
                        (float) ($produsen->tabungan_rate ?? 0),
                        (int) $transaksi->id,
                        $produsen->pembulatan->keterangan ?? null,
                        $lainLainSum
                    );
                    $payout = $preview['payout'];
                }
            }

            if ($payout > 0) {
                $totalPayout += $payout;

                // Calculate breakdown for THIS producer
                $individualBreakdown = $this->getIndividualDenominationBreakdown((int) $payout);
                foreach ($individualBreakdown as $val => $count) {
                    $aggregatedBreakdown[$val] += $count;
                }

                $producerDetails[] = [
                    'nama' => $transaksi->owner->nama ?? 'Unknown',
                    'payout' => $payout,
                ];
            }
        }

        // Format the aggregated breakdown for the view
        $labels = [
            100000 => '100.000an',
            50000 => '50.000an',
            20000 => '20.000an',
            10000 => '10.000an',
            5000 => '5.000an',
            2000 => '2.000an',
            1000 => '1.000an',
            500 => '500an',
            200 => '200an',
            100 => '100an',
        ];

        $finalBreakdown = [];
        foreach ($aggregatedBreakdown as $val => $count) {
            if ($count > 0) {
                $finalBreakdown[] = [
                    'value' => $val,
                    'label' => $labels[$val],
                    'count' => $count,
                    'total' => $val * $count,
                ];
            }
        }

        return [
            'date' => $date,
            'total_payout' => $totalPayout,
            'breakdown' => $finalBreakdown,
            'producers' => $producerDetails,
        ];
    }

    /**
     * Greedy algorithm for a single amount
     */
    private function getIndividualDenominationBreakdown(int $amount): array
    {
        $denominations = [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 200, 100];
        $result = [];
        $remaining = $amount;

        foreach ($denominations as $val) {
            $count = (int) floor($remaining / $val);
            if ($count > 0) {
                $result[$val] = $count;
                $remaining %= $val;
            }
        }

        return $result;
    }
}
