<?php

namespace App\Filament\Resources\PedagangResource\Pages;

use App\Filament\Resources\PedagangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPedagang extends EditRecord
{
    protected static string $resource = PedagangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->owner_type === 'Admin'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
