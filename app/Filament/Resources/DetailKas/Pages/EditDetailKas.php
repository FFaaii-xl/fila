<?php

namespace App\Filament\Resources\DetailKas\Pages;

use App\Filament\Resources\DetailKas\DetailKasResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDetailKas extends EditRecord
{
    protected static string $resource = DetailKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
