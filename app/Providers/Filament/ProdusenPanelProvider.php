<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Login;
use Hammadzafar05\MobileBottomNav\MobileBottomNav;
use Hammadzafar05\MobileBottomNav\MobileBottomNavItem;

class ProdusenPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('produsen')
            ->path('produsen')
            ->login(Login::class)
            ->colors([
                'primary' => '#3b82f6',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->plugin(
                MobileBottomNav::make()
                    ->items([
                        MobileBottomNavItem::make('Beranda')
                            ->label('Beranda')
                            ->url(fn () => '/produsen')
                            ->icon('heroicon-o-home')
                            ->sort(1),
                        MobileBottomNavItem::make('Produk')
                            ->label('Produk')
                            ->url(fn () => '/produsen/produk')
                            ->icon('heroicon-o-archive-box')
                            ->sort(2),
                        MobileBottomNavItem::make('Riwayat')
                            ->label('Riwayat')
                            ->url(fn () => '/produsen/riwayat')
                            ->icon('heroicon-o-clock')
                            ->sort(3),
                        MobileBottomNavItem::make('Akun')
                            ->label('Akun')
                            ->url(fn () => '/produsen/profile')
                            ->icon('heroicon-o-user-circle')
                            ->sort(4),
                    ])
            )
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
            ->brandName('Citroroso - Produsen');
    }
}
