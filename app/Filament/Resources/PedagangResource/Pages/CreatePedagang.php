<?php

namespace App\Filament\Resources\PedagangResource\Pages;

use App\Filament\Resources\PedagangResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePedagang extends CreateRecord
{
    protected static string $resource = PedagangResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Otomatis buat Saldo awal Rp 0 jika belum ada
        $record = $this->record;
        if (!$record->saldo) {
            $record->saldo()->create([
                'jumlah' => 0,
                'owner_type' => 'Pedagang',
                'owner_id' => $record->id,
            ]);
        }
    }
}
