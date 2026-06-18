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
        $user = auth()->user();
        $isAdminOrPengurus = $user && in_array($user->owner_type, ['Admin', 'Pengurus'], true);

        return $isAdminOrPengurus ? [
            Actions\CreateAction::make(),
        ] : [];
    }
}
