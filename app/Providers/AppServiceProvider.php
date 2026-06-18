<?php

namespace App\Providers;

use App\Models\Pedagang;
use App\Models\Penjualan;
use App\Models\Produsen;
use App\Observers\OwnerObserver;
use App\Observers\PedagangObserver;
use App\Observers\ProdusenObserver;
use App\Observers\PenjualanObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // OwnerObserver for general owner operations
        Pedagang::observe(OwnerObserver::class);
        Produsen::observe(OwnerObserver::class);
        
        // Auto-create User observer
        Pedagang::observe(PedagangObserver::class);
        Produsen::observe(ProdusenObserver::class);
        
        // Penjualan observer
        Penjualan::observe(PenjualanObserver::class);
    }
}
