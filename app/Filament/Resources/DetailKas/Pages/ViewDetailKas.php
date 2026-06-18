<?php

namespace App\Filament\Resources\DetailKas\Pages;

use App\Filament\Resources\DetailKas\DetailKasResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDetailKas extends ViewRecord
{
    protected static string $resource = DetailKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
