<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * HasNuclearPrefetch Trait for Filament v5
 * 
 * Provides nuclear-level performance optimization for sales data prefetching.
 * This trait caches performance data at the static level to avoid repeated queries.
 * 
 * Used by: Pedagang, Produsen, Produk resources
 */
trait HasNuclearPrefetch
{
    /**
     * Static cache for performance data
     * Persists across requests within the same process
     */
    protected static ?array $perfCache = null;

    /**
     * Get the target type based on the class name
     * 
     * @return string 'pedagang', 'produsen', or 'produk'
     */
    protected function getNuclearTargetType(): string
    {
        $class = get_class($this);

        return match (true) {
            str_contains($class, 'Pedagang') => 'pedagang',
            str_contains($class, 'Produsen') => 'produsen',
            str_contains($class, 'Produk') => 'produk',
            default => 'produk'
        };
    }

    /**
     * Get the ninety days ago date for performance calculation
     */
    protected function getNinetyDaysAgo(): string
    {
        $tz = config('app.timezone', 'Asia/Jakarta');
        return now($tz)->subDays(90)->toDateString();
    }

    /**
     * Clear the nuclear prefetch cache
     * Call this after mutations that affect sales data
     */
    public static function clearNuclearCache(): void
    {
        static::$perfCache = null;
    }

    /**
     * Get performance data using nuclear prefetching
     * 
     * This method uses aggregated sales_summaries for global views (Admin/Pengurus)
     * and raw penjualan data for role-specific views.
     *
     * @param string $targetType The type to get perf data for ('pedagang', 'produsen', 'produk')
     * @param mixed $user The authenticated user
     * @return array Keyed by join_id with laku, titip, last_date
     */
    public function getNuclearPerfData(string $targetType, $user = null): array
    {
        $user = $user ?? auth()->user();
        $cacheKey = "perf_cache_{$targetType}";
        
        // Return from static cache if available
        if (isset(static::$perfCache[$cacheKey])) {
            return static::$perfCache[$cacheKey];
        }

        $ninetyDaysAgo = $this->getNinetyDaysAgo();
        $isAdminOrPengurus = $user && in_array($user->owner_type, ['Admin', 'Pengurus'], true);

        // NUCLEAR PERFORMANCE: Use aggregated sales_summaries for global views (Admin/Pengurus)
        if ($isAdminOrPengurus) {
            $data = DB::table('sales_summaries')
                ->where('type', $targetType)
                ->selectRaw('type_id as join_id')
                ->selectRaw("SUM(CASE WHEN date >= '{$ninetyDaysAgo}' THEN total_laku ELSE 0 END) as laku")
                ->selectRaw("SUM(CASE WHEN date >= '{$ninetyDaysAgo}' THEN total_titip ELSE 0 END) as titip")
                ->selectRaw('MAX(date) as last_date')
                ->groupBy('join_id')
                ->get()
                ->keyBy('join_id')
                ->all();
        } else {
            // Role-specific queries for Pedagang and Produsen
            $query = DB::table('penjualan')
                ->whereNull('penjualan.deleted_at');

            if ($user && $user->owner_type === 'Pedagang') {
                $query->where('pedagang_id', $user->owner_id);
                
                if ($targetType === 'produk') {
                    $query->selectRaw('produk_id as join_id');
                } else {
                    $query->join('produk as p_sub', 'penjualan.produk_id', '=', 'p_sub.id');
                    $query->selectRaw('p_sub.produsen_id as join_id');
                }
            } elseif ($user && $user->owner_type === 'Produsen') {
                // Optimization: Get products first to use index on produk_id
                $produkIds = DB::table('produk')
                    ->where('produsen_id', $user->owner_id)
                    ->pluck('id')
                    ->toArray();
                
                $query->whereIn('produk_id', $produkIds);

                if ($targetType === 'pedagang') {
                    $query->selectRaw('pedagang_id as join_id');
                } else {
                    $query->selectRaw('produk_id as join_id');
                }
            }

            $data = $query
                ->selectRaw("SUM(CASE WHEN tanggal >= '{$ninetyDaysAgo}' THEN laku ELSE 0 END) as laku")
                ->selectRaw("SUM(CASE WHEN tanggal >= '{$ninetyDaysAgo}' THEN titip ELSE 0 END) as titip")
                ->selectRaw('MAX(tanggal) as last_date')
                ->groupBy('join_id')
                ->get()
                ->keyBy('join_id')
                ->all();
        }

        static::$perfCache[$cacheKey] = $data;

        return $data;
    }

    /**
     * Get performance percentage for a specific ID
     * 
     * @param int|string $id The ID to get performance for
     * @param string|null $targetType Override target type
     * @return array ['percent' => int, 'laku' => float, 'titip' => float, 'last_date' => string|null]
     */
    public function getNuclearPerformance(int|string $id, ?string $targetType = null): array
    {
        $targetType = $targetType ?? $this->getNuclearTargetType();
        $data = $this->getNuclearPerfData($targetType);
        
        $row = $data[$id] ?? null;
        
        if (!$row) {
            return [
                'percent' => 0,
                'laku' => 0,
                'titip' => 0,
                'last_date' => null,
            ];
        }

        $laku = (float) $row->laku;
        $titip = (float) $row->titip;
        $percent = $titip > 0 ? (int) round(($laku / $titip) * 100) : 0;

        return [
            'percent' => $percent,
            'laku' => $laku,
            'titip' => $titip,
            'last_date' => $row->last_date ?? null,
        ];
    }

    /**
     * Calculate performance color based on percentage
     * 
     * @param int $percent The performance percentage
     * @return string Color class: 'success', 'warning', or 'danger'
     */
    public static function getNuclearPerfColor(int $percent): string
    {
        return match (true) {
            $percent > 85 => 'success',
            $percent >= 60 => 'warning',
            default => 'danger',
        };
    }

    /**
     * Render relative date badge (Editorial Style)
     * 
     * @param string|null $dateVal The date value
     * @return HtmlString|string HTML badge or empty string
     */
    protected function renderNuclearDateBadge(?string $dateVal): HtmlString|string
    {
        if (!$dateVal || $dateVal === '') {
            return '';
        }

        $tz = config('app.timezone', 'Asia/Jakarta');
        $date = Carbon::parse($dateVal, $tz)->startOfDay();
        $today = now($tz)->startOfDay();
        $diff = (int) $date->diffInDays($today);

        $label = match (true) {
            $diff === 0 => 'Hari Ini',
            $diff === 1 => 'Kemarin',
            default => "{$diff} H",
        };

        $color = match (true) {
            $diff <= 1 => 'success',
            $diff <= 6 => 'warning',
            default => 'danger',
        };

        $fullDate = date('d M Y', strtotime($dateVal));

        return new HtmlString("
            <div class='flex items-center justify-center' title='Terakhir Titip: {$fullDate}'>
                <span class='inline-flex items-center justify-center px-2 py-1 text-xs font-bold rounded' 
                      style='min-width: 45px; font-size: 9px; font-weight: 800; border-radius: 4px; 
                             text-transform: uppercase; text-align: center; cursor: help;
                             background-color: var(--".$color."-50, #f0fdf4); 
                             color: var(--".$color."-700, #15803d);'>
                    {$label}
                </span>
            </div>
        ");
    }
}
