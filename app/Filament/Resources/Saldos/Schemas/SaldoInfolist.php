<?php

namespace App\Filament\Resources\Saldos\Schemas;

use App\Models\Saldo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SaldoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('jumlah')
                    ->numeric(),
                TextEntry::make('owner_type'),
                TextEntry::make('owner_id')
                    ->numeric(),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Saldo $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
