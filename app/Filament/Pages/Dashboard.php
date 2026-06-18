<?php

namespace App\Filament\Pages;

use App\Models\Pedagang;
use App\Models\Produsen;
use App\Models\Produk;
use App\Models\Penjualan;
use App\Models\Saldo;
use App\Models\Transaksi;
use App\Traits\Filament\HasRoleAuthorization;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Support\Facades\DB;

class Dashboard extends BaseDashboard
{
    use HasRoleAuthorization;

    public function getTitle(): string
    {
        $user = auth()->user();
        $userName = $user?->name ? ucwords(strtolower($user->name)) : 'User';
        
        return match ($user?->owner_type) {
            'Admin', 'Pengurus' => 'Dashboard Pasar',
            'Pedagang' => "Dashboard {$userName}",
            'Produsen' => "Dashboard {$userName}",
            default => 'Dashboard',
        };
    }

    public function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\QuickActionsWidget::class,
            \App\Filament\Widgets\DashboardStatsWidget::class,
            \App\Filament\Widgets\AdminOverviewWidget::class,
            \App\Filament\Widgets\OmsetLabaChart::class,
            \App\Filament\Widgets\MerchantOverviewWidget::class,
            \App\Filament\Widgets\PedagangChartWidget::class,
            \App\Filament\Widgets\ProdusenOverviewWidget::class,
        ];
    }
    
    /**
     * Get the user welcome message for the dashboard header
     */
    public function getWelcomeMessage(): string
    {
        $user = auth()->user();
        
        if (!$user) {
            return 'Selamat Datang!';
        }
        
        $name = $user->name ? ucwords(strtolower($user->name)) : '';
        $role = $this->getRoleLabel();
        
        $greeting = $this->getGreeting();
        
        return "{$greeting}, {$name}!";
    }
    
    /**
     * Get time-based greeting
     */
    protected function getGreeting(): string
    {
        $hour = now()->hour;
        
        return match (true) {
            $hour >= 5 && $hour < 12 => 'Selamat Pagi',
            $hour >= 12 && $hour < 15 => 'Selamat Siang',
            $hour >= 15 && $hour < 18 => 'Selamat Sore',
            default => 'Selamat Malam',
        };
    }
}
