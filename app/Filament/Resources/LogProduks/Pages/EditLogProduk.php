<?php

namespace App\Filament\Resources\LogProduks\Pages;

use App\Filament\Resources\LogProduks\LogProdukResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLogProduk extends EditRecord
{
    protected static string $resource = LogProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
