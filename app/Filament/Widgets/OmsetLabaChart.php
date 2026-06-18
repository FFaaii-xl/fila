<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Services\SalesService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class OmsetLabaChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'omsetLabaChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Trend Omset & Laba (7 Hari Terakhir)';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->owner_type, ['Admin', 'Pengurus']);
    }

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $salesService = app(SalesService::class);
        $days = [];
        $omsetData = [];
        $labaData = [];

        // Get last 7 days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $days[] = $date->translatedFormat('d M');
            $dateString = $date->toDateString();

            $startDate = $date->copy()->startOfDay();
            $endDate = $date->copy()->endOfDay();

            $avg = Cache::flexible("dashboard_intel_all_global_{$dateString}", [300, 600], function () use ($salesService, $startDate, $endDate) {
                return $salesService->calculateAverageMetrics('all', $startDate, $endDate);
            });

            $omsetData[] = $avg['total_omset'] ?? 0;
            $labaData[] = $avg['total_laba'] ?? 0;
        }

        return [
            'chart' => [
                'type' => 'area',
                'height' => 300,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Omset',
                    'data' => $omsetData,
                ],
                [
                    'name' => 'Laba Bersih',
                    'data' => $labaData,
                ],
            ],
            'xaxis' => [
                'categories' => $days,
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => ['#10b981', '#3b82f6'],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 3,
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shadeIntensity' => 1,
                    'opacityFrom' => 0.4,
                    'opacityTo' => 0.05,
                    'stops' => [0, 90, 100]
                ]
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
        ];
    }
}
