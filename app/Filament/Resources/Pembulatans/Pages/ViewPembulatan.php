<?php

namespace App\Filament\Resources\Pembulatans\Pages;

use App\Filament\Resources\Pembulatans\PembulatanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPembulatan extends ViewRecord
{
    protected static string $resource = PembulatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
