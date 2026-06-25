<?php

namespace App\Filament\Pages\Pedagang;

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
    protected static ?string $title = 'Riwayat Penjualan';
    protected string $view = 'filament.pages.pedagang.laporan-page';

    #[Url]
    public string $mode = 'tanggal';
    
    #[Url]
    public string $selectedDate = '';
    
    #[Url]
    public string $dateStart = '';
    
    #[Url]
    public string $dateEnd = '';
    
    #[Url]
    public string $month = '';
    
    #[Url]
    public string $year = '';

    public array $reportData = [];
    public array $totals = [];

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
        
        $this->loadReportData();
    }

    public function loadReportData(): void
    {
        $user = auth()->user();
        if (!$user || $user->owner_type !== 'Pedagang') {
            $this->reportData = [];
            return;
        }

        $pedagangId = $user->owner_id;
        $results = collect();

        if ($this->mode === 'tanggal') {
            $results = DB::table('penjualan as p')
                ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
                ->where('p.pedagang_id', $pedagangId)
                ->whereBetween('p.tanggal', [$this->selectedDate . ' 00:00:00', $this->selectedDate . ' 23:59:59'])
                ->whereNull('p.deleted_at')
                ->select(
                    'pr.nama as produk_nama',
                    'p.produk_id',
                    DB::raw('SUM(p.titip) as total_titip'),
                    DB::raw('SUM(p.laku) as total_laku'),
                    DB::raw('SUM(p.retur) as total_retur'),
                    DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                    DB::raw('SUM(p.laku * p.harga_jual) as total_omset')
                )
                ->groupBy('p.produk_id', 'pr.nama')
                ->get();
        } elseif ($this->mode === 'bulanan') {
            $monthStart = sprintf('%s-%02d-01 00:00:00', $this->year, (int) $this->month);
            $monthEnd = date('Y-m-t 23:59:59', strtotime($monthStart));
            
            $results = DB::table('penjualan as p')
                ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
                ->where('p.pedagang_id', $pedagangId)
                ->whereBetween('p.tanggal', [$monthStart, $monthEnd])
                ->whereNull('p.deleted_at')
                ->select(
                    DB::raw('DATE(p.tanggal) as tgl'),
                    'pr.nama as produk_nama',
                    DB::raw('SUM(p.titip) as total_titip'),
                    DB::raw('SUM(p.laku) as total_laku'),
                    DB::raw('SUM(p.retur) as total_retur'),
                    DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                    DB::raw('SUM(p.laku * p.harga_jual) as total_omset')
                )
                ->groupBy('tgl', 'p.produk_id', 'pr.nama')
                ->get();
        } elseif ($this->mode === 'range') {
            $results = DB::table('penjualan as p')
                ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
                ->where('p.pedagang_id', $pedagangId)
                ->whereBetween('p.tanggal', [$this->dateStart . ' 00:00:00', $this->dateEnd . ' 23:59:59'])
                ->whereNull('p.deleted_at')
                ->select(
                    'pr.nama as produk_nama',
                    'p.produk_id',
                    DB::raw('SUM(p.titip) as total_titip'),
                    DB::raw('SUM(p.laku) as total_laku'),
                    DB::raw('SUM(p.retur) as total_retur'),
                    DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                    DB::raw('SUM(p.laku * p.harga_jual) as total_omset')
                )
                ->groupBy('p.produk_id', 'pr.nama')
                ->get();
        }

        $this->reportData = $results->map(function ($row) {
            $modal = (float) ($row->total_modal ?? 0);
            $omset = (float) ($row->total_omset ?? 0);
            $titip = (float) ($row->total_titip ?? 0);
            $laku = (float) ($row->total_laku ?? 0);
            $retur = (float) ($row->total_retur ?? 0);
            
            return [
                'produk_nama' => $row->produk_nama ?? '',
                'tgl' => $row->tgl ?? $this->selectedDate,
                'total_titip' => $titip,
                'total_laku' => $laku,
                'total_retur' => $retur,
                'sisa' => $titip - $laku - $retur,
                'total_modal' => $modal,
                'total_omset' => $omset,
                'total_laba' => $omset - $modal,
                'persen_laku' => $titip > 0 ? round(($laku / $titip) * 100, 1) : 0,
            ];
        })->toArray();

        $this->totals = [
            'titip' => array_sum(array_column($this->reportData, 'total_titip')),
            'laku' => array_sum(array_column($this->reportData, 'total_laku')),
            'retur' => array_sum(array_column($this->reportData, 'total_retur')),
            'sisa' => array_sum(array_column($this->reportData, 'sisa')),
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
        abort_unless($this->isPedagang(), 403);
    }
}
