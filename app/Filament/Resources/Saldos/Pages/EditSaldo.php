<?php

namespace App\Filament\Resources\Saldos\Pages;

use App\Filament\Resources\Saldos\SaldoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSaldo extends EditRecord
{
    protected static string $resource = SaldoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
