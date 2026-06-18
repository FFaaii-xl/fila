<?php

namespace App\Filament\Resources\Pembulatans\Pages;

use App\Filament\Resources\Pembulatans\PembulatanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPembulatans extends ListRecords
{
    protected static string $resource = PembulatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
