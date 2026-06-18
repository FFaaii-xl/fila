<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Financial Period Detection Trait for Filament v5
 * 
 * Provides methods to detect and build financial periods based on 'finalize' markers
 * in the detail_tabungan table.
 */
trait FinancialPeriodDetection
{
    /**
     * Build standard financial periods based on 'finalize' markers
     * 
     * @param int $cacheSeconds Cache duration in seconds
     * @return array Array of period definitions with start, end, and label
     */
    public static function buildFinancialPeriods(int $cacheSeconds = 120): array
    {
        $finalDates = Cache::remember('tabungan_periods_finalize_global', $cacheSeconds, function () {
            return DB::table('detail_tabungan')
                ->whereNull('deleted_at')
                ->where('keterangan', 'LIKE', 'finalize%')
                ->orderBy('tanggal')
                ->pluck('tanggal')
                ->map(fn ($d) => date('Y-m-d', strtotime($d)))
                ->unique()
                ->values();
        });

        $boundaries = Cache::remember('tabungan_boundaries_global', 3600, function () {
            return DB::table('detail_tabungan')
                ->whereNull('deleted_at')
                ->select([
                    DB::raw('MIN(tanggal) as min_date'),
                    DB::raw('MAX(tanggal) as max_date'),
                ])
                ->first();
        });

        $periods = [];
        $start = $boundaries->min_date ? date('Y-m-d', strtotime($boundaries->min_date)) : date('Y-m-d');
        $periodeKe = 1;

        foreach ($finalDates as $fdt) {
            $periods[] = [
                'start' => $start,
                'end' => $fdt,
                'label' => "Periode {$periodeKe} (".date('d/m/y', strtotime($start)).' - '.date('d/m/y', strtotime($fdt)).')',
            ];
            $start = Carbon::parse($fdt)->addDay()->format('Y-m-d');
            $periodeKe++;
        }

        $actualMax = $boundaries->max_date ? date('Y-m-d', strtotime($boundaries->max_date)) : date('Y-m-d');
        $end = max(date('Y-m-d'), $actualMax);

        $periods[] = [
            'start' => $start,
            'end' => $end,
            'label' => "Periode {$periodeKe} (".date('d/m/y', strtotime($start)).' - Berjalan)',
        ];

        return $periods;
    }

    /**
     * Get the start date of the current (running) period
     */
    public static function getCurrentPeriodStartDate(): string
    {
        $periods = self::buildFinancialPeriods();

        return end($periods)['start'];
    }

    /**
     * Get the current period information
     */
    public static function getCurrentPeriod(): array
    {
        $periods = self::buildFinancialPeriods();

        return end($periods);
    }

    /**
     * Get all periods as options for select dropdown
     */
    public static function getPeriodOptions(): array
    {
        return collect(self::buildFinancialPeriods())
            ->pluck('label', 'start')
            ->toArray();
    }
}
