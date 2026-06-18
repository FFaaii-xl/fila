<?php

namespace App\Filament\Resources\LogProduks;

use App\Filament\Resources\LogProduks\Pages;
use App\Models\LogProduk;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class LogProdukResource extends Resource
{
    protected static ?string $model = LogProduk::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static string | \UnitEnum | null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Histori Produk';

    protected static ?string $pluralModelLabel = 'Histori Produk';

    protected static ?string $modelLabel = 'Log Produk';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_produk')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('field_name')
                    ->label('Field')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('old_value')
                    ->label('Lama'),
                Tables\Columns\TextColumn::make('new_value')
                    ->label('Baru'),
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->searchable()
                    ->color('primary')
                    ->extraAttributes(['class' => 'italic font-serif']),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Only view
            ])
            ->bulkActions([
                // No bulk actions for read-only
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogProduks::route('/'),
        ];
    }
}
