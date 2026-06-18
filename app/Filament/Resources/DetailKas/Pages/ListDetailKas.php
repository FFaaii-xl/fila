<?php

namespace App\Filament\Resources\DetailKas\Pages;

use App\Filament\Resources\DetailKas\DetailKasResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDetailKas extends ListRecords
{
    protected static string $resource = DetailKasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
