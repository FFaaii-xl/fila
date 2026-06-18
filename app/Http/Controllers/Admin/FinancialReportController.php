<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\FinancialReportExport;
use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReportController extends Controller
{
    public function export()
    {
        $defaultMonth = (int) date('n') - 1;
        $defaultYear = (int) date('Y');
        if ($defaultMonth === 0) {
            $defaultMonth = 12;
            $defaultYear -= 1;
        }

        $month = (int) request('month', $defaultMonth);
        $year = (int) request('year', $defaultYear);

        $service = app(FinancialReportService::class);
        $data = $service->getMonthlyDailyRecap($year, $month);

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $monthName = $months[$month] ?? 'Financial';
        $fileName = "Laporan_Keuangan_{$monthName}_{$year}.xlsx";

        return Excel::download(new FinancialReportExport($data, "{$monthName} {$year}", $year, $month), $fileName);
    }
}
