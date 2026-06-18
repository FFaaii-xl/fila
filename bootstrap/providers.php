<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\PedagangPanelProvider;
use App\Providers\Filament\ProdusenPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    PedagangPanelProvider::class,
    ProdusenPanelProvider::class,
];
