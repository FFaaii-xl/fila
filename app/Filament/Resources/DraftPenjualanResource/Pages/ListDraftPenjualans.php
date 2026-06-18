<?php

namespace App\Filament\Resources\DraftPenjualanResource\Pages;

use App\Filament\Resources\DraftPenjualanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDraftPenjualans extends ListRecords
{
    protected static string $resource = DraftPenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
