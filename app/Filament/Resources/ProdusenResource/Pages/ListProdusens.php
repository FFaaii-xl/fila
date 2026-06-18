<?php

namespace App\Filament\Resources\ProdusenResource\Pages;

use App\Filament\Resources\ProdusenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProdusens extends ListRecords
{
    protected static string $resource = ProdusenResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
