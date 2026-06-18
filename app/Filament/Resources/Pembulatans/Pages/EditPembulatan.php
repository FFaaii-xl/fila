<?php

namespace App\Filament\Resources\Pembulatans\Pages;

use App\Filament\Resources\Pembulatans\PembulatanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPembulatan extends EditRecord
{
    protected static string $resource = PembulatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
