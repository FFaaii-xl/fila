<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\SalesService;
use Illuminate\Support\Facades\Cache;

class MerchantOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->owner_type, ['Admin', 'Pengurus', 'Pedagang']);
    }

    protected function getStats(): array
    {
        $salesService = app(SalesService::class);
        $user = auth()->user();
        $displayDate = request()->input('d', now()->toDateString());
        
        $startDate = now()->parse($displayDate)->startOfDay();
        $endDate = now()->parse($displayDate)->endOfDay();

        $pedagangId = $user->owner_type === 'Pedagang' ? $user->owner_id : request('pedagang_id');
        
        if (!$pedagangId) {
            return []; // Don't show if no specific merchant selected/logged in
        }

        $avg = Cache::flexible("dashboard_intel_pedagang_{$pedagangId}_{$displayDate}", [300, 600], function () use ($salesService, $startDate, $endDate, $pedagangId) {
            return $salesService->calculateAverageMetrics('pedagang', $startDate, $endDate, $pedagangId);
        });

        $omset = $avg['total_omset'] ?? 0;
        $laba = $avg['total_laba'] ?? 0;
        $lakuRate = $avg['rata_laku'] ?? 0;

        return [
            Stat::make('Omset Pedagang', 'Rp ' . number_format($omset, 0, ',', '.'))
                ->description('Total omset pedagang ini')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),
                
            Stat::make('Laba Pedagang', 'Rp ' . number_format($laba, 0, ',', '.'))
                ->description('Total laba pedagang')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('info'),
                
            Stat::make('Rata-rata Terjual', number_format($lakuRate, 1) . '%')
                ->description('Persentase barang laku')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($lakuRate > 75 ? 'success' : 'warning'),
        ];
    }
}
