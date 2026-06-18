<?php

namespace App\Filament\Resources\PedagangResource\Pages;

use App\Filament\Resources\PedagangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPedagangs extends ListRecords
{
    protected static string $resource = PedagangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
