<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Login;
use App\Filament\Pages\MerchantSalesPage;
use App\Filament\Pages\ProducerSalesPage;
use App\Filament\Pages\MutasiHarianPage;
use App\Filament\Pages\CatatanSetoranPage;
use App\Filament\Pages\Admin\LaporanPage;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->colors([
                'primary' => '#10b981',
                'gray' => '#6b7280',
                'danger' => '#ef4444',
                'success' => '#22c55e',
                'warning' => '#f59e0b',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                LaporanPage::class,
            ])
            ->widgets([
                // \Filament\Widgets\AccountWidget::class,
            ])
            ->profile()
            ->middleware([
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
            ])
            ->authMiddleware([
                \Filament\Http\Middleware\Authenticate::class,
            ])
            ->brandName('Citroroso');
    }
}
