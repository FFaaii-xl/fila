<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\SalesService;
use Illuminate\Support\Facades\Cache;

class ProdusenOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->owner_type, ['Admin', 'Pengurus', 'Produsen']);
    }

    protected function getStats(): array
    {
        $salesService = app(SalesService::class);
        $user = auth()->user();
        $displayDate = request()->input('d', now()->toDateString());
        
        $startDate = now()->parse($displayDate)->startOfDay();
        $endDate = now()->parse($displayDate)->endOfDay();

        $produsenId = $user->owner_type === 'Produsen' ? $user->owner_id : request('produsen_id');
        
        if (!$produsenId) {
            return []; // Don't show if no specific produsen selected/logged in
        }

        $avg = Cache::flexible("dashboard_intel_produsen_{$produsenId}_{$displayDate}", [300, 600], function () use ($salesService, $startDate, $endDate, $produsenId) {
            return $salesService->calculateAverageMetrics('produsen', $startDate, $endDate, $produsenId);
        });

        $omset = $avg['total_omset'] ?? 0;
        $lakuRate = $avg['rata_laku'] ?? 0;

        return [
            Stat::make('Estimasi Pendapatan', 'Rp ' . number_format($omset, 0, ',', '.'))
                ->description('Total pendapatan kotor')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('Tingkat Terjual', number_format($lakuRate, 1) . '%')
                ->description('Persentase barang laku')
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->color($lakuRate > 75 ? 'success' : 'warning'),
        ];
    }
}
