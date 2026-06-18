<?php

namespace App\Filament\Pages;

use App\Models\Pedagang;
use App\Models\Produsen;
use App\Models\Produk;
use App\Models\Penjualan;
use App\Models\Saldo;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string
    {
        $user = auth()->user();
        return match ($user?->owner_type) {
            'Admin', 'Pengurus' => 'Dashboard Admin',
            'Pedagang' => 'Dashboard Pedagang',
            'Produsen' => 'Dashboard Produsen',
            default => 'Dashboard',
        };
    }

    public function getHeaderWidgets(): array
    {
        $user = auth()->user();
        $ownerType = $user?->owner_type ?? 'Admin';

        return match ($ownerType) {
            'Admin', 'Pengurus' => $this->getAdminStats(),
            'Pedagang' => $this->getPedagangStats(),
            'Produsen' => $this->getProdusenStats(),
            default => [],
        };
    }

    protected function getAdminStats(): array
    {
        $totalPedagang = Pedagang::count();
        $totalProdusen = Produsen::count();
        $totalProduk = Produk::count();
        $todaySales = Penjualan::whereDate('tanggal', today())->whereNull('deleted_at')->sum('laku');
        $totalSaldoPedagang = Saldo::where('owner_type', 'Pedagang')->sum('jumlah');
        $totalSaldoProdusen = Saldo::where('owner_type', 'Produsen')->sum('jumlah');

        return [
            StatsOverviewWidget::make([
                Stat::make('Pedagang', number_format($totalPedagang, 0, ',', '.'))
                    ->color('emerald'),
                Stat::make('Produsen', number_format($totalProdusen, 0, ',', '.'))
                    ->color('blue'),
                Stat::make('Produk', number_format($totalProduk, 0, ',', '.'))
                    ->color('purple'),
                Stat::make('Sales Hari Ini', number_format($todaySales, 0, ',', '.'))
                    ->color('amber'),
                Stat::make('Saldo Pedagang', alignUang($totalSaldoPedagang))
                    ->color('success'),
                Stat::make('Saldo Produsen', alignUang($totalSaldoProdusen))
                    ->color('info'),
            ]),
        ];
    }

    protected function getPedagangStats(): array
    {
        $user = auth()->user();
        $pedagangId = $user?->owner_id;

        if (!$pedagangId) {
            return [];
        }

        $todaySales = Penjualan::where('pedagang_id', $pedagangId)
            ->whereDate('tanggal', today())
            ->whereNull('deleted_at')
            ->sum('laku');

        $saldo = Saldo::where('owner_type', 'Pedagang')
            ->where('owner_id', $pedagangId)
            ->value('jumlah') ?? 0;

        return [
            StatsOverviewWidget::make([
                Stat::make('Sales Hari Ini', number_format($todaySales, 0, ',', '.'))
                    ->color('emerald'),
                Stat::make('Saldo', alignUang($saldo))
                    ->color('success'),
            ]),
        ];
    }

    protected function getProdusenStats(): array
    {
        $user = auth()->user();
        $produsenId = $user?->owner_id;

        if (!$produsenId) {
            return [];
        }

        $totalProduk = Produk::where('produsen_id', $produsenId)->whereNull('deleted_at')->count();
        $todayTitip = Penjualan::whereIn('produk_id', function ($q) use ($produsenId) {
            $q->select('id')->from('produk')->where('produsen_id', $produsenId);
        })
            ->whereDate('tanggal', today())
            ->whereNull('deleted_at')
            ->sum('titip');

        $saldo = Saldo::where('owner_type', 'Produsen')
            ->where('owner_id', $produsenId)
            ->value('jumlah') ?? 0;

        return [
            StatsOverviewWidget::make([
                Stat::make('Produk Saya', number_format($totalProduk, 0, ',', '.'))
                    ->color('blue'),
                Stat::make('Titip Hari Ini', number_format($todayTitip, 0, ',', '.'))
                    ->color('amber'),
                Stat::make('Saldo', alignUang($saldo))
                    ->color('success'),
            ]),
        ];
    }
}
