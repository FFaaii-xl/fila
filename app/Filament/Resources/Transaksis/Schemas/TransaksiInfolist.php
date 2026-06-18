<?php

namespace App\Filament\Resources\Transaksis\Schemas;

use App\Models\Transaksi;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TransaksiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('tanggal')
                    ->dateTime(),
                TextEntry::make('jumlah')
                    ->numeric(),
                TextEntry::make('owner_type'),
                TextEntry::make('owner_id')
                    ->numeric(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('keterangan')
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Transaksi $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('kemarin')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('pembulatan')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('kas')
                    ->numeric()
                    ->placeholder('-'),
            ]);
    }
}
