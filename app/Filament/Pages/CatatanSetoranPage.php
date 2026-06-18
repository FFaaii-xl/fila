<?php

namespace App\Filament\Pages;

use App\Models\Pedagang;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CatatanSetoranPage extends Page
{
    public string $selectedMonth = '';
    public string $selectedPedagang = '';
    public array $setoranList = [];
    public array $totals = [];

    public function mount(): void
    {
        $this->selectedMonth = date('Y-m');
        $this->loadSetoran();
    }

    public function getView(): string
    {
        return 'filament.pages.catatan-setoran';
    }

    public function loadSetoran(): void
    {
        $user = Auth::user();
        $pedagangId = $this->selectedPedagang;

        if ($user && $user->owner_type === 'Pedagang') {
            $pedagangId = $user->owner_id;
        }

        $monthStart = $this->selectedMonth . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $query = DB::table('penjualan as p')
            ->join('pedagang as pdk', 'p.pedagang_id', '=', 'pdk.id')
            ->join('produk as pr', 'p.produk_id', '=', 'pr.id')
            ->whereBetween('p.tanggal', [$monthStart, $monthEnd])
            ->whereNull('p.deleted_at')
            ->where('p.status', 'Ok')
            ->select([
                'pdk.id as pedagang_id',
                'pdk.nama as pedagang_nama',
                DB::raw('DATE(p.tanggal) as tanggal'),
                DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                DB::raw('SUM(p.laku * p.harga_jual) as total_omset'),
                DB::raw('SUM(p.laku) as total_laku'),
                DB::raw('AVG(pdk.tabungan_rate) as tabungan_rate'),
            ])
            ->groupBy('pdk.id', 'pdk.nama', DB::raw('DATE(p.tanggal)'));

        if ($pedagangId) {
            $query->where('p.pedagang_id', $pedagangId);
        }

        $results = $query->get();
        $this->setoranList = [];

        foreach ($results as $row) {
            $modal = (float) $row->total_modal;
            $kas = 1500;
            $tabungan = (float) $row->tabungan_rate;
            $setoran = $modal + $kas + $tabungan;

            $this->setoranList[] = [
                'pedagang' => $row->pedagang_nama,
                'tanggal' => $row->tanggal,
                'modal' => $modal,
                'kas' => $kas,
                'tabungan' => $tabungan,
                'setoran' => $setoran,
                'omset' => (float) $row->total_omset,
                'laku' => $row->total_laku,
            ];
        }

        $this->totals = [
            'modal' => array_sum(array_column($this->setoranList, 'modal')),
            'kas' => array_sum(array_column($this->setoranList, 'kas')),
            'tabungan' => array_sum(array_column($this->setoranList, 'tabungan')),
            'setoran' => array_sum(array_column($this->setoranList, 'setoran')),
            'omset' => array_sum(array_column($this->setoranList, 'omset')),
        ];
    }

    public function updatedSelectedMonth(): void
    {
        $this->loadSetoran();
    }

    public function updatedSelectedPedagang(): void
    {
        $this->loadSetoran();
    }
}
