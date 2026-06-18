<?php

namespace App\Filament\Resources\ProdukResource\Pages;

use App\Filament\Resources\ProdukResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProduks extends ListRecords
{
    protected static string $resource = ProdukResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isAdminOrPengurus = $user && in_array($user->owner_type, ['Admin', 'Pengurus'], true);

        return $isAdminOrPengurus ? [
            Actions\CreateAction::make(),
        ] : [];
    }
}
