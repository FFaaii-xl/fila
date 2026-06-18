<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * HasLastDeposit Trait for Filament v5
 * 
 * Provides "Terakhir Titip" (Last Deposit) column for Filament tables.
 * Integrates with HasNuclearPrefetch for optimal performance.
 * 
 * Usage in Resource:
 *   use HasNuclearPrefetch, HasLastDeposit;
 * 
 * Then in table columns:
 *   TextColumn::make('ttp_sort', 'T.TTP')
 *       ->html()
 *       ->formatStateUsing(fn ($record) => $this->getLastDepositHtml($record))
 */
trait HasLastDeposit
{
    use HasNuclearPrefetch, HasRoleAuthorization;

    /**
     * Check if this resource is Pedagang
     */
    protected function isPedagangResource(): bool
    {
        return str_contains(get_class($this), 'Pedagang');
    }

    /**
     * Check if Last Deposit column should be visible
     * 
     * Hides for Admin viewing Pedagang list (but shows for Produsen viewing Pedagang)
     */
    protected function shouldShowLastDeposit(): bool
    {
        // Sembunyikan jika Admin di daftar Pedagang
        if ($this->isAdminOrPengurus() && $this->isPedagangResource()) {
            return false;
        }

        return true;
    }

    /**
     * Get Last Deposit HTML for a record
     * 
     * @param mixed $record The model record
     * @return HtmlString|string HTML badge or empty string
     */
    public function getLastDepositHtml($record): HtmlString|string
    {
        if (!$this->shouldShowLastDeposit()) {
            return '';
        }

        $user = auth()->user();
        $id = $record->getKey();
        $targetType = $this->getNuclearTargetType();
        
        $data = $this->getNuclearPerfData($targetType, $user);
        $row = $data[$id] ?? null;

        return $this->renderNuclearDateBadge($row->last_date ?? null);
    }

    /**
     * Get Last Deposit value for sorting
     * 
     * @param int|string $id The record ID
     * @param string|null $targetType Override target type
     * @return string|null Date string for sorting
     */
    public function getLastDepositSortValue(int|string $id, ?string $targetType = null): ?string
    {
        $targetType = $targetType ?? $this->getNuclearTargetType();
        $data = $this->getNuclearPerfData($targetType);
        
        $row = $data[$id] ?? null;
        
        return $row->last_date ?? null;
    }

    /**
     * Apply Last Deposit query modification to builder
     * 
     * @param mixed $builder The query builder
     * @param string $table The table name
     */
    public function getLastDepositSortQuery($builder, string $table)
    {
        $targetType = $this->getNuclearTargetType();

        $subQuery = DB::table('sales_summaries')
            ->select('type_id')
            ->selectRaw('MAX(date) as last_date')
            ->where('type', $targetType)
            ->groupBy('type_id');

        return $builder->leftJoinSub($subQuery, 'sort_ttp', "{$table}.id", '=', 'sort_ttp.type_id')
            ->orderBy('last_date', 'desc');
    }

    /**
     * Get the "Terakhir Titip" date for a specific record
     * 
     * @param int|string $id The record ID
     * @param string|null $targetType Override target type
     * @return string|null Formatted date or null
     */
    public function getTerakhirTitip(int|string $id, ?string $targetType = null): ?string
    {
        $targetType = $targetType ?? $this->getNuclearTargetType();
        $data = $this->getNuclearPerfData($targetType);
        
        $row = $data[$id] ?? null;
        
        if (!$row || !$row->last_date) {
            return null;
        }

        return date('d M Y', strtotime($row->last_date));
    }

    /**
     * Get raw last deposit data for a record
     * 
     * @param int|string $id The record ID
     * @param string|null $targetType Override target type
     * @return object|null Raw data object or null
     */
    public function getLastDepositData(int|string $id, ?string $targetType = null): ?object
    {
        $targetType = $targetType ?? $this->getNuclearTargetType();
        $data = $this->getNuclearPerfData($targetType);
        
        return $data[$id] ?? null;
    }
}
