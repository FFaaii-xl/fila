<?php

namespace App\Filament\Widgets;

use App\Models\Pedagang;
use App\Models\Produsen;
use App\Models\Produk;
use App\Models\Penjualan;
use App\Models\Saldo;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class DashboardStatsWidget extends BaseWidget
{
    protected function getStats(): array
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
        $totalProduk = Produk::whereNull('deleted_at')->count();
        
        $activePedagangIds = Pedagang::getActivePedagangIds(14);
        $activePedagangCount = count($activePedagangIds);
        
        $todaySales = Penjualan::whereDate('tanggal', today())
            ->whereNull('deleted_at')
            ->where('status', 'Ok')
            ->sum('laku');
        
        $todayTitip = Penjualan::whereDate('tanggal', today())
            ->whereNull('deleted_at')
            ->sum('titip');
        
        $totalSaldoPedagang = Saldo::where('owner_type', 'Pedagang')->sum('jumlah');
        $totalSaldoProdusen = Saldo::where('owner_type', 'Produsen')->sum('jumlah');
        
        $tz = 'Asia/Jakarta';
        $ninetyDaysAgo = now($tz)->subDays(90)->toDateString();
        
        $avgPerf = DB::table('sales_summaries')
            ->where('date', '>=', $ninetyDaysAgo)
            ->selectRaw('AVG(total_laku / NULLIF(total_titip, 0)) * 100 as avg_perf')
            ->value('avg_perf') ?? 0;

        return [
            Stat::make('Pedagang', number_format($totalPedagang, 0, ',', '.'))
                ->description("{$activePedagangCount} aktif")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('emerald'),
                
            Stat::make('Produsen', number_format($totalProdusen, 0, ',', '.'))
                ->description(number_format($totalProduk, 0, ',', '.').' produk')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('blue'),
                
            Stat::make('Sales Hari Ini', number_format($todaySales, 0, ',', '.'))
                ->description(number_format($todayTitip, 0, ',', '.').' titip')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('amber'),
                
            Stat::make('Perf 90 Hari', number_format((float)$avgPerf, 1, ',', '.').'%')
                ->description('Rata-rata')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color((float)$avgPerf > 85 ? 'success' : ((float)$avgPerf >= 60 ? 'warning' : 'danger')),
                
            Stat::make('Saldo Pedagang', alignUang($totalSaldoPedagang))
                ->color('success'),
                
            Stat::make('Saldo Produsen', alignUang($totalSaldoProdusen))
                ->color('info'),
        ];
    }

    protected function getPedagangStats(): array
    {
        $user = auth()->user();
        $pedagangId = $user?->owner_id;

        if (!$pedagangId) {
            return [];
        }

        $pedagang = Pedagang::find($pedagangId);
        
        $todaySales = Penjualan::where('pedagang_id', $pedagangId)
            ->whereDate('tanggal', today())
            ->whereNull('deleted_at')
            ->where('status', 'Ok')
            ->sum('laku');
        
        $todayTitip = Penjualan::where('pedagang_id', $pedagangId)
            ->whereDate('tanggal', today())
            ->whereNull('deleted_at')
            ->sum('titip');
        
        $saldo = Saldo::where('owner_type', 'Pedagang')
            ->where('owner_id', $pedagangId)
            ->value('jumlah') ?? 0;
        
        $tabungan = $pedagang?->tabungan ?? 0;
        
        $tabunganRate = $pedagang?->tabungan_rate ?? 0;

        return [
            Stat::make('Sales Hari Ini', number_format($todaySales, 0, ',', '.'))
                ->description(number_format($todayTitip, 0, ',', '.').' titip')
                ->color('emerald'),
                
            Stat::make('Saldo Kas', alignUang($saldo))
                ->description(number_format($tabunganRate, 0, ',', '.').'/hari')
                ->color('success'),
                
            Stat::make('Tabungan', alignUang($tabungan))
                ->color('info'),
        ];
    }

    protected function getProdusenStats(): array
    {
        $user = auth()->user();
        $produsenId = $user?->owner_id;

        if (!$produsenId) {
            return [];
        }

        $produsen = Produsen::find($produsenId);
        
        $totalProduk = Produk::where('produsen_id', $produsenId)
            ->whereNull('deleted_at')
            ->count();
        
        $todayTitip = Penjualan::whereIn('produk_id', function ($q) use ($produsenId) {
            $q->select('id')->from('produk')->where('produsen_id', $produsenId);
        })
            ->whereDate('tanggal', today())
            ->whereNull('deleted_at')
            ->sum('titip');
        
        $todayLaku = Penjualan::whereIn('produk_id', function ($q) use ($produsenId) {
            $q->select('id')->from('produk')->where('produsen_id', $produsenId);
        })
            ->whereDate('tanggal', today())
            ->whereNull('deleted_at')
            ->sum('laku');
        
        $saldo = Saldo::where('owner_type', 'Produsen')
            ->where('owner_id', $produsenId)
            ->value('jumlah') ?? 0;
        
        $tabungan = $produsen?->tabungan ?? 0;

        return [
            Stat::make('Produk Saya', number_format($totalProduk, 0, ',', '.'))
                ->color('blue'),
                
            Stat::make('Titip Hari Ini', number_format($todayTitip, 0, ',', '.'))
                ->description(number_format($todayLaku, 0, ',', '.').' laku')
                ->color('amber'),
                
            Stat::make('Saldo', alignUang($saldo))
                ->description(alignUang($tabungan).' tabungan')
                ->color('success'),
        ];
    }
}
