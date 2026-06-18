<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\SalesService;
use Illuminate\Support\Facades\Cache;

class AdminOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->owner_type, ['Admin', 'Pengurus']);
    }

    protected function getStats(): array
    {
        $salesService = app(SalesService::class);
        $displayDate = request()->input('d', now()->toDateString());
        
        $startDate = now()->parse($displayDate)->startOfDay();
        $endDate = now()->parse($displayDate)->endOfDay();

        $avg = Cache::flexible("dashboard_intel_all_global_{$displayDate}", [300, 600], function () use ($salesService, $startDate, $endDate) {
            return $salesService->calculateAverageMetrics('all', $startDate, $endDate);
        });

        $omset = $avg['total_omset'] ?? 0;
        $laba = $avg['total_laba'] ?? 0;
        $lakuRate = $avg['rata_laku'] ?? 0;

        return [
            Stat::make('Total Omset', 'Rp ' . number_format($omset, 0, ',', '.'))
                ->description('Total omset hari ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
                
            Stat::make('Total Laba Bersih', 'Rp ' . number_format($laba, 0, ',', '.'))
                ->description('Total margin laba')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info')
                ->chart([3, 5, 4, 8, 5, 9, 10]),
                
            Stat::make('Efisiensi Terjual', number_format($lakuRate, 1) . '%')
                ->description('Rata-rata persentase barang laku')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($lakuRate > 75 ? 'success' : 'warning'),
        ];
    }
}
