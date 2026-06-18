<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\Login;
use Hammadzafar05\MobileBottomNav\MobileBottomNav;
use Hammadzafar05\MobileBottomNav\MobileBottomNavItem;

class PedagangPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('pedagang')
            ->path('pedagang')
            ->login(Login::class)
            ->colors([
                'primary' => '#10b981',
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
                            ->url(fn () => '/pedagang')
                            ->icon('heroicon-o-home')
                            ->sort(1),
                        MobileBottomNavItem::make('Penjualan')
                            ->label('Penjualan')
                            ->url(fn () => '/pedagang/penjualan')
                            ->icon('heroicon-o-shopping-bag')
                            ->sort(2),
                        MobileBottomNavItem::make('Mutasi')
                            ->label('Mutasi')
                            ->url(fn () => '/pedagang/mutasi')
                            ->icon('heroicon-o-list-bullet')
                            ->sort(3),
                        MobileBottomNavItem::make('Akun')
                            ->label('Akun')
                            ->url(fn () => '/pedagang/profile')
                            ->icon('heroicon-o-user-circle')
                            ->sort(4),
                    ])
            )
            ->middleware([
                \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
            ])
            ->brandName('Citroroso - Pedagang');
    }
}
