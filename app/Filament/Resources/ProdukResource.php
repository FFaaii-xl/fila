<?php

namespace App\Filament\Resources;

use App\Models\Produk;
use App\Models\Produsen;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\ProdukResource\Pages;
use UnitEnum;
use BackedEnum;

class ProdukResource extends Resource
{
    protected static ?string $model = Produk::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static UnitEnum | string | null $navigationGroup = 'Data Master';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('produsen_id')
                    ->label('Produsen')
                    ->options(Produsen::pluck('nama', 'id'))
                    ->required(),

                Forms\Components\TextInput::make('nama')
                    ->label('Nama Produk')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('harga_beli')
                    ->label('Harga Beli')
                    ->numeric()
                    ->prefix('Rp'),

                Forms\Components\TextInput::make('harga_jual')
                    ->label('Harga Jual')
                    ->numeric()
                    ->prefix('Rp'),

                Forms\Components\TextInput::make('stok')
                    ->label('Stok')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('nama')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('produsen.nama')->label('Produsen'),
                Tables\Columns\TextColumn::make('harga_beli')->label('Harga Beli')
                    ->formatStateUsing(fn ($state) => alignUang($state)),
                Tables\Columns\TextColumn::make('harga_jual')->label('Harga Jual')
                    ->formatStateUsing(fn ($state) => alignUang($state)),
                Tables\Columns\TextColumn::make('stok')->label('Stok'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProduks::route('/'),
            'create' => Pages\CreateProduk::route('/create'),
            'view' => Pages\ViewProduk::route('/{record}'),
            'edit' => Pages\EditProduk::route('/{record}/edit'),
        ];
    }
}
