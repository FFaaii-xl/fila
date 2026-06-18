<?php

declare(strict_types=1);

namespace App\Exports;

use App\Exports\Sheets\FinancialReportRecapSheet;
use App\Exports\Sheets\FinancialReportTabunganSheet;
use App\Services\FinancialReportService;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FinancialReportExport implements WithMultipleSheets
{
    use Exportable;

    private array $data;

    private string $title;

    private int $year;

    private int $month;

    public function __construct(array $data, string $title, int $year, int $month)
    {
        $this->data = $data;
        $this->title = $title;
        $this->year = $year;
        $this->month = $month;
    }

    public function sheets(): array
    {
        $service = app(FinancialReportService::class);
        $tabunganData = $service->getTabunganExportData($this->year, $this->month);

        return [
            new FinancialReportRecapSheet($this->data, $this->title),
            new FinancialReportTabunganSheet($tabunganData, $this->title),
        ];
    }
}
