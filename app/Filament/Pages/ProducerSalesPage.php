<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProducerSalesPage extends Page
{
    public string $mode = 'tanggal';
    public string $selectedDate = '';
    public string $dateStart = '';
    public string $dateEnd = '';
    public ?string $selectedProdusen = null;
    public string $selectedMonth = '';
    public string $selectedYear = '';
    public string $rangeType = 'hari';

    public array $groupedData = [];
    public array $totals = [];
    public array $produsenList = [];
    public array $producerDetailData = [];
    public bool $isProdusenPedagangMode = false;
    public bool $isProdusenOnlyMode = false;

    public function mount(): void
    {
        // Direct query without cache to avoid serialization issues
        $latestTanggal = DB::table('penjualan')
            ->whereNull('deleted_at')
            ->max('tanggal');

        $this->selectedDate = $latestTanggal ? date('Y-m-d', strtotime($latestTanggal)) : date('Y-m-d');
        $this->dateStart = $this->selectedDate;
        $this->dateEnd = $this->selectedDate;
        $this->selectedMonth = $latestTanggal ? date('m', strtotime($latestTanggal)) : date('m');
        $this->selectedYear = $latestTanggal ? date('Y', strtotime($latestTanggal)) : date('Y');

        $this->loadReportData();
    }

    public function getHeading(): string
    {
        return 'Laporan Penjualan Produsen';
    }

    public function getView(): string
    {
        return 'filament.pages.producer-sales';
    }

    public function loadReportData(): void
    {
        $user = Auth::user();
        
        // Reset states
        $this->isProdusenPedagangMode = false;
        $this->isProdusenOnlyMode = false;

        // Produsen user: lock to self
        if ($user && $user->owner_type === 'Produsen') {
            $this->selectedProdusen = $user->owner_id;
            $this->isProdusenPedagangMode = true;
            $this->isProdusenOnlyMode = true;
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
        }

        // Load produsen list - direct query without cache
        $this->produsenList = DB::table('produsen')
            ->whereNull('deleted_at')
            ->when($user && $user->owner_type === 'Produsen', fn ($q) => $q->where('id', $user->owner_id))
            ->orderBy('nama')
            ->get()
            ->toArray();

        // DATA QUERIES
        $rawData = collect();
        $detailData = collect();

        if ($this->mode === 'tanggal') {
            // HARIAN mode
            $query = DB::table('penjualan as p')
                ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
                ->join('produsen as ps', 'pr.produsen_id', '=', 'ps.id')
                ->join('pedagang as pd', 'p.pedagang_id', '=', 'pd.id')
                ->whereNull('p.deleted_at')
                ->whereBetween('p.tanggal', [$this->selectedDate.' 00:00:00', $this->selectedDate.' 23:59:59'])
                ->select([
                    'ps.id as produsen_id',
                    'ps.nama as produsen_nama',
                    'pr.nama as produk_nama',
                    'pd.nama as pedagang_nama',
                    DB::raw('SUM(p.titip) as total_titip'),
                    DB::raw('SUM(p.laku) as total_laku'),
                    DB::raw('SUM(p.laku * p.harga_beli) as total_omset'),
                    DB::raw('DATE(p.tanggal) as tgl'),
                    DB::raw('1 as days_count'),
                ]);

            if ($user && $user->owner_type === 'Produsen') {
                $query->where('ps.id', $user->owner_id);
            } elseif ($this->selectedProdusen) {
                $query->where('ps.id', $this->selectedProdusen);
            }

            $rawData = $query->groupBy('ps.id', 'ps.nama', 'pr.nama', 'pd.nama', 'tgl')->get();
        } else {
            // BULANAN / TAHUNAN / RANGE modes
            if ($this->isProdusenOnlyMode) {
                // PRODUSEN SIMPLIFIED: Query langsung dari penjualan
                if ($this->mode === 'nama') {
                    // MODE BULANAN
                    $monthStart = sprintf('%s-%02d-01 00:00:00', $this->selectedYear, (int)$this->selectedMonth);
                    $monthEnd = date('Y-m-t', strtotime($monthStart)) . ' 23:59:59';

                    $summaryQuery = DB::table('penjualan as p')
                        ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
                        ->join('produsen as ps', 'pr.produsen_id', '=', 'ps.id')
                        ->whereNull('p.deleted_at')
                        ->where('p.tanggal', '>=', $monthStart)
                        ->where('p.tanggal', '<=', $monthEnd)
                        ->where('ps.id', $user->owner_id)
                        ->select([
                            'ps.id as produsen_id',
                            'ps.nama as produsen_nama',
                            'pr.nama as produk_nama',
                            DB::raw('SUM(p.titip) as total_titip'),
                            DB::raw('SUM(p.laku) as total_laku'),
                            DB::raw('SUM(p.laku * p.harga_beli) as total_omset'),
                            DB::raw('COUNT(DISTINCT DATE(p.tanggal)) as days_count'),
                        ])
                        ->groupBy('ps.id', 'ps.nama', 'pr.nama');

                    $rawData = $summaryQuery->get();

                    // Detail per produk per tanggal
                    $detailData = DB::table('penjualan as p')
                        ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
                        ->join('produsen as ps', 'pr.produsen_id', '=', 'ps.id')
                        ->whereNull('p.deleted_at')
                        ->where('p.tanggal', '>=', $monthStart)
                        ->where('p.tanggal', '<=', $monthEnd)
                        ->where('ps.id', $user->owner_id)
                        ->select([
                            'pr.nama as produk_nama',
                            DB::raw('DATE(p.tanggal) as tgl'),
                            DB::raw('SUM(p.titip) as total_titip'),
                            DB::raw('SUM(p.laku) as total_laku'),
                            DB::raw('SUM(p.laku * p.harga_beli) as total_omset'),
                        ])
                        ->groupBy('pr.nama', 'tgl')
                        ->get();
                } elseif ($this->mode === 'tahunan') {
                    // MODE TAHUNAN
                    $summaryQuery = DB::table('penjualan as p')
                        ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
                        ->join('produsen as ps', 'pr.produsen_id', '=', 'ps.id')
                        ->whereNull('p.deleted_at')
                        ->whereYear('p.tanggal', $this->selectedYear)
                        ->where('ps.id', $user->owner_id)
                        ->select([
                            'ps.id as produsen_id',
                            'ps.nama as produsen_nama',
                            'pr.nama as produk_nama',
                            DB::raw('SUM(p.titip) as total_titip'),
                            DB::raw('SUM(p.laku) as total_laku'),
                            DB::raw('SUM(p.laku * p.harga_beli) as total_omset'),
                            DB::raw('COUNT(DISTINCT MONTH(p.tanggal)) as days_count'),
                        ])
                        ->groupBy('ps.id', 'ps.nama', 'pr.nama');
                    $rawData = $summaryQuery->get();

                    $detailData = DB::table('penjualan as p')
                        ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
                        ->join('produsen as ps', 'pr.produsen_id', '=', 'ps.id')
                        ->whereNull('p.deleted_at')
                        ->whereYear('p.tanggal', $this->selectedYear)
                        ->where('ps.id', $user->owner_id)
                        ->select([
                            'pr.nama as produk_nama',
                            DB::raw('MONTH(p.tanggal) as bln'),
                            DB::raw('SUM(p.titip) as total_titip'),
                            DB::raw('SUM(p.laku) as total_laku'),
                            DB::raw('SUM(p.laku * p.harga_beli) as total_omset'),
                        ])
                        ->groupBy('pr.nama', 'bln')
                        ->get();
                } else {
                    // MODE RANGE
                    $summaryQuery = DB::table('penjualan as p')
                        ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
                        ->join('produsen as ps', 'pr.produsen_id', '=', 'ps.id')
                        ->leftJoin('pedagang as pd', 'p.pedagang_id', '=', 'pd.id')
                        ->whereNull('p.deleted_at')
                        ->whereBetween('p.tanggal', [$this->dateStart . ' 00:00:00', $this->dateEnd . ' 23:59:59'])
                        ->where('ps.id', $user->owner_id)
                        ->select([
                            'ps.id as produsen_id',
                            'ps.nama as produsen_nama',
                            'pr.nama as produk_nama',
                            DB::raw('SUM(p.titip) as total_titip'),
                            DB::raw('SUM(p.laku) as total_laku'),
                            DB::raw('SUM(p.laku * p.harga_beli) as total_omset'),
                            DB::raw('COUNT(DISTINCT DATE(p.tanggal)) as days_count'),
                        ])
                        ->groupBy('ps.id', 'ps.nama', 'pr.nama');

                    $rawData = $summaryQuery->get();

                    $detailData = DB::table('penjualan as p')
                        ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
                        ->join('produsen as ps', 'pr.produsen_id', '=', 'ps.id')
                        ->leftJoin('pedagang as pd', 'p.pedagang_id', '=', 'pd.id')
                        ->whereNull('p.deleted_at')
                        ->whereBetween('p.tanggal', [$this->dateStart . ' 00:00:00', $this->dateEnd . ' 23:59:59'])
                        ->where('ps.id', $user->owner_id)
                        ->select([
                            'pr.nama as produk_nama',
                            'pd.nama as pedagang_nama',
                            DB::raw('DATE(p.tanggal) as tgl'),
                            DB::raw('SUM(p.titip) as total_titip'),
                            DB::raw('SUM(p.laku) as total_laku'),
                            DB::raw('SUM(p.laku * p.harga_beli) as total_omset'),
                        ])
                        ->groupBy('pr.nama', 'pd.nama', 'tgl')
                        ->get();
                }
            } else {
                // ADMIN/PENGURUS: Standard query from sales_summaries
                $summaryQuery = DB::table('sales_summaries as ss')
                    ->join('produk as pr', 'ss.type_id', '=', 'pr.id')
                    ->join('produsen as ps', 'pr.produsen_id', '=', 'ps.id')
                    ->where('ss.type', 'produk')
                    ->select([
                        'ps.id as produsen_id',
                        'ps.nama as produsen_nama',
                        'pr.nama as produk_nama',
                        DB::raw('SUM(ss.total_titip) as total_titip'),
                        DB::raw('SUM(ss.total_laku) as total_laku'),
                        DB::raw('SUM(ss.total_modal) as total_omset'),
                        DB::raw('COUNT(DISTINCT DATE(ss.date)) as days_count'),
                    ])
                    ->groupBy('ps.id', 'ps.nama', 'pr.nama');

                if ($this->mode === 'tahunan' || ($this->mode === 'range' && $this->rangeType === 'bulan')) {
                    $summaryQuery->addSelect(DB::raw('MONTH(ss.date) as bln'), DB::raw('YEAR(ss.date) as thn'))->groupBy('thn', 'bln');
                } elseif ($this->mode === 'range' && $this->rangeType === 'tahun') {
                    $summaryQuery->addSelect(DB::raw('YEAR(ss.date) as thn'))->groupBy('thn');
                } else {
                    $summaryQuery->addSelect(DB::raw('DATE(ss.date) as tgl'))->groupBy('tgl');
                }

                if ($this->selectedProdusen) {
                    $summaryQuery->where('ps.id', $this->selectedProdusen);
                }

                if ($this->mode === 'tahunan') {
                    $summaryQuery->whereBetween('ss.date', [$this->selectedYear . '-01-01', $this->selectedYear . '-12-31']);
                } elseif ($this->mode === 'range') {
                    $summaryQuery->whereBetween('ss.date', [$this->dateStart, $this->dateEnd]);
                } else {
                    $monthStart = sprintf('%s-%02d-01', $this->selectedYear, (int)$this->selectedMonth);
                    $monthEnd = date('Y-m-t', strtotime($monthStart));
                    $summaryQuery->whereBetween('ss.date', [$monthStart, $monthEnd]);
                }
                $rawData = $summaryQuery->get();
            }
        }

        // Store detail data
        $this->producerDetailData = $detailData->toArray();

        // GROUP DATA
        if ($this->isProdusenOnlyMode) {
            // PRODUSEN LOGIN: Group only by produk (SIMPLIFIED)
            $this->groupedData = $rawData->groupBy('produk_nama')->map(function ($items, $produkName) {
                return [
                    'summary' => [
                        'titip' => $items->sum('total_titip'),
                        'laku' => $items->sum('total_laku'),
                        'omset' => $items->sum('total_omset'),
                        'hari_jualan' => $this->mode === 'range' ? $items->unique('tgl')->count() : ($this->mode === 'tanggal' ? 1 : $items->unique('tgl')->count()),
                    ],
                    'details' => $items->sortBy('tgl')->values()->toArray(),
                    'produsen_nama' => $items->first()->produsen_nama ?? '',
                ];
            })->toArray();
        } else {
            // ADMIN/PENGURUS: Keep original nested structure
            $groupKey = 'produsen_nama';
            if ($this->mode === 'nama' || ($this->mode === 'range' && $this->selectedProdusen) || ($this->mode === 'tahunan' && $this->selectedProdusen)) {
                $groupKey = 'produk_nama';
            }
            if ($this->isProdusenPedagangMode) {
                $groupKey = 'pedagang_nama';
            }

            if ($this->mode === 'tahunan') {
                if ($this->selectedProdusen) {
                    $this->groupedData = $rawData->groupBy('produk_nama')->map(function ($productItems) {
                        return [
                            'summary' => [
                                'titip' => $productItems->sum('total_titip'),
                                'laku' => $productItems->sum('total_laku'),
                                'omset' => $productItems->sum('total_omset'),
                                'hari_jualan' => $productItems->sum('days_count'),
                            ],
                            'details' => $productItems->sortBy(fn($i) => ($i->thn ?? 0) . '-' . str_pad((string)($i->bln ?? 0), 2, '0', STR_PAD_LEFT))->values()->toArray(),
                        ];
                    })->toArray();
                } else {
                    $this->groupedData = $rawData->groupBy('produsen_nama')->map(function ($producerItems) {
                        return [
                            'summary' => [
                                'titip' => $producerItems->sum('total_titip'),
                                'laku' => $producerItems->sum('total_laku'),
                                'omset' => $producerItems->sum('total_omset'),
                                'hari_jualan' => $producerItems->sum('days_count'),
                            ],
                            'products' => $producerItems->groupBy('produk_nama')->map(fn ($productItems) => [
                                'summary' => [
                                    'titip' => $productItems->sum('total_titip'),
                                    'laku' => $productItems->sum('total_laku'),
                                    'omset' => $productItems->sum('total_omset'),
                                    'hari_jualan' => $productItems->sum('days_count'),
                                ],
                                'details' => $productItems->sortBy(fn($i) => ($i->thn ?? 0) . '-' . str_pad((string)($i->bln ?? 0), 2, '0', STR_PAD_LEFT))->values()->toArray(),
                            ])->toArray(),
                        ];
                    })->toArray();
                }
            } elseif ($this->mode === 'nama') {
                if ($this->selectedProdusen) {
                    $this->groupedData = $rawData->groupBy('produk_nama')->map(fn ($items) => [
                        'summary' => [
                            'titip' => $items->sum('total_titip'),
                            'laku' => $items->sum('total_laku'),
                            'omset' => $items->sum('total_omset'),
                            'hari_jualan' => $items->unique('tgl')->count(),
                        ],
                        'details' => $items->sortBy('tgl')->values()->toArray(),
                    ])->toArray();
                } else {
                    $this->groupedData = $rawData->groupBy('produsen_nama')->map(fn ($producerItems) => [
                        'summary' => [
                            'titip' => $producerItems->sum('total_titip'),
                            'laku' => $producerItems->sum('total_laku'),
                            'omset' => $producerItems->sum('total_omset'),
                            'hari_jualan' => $producerItems->unique('tgl')->count(),
                        ],
                        'products' => $producerItems->groupBy('produk_nama')->map(fn ($productItems) => [
                            'summary' => [
                                'titip' => $productItems->sum('total_titip'),
                                'laku' => $productItems->sum('total_laku'),
                                'omset' => $productItems->sum('total_omset'),
                                'hari_jualan' => $productItems->unique('tgl')->count(),
                            ],
                            'details' => $productItems->sortBy('tgl')->values()->toArray(),
                        ])->toArray(),
                    ])->toArray();
                }
            } else {
                $this->groupedData = $rawData->groupBy($groupKey)->map(fn ($items) => [
                    'summary' => [
                        'titip' => $items->sum('total_titip'),
                        'laku' => $items->sum('total_laku'),
                        'omset' => $items->sum('total_omset'),
                        'hari_jualan' => $this->mode === 'range' ? $items->unique('tgl')->count() : 1,
                    ],
                    'details' => $items->sortBy('tgl')->values()->toArray(),
                ])->toArray();
            }
        }

        // TOTALS
        $this->totals = [
            'titip' => $rawData->sum('total_titip'),
            'laku' => $rawData->sum('total_laku'),
            'omset' => $rawData->sum('total_omset'),
        ];
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

    public function updatedSelectedProdusen(): void
    {
        $this->loadReportData();
    }

    public function updatedSelectedMonth(): void
    {
        $this->loadReportData();
    }

    public function updatedSelectedYear(): void
    {
        $this->loadReportData();
    }

    // Property accessor for mode
    public function getModeProperty(): string
    {
        return request('mode', 'tanggal');
    }
}
