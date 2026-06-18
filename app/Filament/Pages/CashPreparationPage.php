<?php

namespace App\Filament\Pages;

use App\Services\CashPreparationService;
use App\Traits\Filament\HasRoleAuthorization;
use Filament\Pages\Page;

class CashPreparationPage extends Page
{
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = 31;
    protected static ?string $title = 'Persiapan Uang Nota';

    public static function getNavigationGroup(): ?string
    {
        return 'Operasional';
    }

    protected string $view = 'filament.pages.cash-preparation-page';

    public static function canAccess(): bool
    {
        return (new static)->isAdminOrPengurus();
    }

    protected function getViewData(): array
    {
        $date = request('date', now('Asia/Jakarta')->toDateString());

        $service = app(CashPreparationService::class);
        $data = $service->calculate($date);

        return [
            'date' => $date,
            'total_payout' => $data['total_payout'],
            'breakdown' => $data['breakdown'],
            'producers' => $data['producers'],
        ];
    }
}
