<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\Auth\Authenticatable;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = 0;
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getViewData(): array
    {
        return [
            'actions' => [
                [
                    'label' => 'Upload Penjualan',
                    'icon' => 'heroicon-o-cloud-arrow-up',
                    'color' => 'emerald',
                    'url' => '/admin/upload-penjualan',
                    'description' => 'Upload data Excel',
                ],
                [
                    'label' => 'Mutasi Harian',
                    'icon' => 'heroicon-o-document-chart-bar',
                    'color' => 'blue',
                    'url' => '/admin/mutasi-harian',
                    'description' => 'Riwayat transaksi',
                ],
                [
                    'label' => 'Tabungan',
                    'icon' => 'heroicon-o-banknotes',
                    'color' => 'amber',
                    'url' => '/admin/tabungan',
                    'description' => 'Kelola tabungan',
                ],
                [
                    'label' => 'Cetak Nota',
                    'icon' => 'heroicon-o-printer',
                    'color' => 'indigo',
                    'url' => '/admin/nota-penjualan',
                    'description' => 'Print nota',
                ],
                [
                    'label' => 'Laporan',
                    'icon' => 'heroicon-o-document-text',
                    'color' => 'purple',
                    'url' => '/admin/laporan',
                    'description' => 'Laporan lengkap',
                ],
                [
                    'label' => 'Pengaturan',
                    'icon' => 'heroicon-o-cog-6-tooth',
                    'color' => 'gray',
                    'url' => '/admin/settings',
                    'description' => 'Konfigurasi',
                ],
            ],
        ];
    }
    
    protected function getView(): string
    {
        return 'filament.widgets.quick-actions-widget';
    }
}
