<?php

namespace App\Filament\Pages;

use App\Services\FinancialReportService;
use App\Traits\Filament\HasRoleAuthorization;
use Filament\Pages\Page;

class FinancialReportPage extends Page
{
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static string | \UnitEnum | null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 10;
    protected static ?string $title = 'Laporan Bulanan Keuangan Pusat';

    protected string $view = 'filament.pages.financial-report-page';

    private array $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public static function canAccess(): bool
    {
        return (new static)->isAdminOrPengurus();
    }

    protected function getViewData(): array
    {
        $service = app(FinancialReportService::class);

        $defaultMonth = (int) date('n') - 1;
        $defaultYear = (int) date('Y');
        if ($defaultMonth === 0) {
            $defaultMonth = 12;
            $defaultYear -= 1;
        }

        $month = (int) request('month', $defaultMonth);
        $year = (int) request('year', $defaultYear);

        $recap = $service->getMonthlyDailyRecap($year, $month);

        return [
            'months' => $this->months,
            'month' => $month,
            'year' => $year,
            'years' => array_combine(
                range(date('Y') - 2, date('Y') + 1),
                range(date('Y') - 2, date('Y') + 1)
            ),
            'recap' => $recap,
        ];
    }
}
