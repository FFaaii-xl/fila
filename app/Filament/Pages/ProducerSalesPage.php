<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProducerSalesPage extends Page
{
    public string $selectedDate = '';
    public string $selectedProdusen = '';
    public array $reportData = [];
    public array $totals = [];

    public function mount(): void
    {
        $latest = DB::table('penjualan')
            ->whereNull('deleted_at')
            ->latest('tanggal')
            ->first();

        $this->selectedDate = $latest ? date('Y-m-d', strtotime($latest->tanggal)) : date('Y-m-d');
        $this->loadReportData();
    }

    public function getView(): string
    {
        return 'filament.pages.producer-sales';
    }

    public function loadReportData(): void
    {
        $user = Auth::user();
        $produsenId = $this->selectedProdusen;

        if ($user && $user->owner_type === 'Produsen') {
            $produsenId = $user->owner_id;
        }

        $query = DB::table('penjualan as p')
            ->join('produsen as prd', 'p.produsen_id', '=', 'prd.id')
            ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
            ->join('pedagang as pdk', 'p.pedagang_id', '=', 'pdk.id')
            ->whereBetween('p.tanggal', [$this->selectedDate.' 00:00:00', $this->selectedDate.' 23:59:59'])
            ->whereNull('p.deleted_at')
            ->select([
                'prd.nama as produsen_nama',
                'pr.nama as produk_nama',
                'pdk.nama as pedagang_nama',
                'p.titip',
                'p.laku',
                'p.sisa_jual',
                'p.retur',
                'p.modal',
                'p.jual',
            ]);

        if ($produsenId) {
            $query->where('p.produsen_id', $produsenId);
        }

        $results = $query->get();
        $this->reportData = [];

        foreach ($results as $row) {
            $modal = (float) $row->modal;
            $omset = (float) $row->jual;
            $laba = $omset - $modal;

            $this->reportData[] = [
                'produsen' => $row->produsen_nama,
                'produk' => $row->produk_nama,
                'pedagang' => $row->pedagang_nama,
                'titip' => $row->titip,
                'laku' => $row->laku,
                'sisa' => $row->sisa_jual,
                'retur' => $row->retur,
                'modal' => $modal,
                'omset' => $omset,
                'laba' => $laba,
            ];
        }

        $this->totals = [
            'titip' => array_sum(array_column($this->reportData, 'titip')),
            'laku' => array_sum(array_column($this->reportData, 'laku')),
            'modal' => array_sum(array_column($this->reportData, 'modal')),
            'omset' => array_sum(array_column($this->reportData, 'omset')),
            'laba' => array_sum(array_column($this->reportData, 'laba')),
        ];
    }

    public function updatedSelectedDate(): void
    {
        $this->loadReportData();
    }

    public function updatedSelectedProdusen(): void
    {
        $this->loadReportData();
    }
}
