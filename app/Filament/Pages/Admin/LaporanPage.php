<?php

namespace App\Filament\Pages\Admin;

use App\Traits\Filament\HasReportPageStyling;
use App\Traits\Filament\HasRoleAuthorization;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;

class LaporanPage extends Page
{
    use HasReportPageStyling;
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string | \UnitEnum | null $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Laporan & Analisis';
    protected string $view = 'filament.pages.admin.laporan-page';

    #[Url]
    public string $mode = 'tanggal';
    
    #[Url]
    public string $selectedDate = '';
    
    #[Url]
    public string $dateStart = '';
    
    #[Url]
    public string $dateEnd = '';
    
    #[Url]
    public ?string $pedagangId = null;
    
    #[Url]
    public string $month = '';
    
    #[Url]
    public string $year = '';

    public array $reportData = [];
    public array $totals = [];
    public array $pedagangList = [];

    public function mount(): void
    {
        $this->authorizeAccess();
        
        $latestTanggal = DB::table('penjualan')
            ->whereNull('deleted_at')
            ->max('tanggal');

        $this->selectedDate = $latestTanggal ? date('Y-m-d', strtotime($latestTanggal)) : date('Y-m-d');
        $this->dateStart = $this->selectedDate;
        $this->dateEnd = $this->selectedDate;
        $this->month = $latestTanggal ? date('m', strtotime($latestTanggal)) : date('m');
        $this->year = $latestTanggal ? date('Y', strtotime($latestTanggal)) : date('Y');
        
        $this->loadPedagangList();
        $this->loadReportData();
    }

    protected function loadPedagangList(): void
    {
        $this->pedagangList = DB::table('pedagang')
            ->whereNull('deleted_at')
            ->orderBy('nama')
            ->get()
            ->toArray();
    }

    public function loadReportData(): void
    {
        $results = collect();

        if ($this->mode === 'tanggal') {
            $results = DB::table('penjualan as p')
                ->join('pedagang as pdk', 'p.pedagang_id', '=', 'pdk.id')
                ->whereBetween('p.tanggal', [$this->selectedDate . ' 00:00:00', $this->selectedDate . ' 23:59:59'])
                ->whereNull('p.deleted_at')
                ->when($this->pedagangId, fn($q) => $q->where('p.pedagang_id', $this->pedagangId))
                ->select(
                    'pdk.nama',
                    'p.pedagang_id',
                    DB::raw('COUNT(DISTINCT p.produk_id) as total_produk'),
                    DB::raw('SUM(p.titip) as total_titip'),
                    DB::raw('SUM(p.laku) as total_laku'),
                    DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                    DB::raw('SUM(p.laku * p.harga_jual) as total_omset')
                )
                ->groupBy('p.pedagang_id', 'pdk.nama')
                ->get();
        } elseif ($this->mode === 'bulanan') {
            $monthStart = sprintf('%s-%02d-01 00:00:00', $this->year, (int) $this->month);
            $monthEnd = date('Y-m-t 23:59:59', strtotime($monthStart));
            
            $results = DB::table('penjualan as p')
                ->join('pedagang as pdk', 'p.pedagang_id', '=', 'pdk.id')
                ->whereBetween('p.tanggal', [$monthStart, $monthEnd])
                ->whereNull('p.deleted_at')
                ->when($this->pedagangId, fn($q) => $q->where('p.pedagang_id', $this->pedagangId))
                ->select(
                    DB::raw('DATE(p.tanggal) as tgl'),
                    DB::raw('COUNT(DISTINCT p.produk_id) as total_produk'),
                    DB::raw('SUM(p.titip) as total_titip'),
                    DB::raw('SUM(p.laku) as total_laku'),
                    DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                    DB::raw('SUM(p.laku * p.harga_jual) as total_omset')
                )
                ->groupBy('tgl')
                ->get();
        } elseif ($this->mode === 'range') {
            $results = DB::table('penjualan as p')
                ->join('pedagang as pdk', 'p.pedagang_id', '=', 'pdk.id')
                ->whereBetween('p.tanggal', [$this->dateStart . ' 00:00:00', $this->dateEnd . ' 23:59:59'])
                ->whereNull('p.deleted_at')
                ->when($this->pedagangId, fn($q) => $q->where('p.pedagang_id', $this->pedagangId))
                ->select(
                    'pdk.nama',
                    'p.pedagang_id',
                    DB::raw('COUNT(DISTINCT p.produk_id) as total_produk'),
                    DB::raw('SUM(p.titip) as total_titip'),
                    DB::raw('SUM(p.laku) as total_laku'),
                    DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                    DB::raw('SUM(p.laku * p.harga_jual) as total_omset')
                )
                ->groupBy('p.pedagang_id', 'pdk.nama')
                ->get();
        }

        $this->reportData = $results->map(function ($row) {
            $modal = (float) ($row->total_modal ?? 0);
            $omset = (float) ($row->total_omset ?? 0);
            
            return [
                'nama' => $row->nama ?? '',
                'tgl' => $row->tgl ?? $this->selectedDate,
                'total_produk' => $row->total_produk ?? 0,
                'total_titip' => $row->total_titip ?? 0,
                'total_laku' => $row->total_laku ?? 0,
                'total_modal' => $modal,
                'total_omset' => $omset,
                'total_laba' => $omset - $modal,
                'persen_laku' => ($row->total_titip ?? 0) > 0 
                    ? round(($row->total_laku / $row->total_titip) * 100, 1) 
                    : 0,
            ];
        })->toArray();

        $this->totals = [
            'produk' => array_sum(array_column($this->reportData, 'total_produk')),
            'titip' => array_sum(array_column($this->reportData, 'total_titip')),
            'laku' => array_sum(array_column($this->reportData, 'total_laku')),
            'modal' => array_sum(array_column($this->reportData, 'total_modal')),
            'omset' => array_sum(array_column($this->reportData, 'total_omset')),
            'laba' => array_sum(array_column($this->reportData, 'total_laba')),
        ];
    }

    public function updatedMode(): void
    {
        $this->loadReportData();
    }

    public function updatedSelectedDate(): void
    {
        $this->loadReportData();
    }

    public function updatedDateStart(): void
    {
        $this->loadReportData();
    }

    public function updatedDateEnd(): void
    {
        $this->loadReportData();
    }

    public function updatedPedagangId(): void
    {
        $this->loadReportData();
    }

    public function updatedMonth(): void
    {
        $this->loadReportData();
    }

    public function updatedYear(): void
    {
        $this->loadReportData();
    }

    public function getModes(): array
    {
        return [
            'tanggal' => 'Harian',
            'bulanan' => 'Bulanan',
            'range' => 'Range',
        ];
    }

    protected function authorizeAccess(): void
    {
        abort_unless($this->isAdminOrPengurus(), 403);
    }
}
