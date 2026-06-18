<?php

namespace App\Filament\Resources\Saldos\Pages;

use App\Filament\Resources\Saldos\SaldoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSaldo extends ViewRecord
{
    protected static string $resource = SaldoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
