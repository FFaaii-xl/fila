<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * HasPerformanceMetrics Trait for Filament v5
 * 
 * Provides performance metrics columns for Filament tables.
 * Integrates with HasNuclearPrefetch for optimal performance.
 * 
 * Usage in Resource:
 *   use HasNuclearPrefetch, HasPerformanceMetrics;
 * 
 * Then in table columns:
 *   PerformanceMetricsColumn::make('perf_sort', 'Perf')
 *       ->getPerfCallback()
 */
trait HasPerformanceMetrics
{
    use HasNuclearPrefetch;

    /**
     * Get performance data callback for column
     * 
     * @return callable Returns a function that accepts $record and returns HTML badge
     */
    public function getPerfCallback(): callable
    {
        return function ($record) {
            $user = auth()->user();
            $id = $record->getKey();
            $targetType = $this->getNuclearTargetType();
            
            $perf = $this->getNuclearPerformance($id, $targetType);
            
            if ($perf['titip'] <= 0) {
                return '';
            }

            $percent = $perf['percent'];
            $color = $this->getNuclearPerfColor($percent);
            $title = "90 Hari: {$percent}% ({$perf['laku']}/{$perf['titip']})";

            return new HtmlString("
                <div class='flex items-center justify-center' title='{$title}'>
                    <span class='inline-flex items-center justify-center px-2 py-1 text-xs font-bold rounded' 
                          style='min-width: 45px; font-size: 10px; font-weight: 800; border-radius: 4px; 
                                 text-transform: uppercase; text-align: center;
                                 background-color: var(--{$color}-100, #dcfce7); 
                                 color: var(--{$color}-700, #15803d);'>
                        {$percent}%
                    </span>
                </div>
            ");
        };
    }

    /**
     * Get performance percentage for sorting
     * 
     * @param int|string $id The record ID
     * @param string|null $targetType Override target type
     * @return int Performance percentage (0-100+)
     */
    public function getPerfSortValue(int|string $id, ?string $targetType = null): int
    {
        $targetType = $targetType ?? $this->getNuclearTargetType();
        $perf = $this->getNuclearPerformance($id, $targetType);
        
        return $perf['percent'];
    }

    /**
     * Apply performance query modification to builder
     * 
     * @param mixed $builder The query builder
     * @param string $tableName The main table name
     */
    protected function applyPerformanceQuery($builder, string $tableName): void
    {
        // Query utama tetap bersih untuk kecepatan maksimal
        // Prefetch dilakukan di getNuclearPerfData()
    }

    /**
     * Get sortable performance query modification
     * 
     * @param mixed $builder The query builder
     * @param string $table The table name
     * @param string $direction Sort direction (asc/desc)
     * @return mixed Modified query builder
     */
    public function getPerfSortQuery($builder, string $table, string $direction = 'desc')
    {
        $targetType = $this->getNuclearTargetType();
        $ninetyDaysAgo = $this->getNinetyDaysAgo();

        $subQuery = DB::table('sales_summaries')
            ->select('type_id')
            ->selectRaw('SUM(total_laku) / NULLIF(SUM(total_titip), 0) as perf_ratio')
            ->where('type', $targetType)
            ->where('date', '>=', $ninetyDaysAgo)
            ->groupBy('type_id');

        return $builder->leftJoinSub($subQuery, 'sort_perf', "{$table}.id", '=', 'sort_perf.type_id')
            ->orderBy('perf_ratio', $direction);
    }
}
