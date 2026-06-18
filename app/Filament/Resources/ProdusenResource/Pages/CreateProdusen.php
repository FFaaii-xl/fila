<?php

namespace App\Filament\Resources\ProdusenResource\Pages;

use App\Filament\Resources\ProdusenResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProdusen extends CreateRecord
{
    protected static string $resource = ProdusenResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
