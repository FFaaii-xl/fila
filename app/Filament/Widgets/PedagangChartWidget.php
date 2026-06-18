<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\SalesService;
use Carbon\Carbon;

class PedagangChartWidget extends ChartWidget
{
    protected ?string $heading = 'Grafik Penjualan 7 Hari Terakhir';
    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->owner_type, ['Admin', 'Pengurus', 'Pedagang']);
    }

    protected function getData(): array
    {
        $salesService = app(SalesService::class);
        $user = auth()->user();
        
        $pedagangId = $user->owner_type === 'Pedagang' ? $user->owner_id : request('pedagang_id');
        if (!$pedagangId) {
            return ['datasets' => [], 'labels' => []];
        }

        $endDate = now();
        $startDate = now()->subDays(6);
        
        $metrics = $salesService->getMetricsTrend('pedagang', $startDate, $endDate, $pedagangId);
        
        $labels = [];
        $dataLaba = [];
        
        foreach ($metrics as $m) {
            $labels[] = Carbon::parse($m->tanggal)->format('d/m');
            $dataLaba[] = $m->laba;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Laba Bersih',
                    'data' => $dataLaba,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
