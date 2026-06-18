<?php

namespace App\Filament\Pages;

use App\Services\SalesService;
use App\Traits\Filament\HasRoleAuthorization;
use Filament\Pages\Page;

class HealthCheckPage extends Page
{
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-heart';
    protected static string | \UnitEnum | null $navigationGroup = 'AI & Utilities';
    protected static ?int $navigationSort = 110;
    protected static ?string $title = 'System Health Check';

    protected string $view = 'filament.pages.health-check-page';

    public static function canAccess(): bool
    {
        return (new static)->isAdminOrPengurus();
    }

    protected function getViewData(): array
    {
        $salesService = app(SalesService::class);
        $health = $salesService->checkSystemHealth();

        return [
            'health' => $health,
        ];
    }
}
