<?php

namespace App\Filament\Pages;

use App\Traits\MerchantFinancialRules;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MerchantSalesPage extends Page
{
    use MerchantFinancialRules;

    public string $selectedDate = '';
    public string $selectedPedagang = '';
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

    public function loadReportData(): void
    {
        $user = Auth::user();
        $pedagangId = $this->selectedPedagang;

        if ($user && $user->owner_type === 'Pedagang') {
            $pedagangId = $user->owner_id;
        }

        $query = DB::table('penjualan as p')
            ->join('pedagang as pdk', 'p.pedagang_id', '=', 'pdk.id')
            ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
            ->whereBetween('p.tanggal', [$this->selectedDate.' 00:00:00', $this->selectedDate.' 23:59:59'])
            ->whereNull('p.deleted_at')
            ->select([
                'pdk.nama as pedagang_nama',
                'pr.nama as produk_nama',
                'p.titip',
                'p.laku',
                'p.sisa_jual',
                'p.modal',
                'p.jual',
                'pdk.tabungan_rate',
            ]);

        if ($pedagangId) {
            $query->where('p.pedagang_id', $pedagangId);
        }

        $results = $query->get();
        $this->reportData = [];

        foreach ($results as $row) {
            $modal = (float) $row->modal;
            $laku = (int) $row->laku;
            $pedagangNama = $row->pedagang_nama;

            $adjustedModal = $this->getAdjustedMerchantModal($modal, $laku, $pedagangNama);
            $kas = $this->getTieredMerchantKas($adjustedModal);
            $tabungan = (float) $row->tabungan_rate;
            $omset = (float) $row->jual;
            $laba = $omset - $adjustedModal;
            $setoran = $adjustedModal + $kas + $tabungan;

            $this->reportData[] = [
                'pedagang' => $pedagangNama,
                'produk' => $row->produk_nama,
                'titip' => $row->titip,
                'laku' => $row->laku,
                'sisa' => $row->sisa_jual,
                'retur' => 0,
                'modal' => $adjustedModal,
                'kas' => $kas,
                'tabungan' => $tabungan,
                'setoran' => $setoran,
                'omset' => $omset,
                'laba' => $laba,
            ];
        }

        $this->totals = [
            'titip' => array_sum(array_column($this->reportData, 'titip')),
            'laku' => array_sum(array_column($this->reportData, 'laku')),
            'modal' => array_sum(array_column($this->reportData, 'modal')),
            'kas' => array_sum(array_column($this->reportData, 'kas')),
            'tabungan' => array_sum(array_column($this->reportData, 'tabungan')),
            'setoran' => array_sum(array_column($this->reportData, 'setoran')),
            'omset' => array_sum(array_column($this->reportData, 'omset')),
            'laba' => array_sum(array_column($this->reportData, 'laba')),
        ];
    }

    public function updatedSelectedDate(): void
    {
        $this->loadReportData();
    }

    public function updatedSelectedPedagang(): void
    {
        $this->loadReportData();
    }
}
