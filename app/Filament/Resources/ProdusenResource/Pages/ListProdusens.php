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
        $user = auth()->user();
        $isAdminOrPengurus = $user && in_array($user->owner_type, ['Admin', 'Pengurus'], true);

        return $isAdminOrPengurus ? [
            Actions\CreateAction::make(),
        ] : [];
    }
}
