<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LogProduk;
use App\Models\Produk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TemplateVersionManager
{
    /**
     * Get current product set (Logic: 60 business days + 2 weeks new)
     */
    public function getCurrentProductSet(): Collection
    {
        $latest60Dates = cache()->remember('latest_60_sale_dates', 3600, function () {
            return DB::table('penjualan')
                ->select('tanggal')
                ->distinct()
                ->orderBy('tanggal', 'desc')
                ->limit(60)
                ->pluck('tanggal')
                ->toArray();
        });

        $soldProductIds = DB::table('penjualan')
            ->whereIn('tanggal', $latest60Dates)
            ->pluck('produk_id');

        $newProductIds = Produk::where('created_at', '>=', now()->subWeeks(2))
            ->pluck('id');

        return Produk::whereIn('id', $soldProductIds->merge($newProductIds)->unique())
            ->orderBy('id', 'asc')
            ->get(['id', 'nama', 'harga_beli', 'harga_jual']);
    }

    /**
     * Get recently changed products for template notification and highlighting
     */
    public function getRecentlyChangedData(): array
    {
        $cutoff = now()->subDays(7);
        $logs = LogProduk::where('created_at', '>=', $cutoff)->get();

        // New products (Added in last 7 days)
        $addedNames = $logs->where('field_name', 'created')->pluck('new_value')->toArray();

        // Edited products (Updated attributes in last 7 days, excluding those that were just created)
        $editedLogs = $logs->whereIn('field_name', ['nama', 'harga_beli', 'harga_jual', 'produsen_id']);
        $editedNames = $editedLogs->pluck('nama_produk')->unique()->filter(fn ($name) => ! in_array($name, $addedNames, true))->toArray();

        return [
            'added' => array_values($addedNames),
            'edits' => array_values($editedNames),
            'has_changes' => $logs->isNotEmpty(),
            'version_date' => $this->getCurrentVersionDate(),
        ];
    }

    /**
     * Get the current version reference based on latest change
     */
    public function getCurrentVersionDate(): string
    {
        $latest = LogProduk::latest()->first();

        return $latest ? $latest->created_at->format('d.m.y-Hi') : date('d.m.y');
    }

    /**
     * Replacement for legacy updateVersion - just returns current state
     */
    public function updateVersion(): array
    {
        return $this->getRecentlyChangedData();
    }

    public function getLatestEntry(): ?array
    {
        return $this->getRecentlyChangedData();
    }

    /**
     * Get history from DB (LogProduk)
     */
    public function getHistory(): array
    {
        return LogProduk::latest()
            ->limit(100)
            ->get()
            ->toArray();
    }
}
