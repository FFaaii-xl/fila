<?php

namespace App\Filament\Resources\Penjualans\Schemas;

use App\Models\Penjualan;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PenjualanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('produk.id')
                    ->label('Produk'),
                TextEntry::make('titip')
                    ->numeric(),
                TextEntry::make('laku')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('sisa_jual')
                    ->numeric(),
                TextEntry::make('harga_jual')
                    ->numeric(),
                TextEntry::make('harga_beli')
                    ->numeric(),
                TextEntry::make('tanggal')
                    ->dateTime(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('pedagang.id')
                    ->label('Pedagang'),
                TextEntry::make('keterangan')
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Penjualan $record): bool => $record->trashed()),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
