<?php

namespace App\Filament\Resources\ProdusenResource\Pages;

use App\Filament\Resources\ProdusenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProdusen extends EditRecord
{
    protected static string $resource = ProdusenResource::class;

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
