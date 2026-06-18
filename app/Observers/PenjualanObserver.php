<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Penjualan;
use App\Services\SalesService;
use Illuminate\Support\Facades\Cache;

class PenjualanObserver
{
    /**
     * Handle the Penjualan "saved" event (Covers created & updated).
     * Memicu refresh summary jika statusnya 'Ok' atau baru saja berubah menjadi 'Ok'
     */
    public function saved(Penjualan $penjualan): void
    {
        // Jika status adalah 'Ok' atau baru saja berubah (misal dari Draft ke Ok)
        if ($penjualan->status === 'Ok' || $penjualan->isDirty('status') || $penjualan->wasChanged('status')) {
            app(SalesService::class)->refreshSummary(date('Y-m-d', strtotime($penjualan->tanggal)));

            // Invalidaasi cache daftar pedagang aktif agar filter hub diperbarui
            Cache::forget('active_pedagangs_list');
            
            // PHASE 56: Invalidate pedagango preload cache for instant fresh data
            $tanggal = date('Y-m-d', strtotime($penjualan->tanggal));
            $pedagangId = $penjualan->pedagang_id;
            if ($pedagangId) {
                Cache::forget("pedagang_summary_{$pedagangId}_{$tanggal}");
                Cache::forget("not_reported_pedagang_{$pedagangId}_{$tanggal}");
            }
        }
    }

    /**
     * Handle the Penjualan "deleted" event.
     */
    public function deleted(Penjualan $penjualan): void
    {
        if ($penjualan->status === 'Ok') {
            app(SalesService::class)->refreshSummary(date('Y-m-d', strtotime($penjualan->tanggal)));
        }
    }

    /**
     * Handle the Penjualan "restored" event.
     */
    public function restored(Penjualan $penjualan): void
    {
        if ($penjualan->status === 'Ok') {
            app(SalesService::class)->refreshSummary(date('Y-m-d', strtotime($penjualan->tanggal)));
        }
    }

    /**
     * Handle the Penjualan "force deleted" event.
     */
    public function forceDeleted(Penjualan $penjualan): void
    {
        if ($penjualan->status === 'Ok') {
            app(SalesService::class)->refreshSummary(date('Y-m-d', strtotime($penjualan->tanggal)));
        }
    }
}
