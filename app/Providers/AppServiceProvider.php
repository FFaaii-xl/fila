<?php

namespace App\Providers;

use App\Models\Pedagang;
use App\Models\Penjualan;
use App\Models\Produsen;
use App\Observers\OwnerObserver;
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
        Pedagang::observe(OwnerObserver::class);
        Produsen::observe(OwnerObserver::class);
        Penjualan::observe(PenjualanObserver::class);
    }
}
