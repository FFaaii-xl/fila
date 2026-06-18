<?php

namespace App\Filament\Resources\LogProduks\Pages;

use App\Filament\Resources\LogProduks\LogProdukResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLogProduk extends ViewRecord
{
    protected static string $resource = LogProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
