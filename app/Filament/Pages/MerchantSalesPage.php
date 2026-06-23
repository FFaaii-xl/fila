<?php

namespace App\Filament\Pages;

use App\Traits\MerchantFinancialRules;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MerchantSalesPage extends Page
{
    use MerchantFinancialRules;

    public string $mode = 'tanggal';
    public string $selectedDate = '';
    public string $dateStart = '';
    public string $dateEnd = '';
    public ?string $pedagangId = null;
    public string $month = '';
    public string $year = '';
    public string $sort = 'nama';
    public string $direction = 'asc';
    public string $rangeType = 'hari';

    public array $reportData = [];
    public array $totals = [];
    public array $pedagangList = [];
    public $notReported = null;
    public bool $isPedagangProductMode = false;
    public bool $isPedagangUser = false;

    public function mount(): void
    {
        // Direct query without cache to avoid serialization issues
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

    public function getHeading(): string
    {
        return 'Laporan Penjualan Pedagang';
    }

    public function getView(): string
    {
        return 'filament.pages.merchant-sales';
    }

    public function loadReportData(): void
    {
        $user = Auth::user();
        
        // Reset states
        $this->isPedagangProductMode = false;
        $this->isPedagangUser = false;

        // Pedagang user: lock to self
        if ($user && $user->owner_type === 'Pedagang') {
            $this->pedagangId = $user->owner_id;
            $this->isPedagangUser = true;
            if ($this->mode === 'tanggal') {
                $this->isPedagangProductMode = true;
            }
        }

        // Range type detection
        if ($this->mode === 'range') {
            $diffInDays = \Carbon\Carbon::parse($this->dateStart)->diffInDays(\Carbon\Carbon::parse($this->dateEnd));
            if ($diffInDays > 365) {
                $this->rangeType = 'tahun';
            } elseif ($diffInDays >= 30) {
                $this->rangeType = 'bulan';
            } else {
                $this->rangeType = 'hari';
            }
            $this->sort = $this->rangeType === 'hari' ? 'tgl' : ($this->rangeType === 'bulan' ? 'bln' : 'thn');
        } elseif ($this->mode === 'tanggal') {
            $this->sort = 'nama';
        } elseif ($this->mode === 'nama') {
            $this->sort = 'tgl';
        } else {
            $this->sort = 'bln';
        }

        // Load pedagang list for Admin/Pengurus - direct query without cache
        $this->pedagangList = DB::table('pedagang')
            ->whereNull('deleted_at')
            ->orderBy('nama')
            ->get()
            ->toArray();

        // DATA AGGREGATION ENGINE
        $results = collect();

        if ($this->mode === 'tanggal') {
            // HARIAN mode
            if ($this->isPedagangUser && $this->pedagangId) {
                // Single Pedagang - show products
                $results = DB::table('penjualan as p')
                    ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
                    ->whereBetween('p.tanggal', [$this->selectedDate.' 00:00:00', $this->selectedDate.' 23:59:59'])
                    ->where('p.pedagang_id', $this->pedagangId)
                    ->whereNull('p.deleted_at')
                    ->select('pr.nama', 'p.produk_id', 'p.pedagang_id',
                        DB::raw('1 as total_produk'),
                        DB::raw('SUM(p.titip) as total_titip'),
                        DB::raw('SUM(p.laku) as total_laku'),
                        DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                        DB::raw('SUM(p.laku * p.harga_jual) as total_omset'),
                        DB::raw('0 as tabungan_rate'),
                        DB::raw('1 as is_product_row'),
                        DB::raw('1 as proup_applied'))
                    ->groupBy('p.produk_id', 'pr.nama', 'p.pedagang_id')
                    ->get();
            } else {
                // All pedagang - aggregate by pedagang
                $results = DB::table('penjualan as p')
                    ->join('pedagang as pdk', 'p.pedagang_id', '=', 'pdk.id')
                    ->whereBetween('p.tanggal', [$this->selectedDate.' 00:00:00', $this->selectedDate.' 23:59:59'])
                    ->whereNull('p.deleted_at')
                    ->select('pdk.nama', 'p.pedagang_id', 'pdk.tabungan_rate',
                        DB::raw('COUNT(DISTINCT p.produk_id) as total_produk'),
                        DB::raw('SUM(p.titip) as total_titip'),
                        DB::raw('SUM(p.laku) as total_laku'),
                        DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                        DB::raw('SUM(p.laku * p.harga_jual) as total_omset'),
                        DB::raw('0 as proup_applied'))
                    ->groupBy('p.pedagang_id', 'pdk.nama', 'pdk.tabungan_rate')
                    ->get();
            }
        } elseif ($this->mode === 'nama') {
            // BULANAN mode
            $monthStart = sprintf('%s-%02d-01 00:00:00', $this->year, (int)$this->month);
            $monthEnd = date('Y-m-t 23:59:59', strtotime($monthStart));
            
            $results = DB::table('penjualan as p')
                ->join('pedagang as pdk', 'p.pedagang_id', '=', 'pdk.id')
                ->whereBetween('p.tanggal', [$monthStart, $monthEnd])
                ->whereNull('p.deleted_at')
                ->select(DB::raw('DATE(p.tanggal) as tgl'), 'p.pedagang_id', 'pdk.nama',
                    DB::raw('COUNT(DISTINCT p.produk_id) as total_produk'),
                    DB::raw('SUM(p.titip) as total_titip'),
                    DB::raw('SUM(p.laku) as total_laku'),
                    DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                    DB::raw('SUM(p.laku * p.harga_jual) as total_omset'),
                    DB::raw('pdk.tabungan_rate'),
                    DB::raw('0 as proup_applied'))
                ->groupBy('tgl', 'p.pedagang_id', 'pdk.nama', 'pdk.tabungan_rate')
                ->get();
        } elseif ($this->mode === 'tahunan') {
            // TAHUNAN mode
            $results = DB::table('penjualan as p')
                ->join('pedagang as pdk', 'p.pedagang_id', '=', 'pdk.id')
                ->whereYear('p.tanggal', $this->year)
                ->whereNull('p.deleted_at')
                ->select(DB::raw('MONTH(p.tanggal) as bln'), 'p.pedagang_id', 'pdk.nama',
                    DB::raw('COUNT(DISTINCT p.produk_id) as total_produk'),
                    DB::raw('SUM(p.titip) as total_titip'),
                    DB::raw('SUM(p.laku) as total_laku'),
                    DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                    DB::raw('SUM(p.laku * p.harga_jual) as total_omset'),
                    DB::raw('pdk.tabungan_rate'),
                    DB::raw('0 as proup_applied'))
                ->groupBy('bln', 'p.pedagang_id', 'pdk.nama', 'pdk.tabungan_rate')
                ->get();
        } elseif ($this->mode === 'range') {
            // RANGE mode
            $results = DB::table('penjualan as p')
                ->join('pedagang as pdk', 'p.pedagang_id', '=', 'pdk.id')
                ->whereBetween('p.tanggal', [$this->dateStart.' 00:00:00', $this->dateEnd.' 23:59:59'])
                ->whereNull('p.deleted_at')
                ->select('p.pedagang_id', 'pdk.nama',
                    DB::raw('COUNT(DISTINCT p.produk_id) as total_produk'),
                    DB::raw('SUM(p.titip) as total_titip'),
                    DB::raw('SUM(p.laku) as total_laku'),
                    DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                    DB::raw('SUM(p.laku * p.harga_jual) as total_omset'),
                    DB::raw('pdk.tabungan_rate'),
                    DB::raw('0 as proup_applied'));

            if ($this->rangeType === 'hari') {
                $results->addSelect(DB::raw('DATE(p.tanggal) as tgl'))
                    ->groupBy('tgl', 'p.pedagang_id', 'pdk.nama', 'pdk.tabungan_rate');
            } elseif ($this->rangeType === 'bulan') {
                $results->addSelect(DB::raw('MONTH(p.tanggal) as bln'), DB::raw('YEAR(p.tanggal) as thn'))
                    ->groupBy('thn', 'bln', 'p.pedagang_id', 'pdk.nama', 'pdk.tabungan_rate');
            } else {
                $results->addSelect(DB::raw('YEAR(p.tanggal) as thn'))
                    ->groupBy('thn', 'p.pedagang_id', 'pdk.nama', 'pdk.tabungan_rate');
            }
            
            if ($this->pedagangId) {
                $results->where('p.pedagang_id', $this->pedagangId);
            }
            
            $results = $results->get();
        }

        // AGGREGASI: Group by date/period
        if ($this->mode === 'nama') {
            $results = $results->groupBy('tgl')->map(function ($items, $tgl) {
                return (object)[
                    'tgl' => $tgl,
                    'nama' => '',
                    'total_produk' => $items->sum('total_produk'),
                    'total_titip' => $items->sum('total_titip'),
                    'total_laku' => $items->sum('total_laku'),
                    'total_modal_final' => $items->sum('total_modal'),
                    'total_modal' => $items->sum('total_modal'),
                    'total_kas' => 0,
                    'total_tab_final' => $items->count() * ($items->first()->tabungan_rate ?? 0),
                    'total_setoran' => 0,
                    'total_omset' => $items->sum('total_omset'),
                    'total_laba' => 0,
                    'persen_laku' => $items->sum('total_titip') > 0 ? round(($items->sum('total_laku') / $items->sum('total_titip')) * 100, 1) : 0,
                    'tabungan_rate' => $items->count() > 0 ? $items->first()->tabungan_rate : 0,
                ];
            })->values();
        } elseif ($this->mode === 'tahunan') {
            $results = $results->groupBy('bln')->map(function ($items, $bln) {
                return (object)[
                    'bln' => $bln,
                    'nama' => '',
                    'total_produk' => $items->sum('total_produk'),
                    'total_titip' => $items->sum('total_titip'),
                    'total_laku' => $items->sum('total_laku'),
                    'total_modal_final' => $items->sum('total_modal'),
                    'total_modal' => $items->sum('total_modal'),
                    'total_kas' => 0,
                    'total_tab_final' => $items->count() * ($items->first()->tabungan_rate ?? 0),
                    'total_setoran' => 0,
                    'total_omset' => $items->sum('total_omset'),
                    'total_laba' => 0,
                    'persen_laku' => $items->sum('total_titip') > 0 ? round(($items->sum('total_laku') / $items->sum('total_titip')) * 100, 1) : 0,
                    'tabungan_rate' => $items->count() > 0 ? $items->first()->tabungan_rate : 0,
                ];
            })->values();
        } elseif ($this->mode === 'range') {
            if ($this->rangeType === 'hari') {
                $results = $results->groupBy('tgl')->map(function ($items, $tgl) {
                    return (object)[
                        'tgl' => $tgl,
                        'nama' => '',
                        'total_produk' => $items->sum('total_produk'),
                        'total_titip' => $items->sum('total_titip'),
                        'total_laku' => $items->sum('total_laku'),
                        'total_modal_final' => $items->sum('total_modal'),
                        'total_modal' => $items->sum('total_modal'),
                        'total_kas' => 0,
                        'total_tab_final' => $items->count() * ($items->first()->tabungan_rate ?? 0),
                        'total_setoran' => 0,
                        'total_omset' => $items->sum('total_omset'),
                        'total_laba' => 0,
                        'persen_laku' => $items->sum('total_titip') > 0 ? round(($items->sum('total_laku') / $items->sum('total_titip')) * 100, 1) : 0,
                    ];
                })->values();
            } elseif ($this->rangeType === 'bulan') {
                $results = $results->groupBy(function($item) { return $item->thn . '-' . str_pad((string)$item->bln, 2, '0', STR_PAD_LEFT); })->map(function ($items) {
                    $first = $items->first();
                    return (object)[
                        'bln' => $first->bln,
                        'thn' => $first->thn,
                        'nama' => '',
                        'total_produk' => $items->sum('total_produk'),
                        'total_titip' => $items->sum('total_titip'),
                        'total_laku' => $items->sum('total_laku'),
                        'total_modal_final' => $items->sum('total_modal'),
                        'total_modal' => $items->sum('total_modal'),
                        'total_kas' => 0,
                        'total_tab_final' => $items->count() * ($first->tabungan_rate ?? 0),
                        'total_setoran' => 0,
                        'total_omset' => $items->sum('total_omset'),
                        'total_laba' => 0,
                        'persen_laku' => $items->sum('total_titip') > 0 ? round(($items->sum('total_laku') / $items->sum('total_titip')) * 100, 1) : 0,
                    ];
                })->values();
            } else {
                $results = $results->groupBy('thn')->map(function ($items, $thn) {
                    return (object)[
                        'thn' => $thn,
                        'nama' => '',
                        'total_produk' => $items->sum('total_produk'),
                        'total_titip' => $items->sum('total_titip'),
                        'total_laku' => $items->sum('total_laku'),
                        'total_modal_final' => $items->sum('total_modal'),
                        'total_modal' => $items->sum('total_modal'),
                        'total_kas' => 0,
                        'total_tab_final' => $items->count() * ($items->first()->tabungan_rate ?? 0),
                        'total_setoran' => 0,
                        'total_omset' => $items->sum('total_omset'),
                        'total_laba' => 0,
                        'persen_laku' => $items->sum('total_titip') > 0 ? round(($items->sum('total_laku') / $items->sum('total_titip')) * 100, 1) : 0,
                    ];
                })->values();
            }
        }

        // PROCESSING: Calculate modal, kas, tabungan, setoran, laba
        $this->reportData = $results->map(function ($row) {
            $modal = (float) ($row->total_modal ?? 0);
            $namaPedagang = $row->nama ?? '';
            
            $row->total_modal_final = $row->proup_applied ?? false 
                ? $modal 
                : $this->getAdjustedMerchantModal($modal, (int) ($row->total_produk ?? 0), $namaPedagang);
            
            $row->total_kas = $this->getTieredMerchantKas($row->total_modal_final);
            $row->total_tab_final = (float) ($row->total_tab_final ?? $row->tabungan_rate ?? 0);
            $row->total_setoran = $row->total_modal_final + $row->total_kas + $row->total_tab_final;
            $row->total_omset = (float) ($row->total_omset ?? 0);
            $row->total_laba = $row->total_omset - $row->total_modal_final;
            $row->persen_laku = ($row->total_titip ?? 0) > 0 ? round(($row->total_laku / $row->total_titip) * 100, 1) : 0;
            
            return $row;
        })->toArray();

        // SORTING
        if ($this->direction === 'desc') {
            usort($this->reportData, function ($a, $b) {
                $aVal = $a->{$this->sort} ?? '';
                $bVal = $b->{$this->sort} ?? '';
                return is_numeric($aVal) && is_numeric($bVal) ? $bVal <=> $aVal : strcmp($aVal, $bVal);
            });
        }

        // TOTALS
        $this->totals = [
            'produk' => array_sum(array_column($this->reportData, 'total_produk')),
            'titip' => array_sum(array_column($this->reportData, 'total_titip')),
            'laku' => array_sum(array_column($this->reportData, 'total_laku')),
            'modal' => array_sum(array_column($this->reportData, 'total_modal_final')),
            'kas' => array_sum(array_column($this->reportData, 'total_kas')),
            'tab' => array_sum(array_column($this->reportData, 'total_tab_final')),
            'setoran' => array_sum(array_column($this->reportData, 'total_setoran')),
            'omset' => array_sum(array_column($this->reportData, 'total_omset')),
            'laba' => array_sum(array_column($this->reportData, 'total_laba')),
        ];

        // NOT REPORTED (hanya mode harian) - direct query without cache
        if ($this->mode === 'tanggal' && !$this->isPedagangProductMode) {
            $startDt = $this->selectedDate.' 00:00:00';
            $endDt = $this->selectedDate.' 23:59:59';
            
            $this->notReported = DB::table('pedagang as p')
                ->whereNull('p.deleted_at')
                ->whereNotExists(function ($q) use ($startDt, $endDt) {
                    $q->select(DB::raw(1))
                        ->from('penjualan as pj')
                        ->whereNull('pj.deleted_at')
                        ->whereColumn('pj.pedagang_id', 'p.id')
                        ->whereBetween('pj.tanggal', [$startDt, $endDt]);
                })
                ->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('penjualan as pj2')
                        ->whereNull('pj2.deleted_at')
                        ->whereColumn('pj2.pedagang_id', 'p.id')
                        ->where('pj2.tanggal', '>=', date('Y-m-d', strtotime($this->selectedDate.' -7 days')));
                })
                ->orderBy('p.nama')
                ->pluck('p.nama');
        }
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

    public function sortBy(string $column): void
    {
        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = 'asc';
        }
        $this->loadReportData();
    }
}
