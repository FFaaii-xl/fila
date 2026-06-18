<?php

namespace App\Filament\Resources\LogProduks\Pages;

use App\Filament\Resources\LogProduks\LogProdukResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLogProduks extends ListRecords
{
    protected static string $resource = LogProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
