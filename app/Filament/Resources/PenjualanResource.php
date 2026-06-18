<?php

namespace App\Filament\Resources;

use App\Models\Penjualan;
use App\Models\Pedagang;
use App\Models\Produk;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\PenjualanResource\Pages;
use UnitEnum;
use BackedEnum;

class PenjualanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Penjualan';

    protected static ?string $modelLabel = 'Penjualan';

    protected static ?string $pluralModelLabel = 'Penjualan';

    protected static UnitEnum | string | null $navigationGroup = 'Transaksi';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('pedagang_id')
                    ->label('Pedagang')
                    ->options(Pedagang::pluck('nama', 'id'))
                    ->required(),

                Forms\Components\Select::make('produk_id')
                    ->label('Produk')
                    ->options(Produk::pluck('nama', 'id'))
                    ->required(),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->default(now()),

                Forms\Components\TextInput::make('titip')
                    ->label('Jumlah Titip')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('laku')
                    ->label('Jumlah Laku')
                    ->numeric()
                    ->default(0),

                Forms\Components\TextInput::make('sisa_jual')
                    ->label('Sisa Jual')
                    ->numeric()
                    ->default(0),

                Forms\Components\TextInput::make('retur')
                    ->label('Retur')
                    ->numeric()
                    ->default(0),

                Forms\Components\TextInput::make('modal')
                    ->label('Modal')
                    ->numeric()
                    ->prefix('Rp'),

                Forms\Components\TextInput::make('jual')
                    ->label('Harga Jual')
                    ->numeric()
                    ->prefix('Rp'),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'Draft' => 'Draft',
                        'Pending' => 'Pending',
                        'Ok' => 'OK',
                    ])
                    ->default('Draft'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('tanggal')->label('Tanggal')->date(),
                Tables\Columns\TextColumn::make('pedagang.nama')->label('Pedagang'),
                Tables\Columns\TextColumn::make('produk.nama')->label('Produk'),
                Tables\Columns\TextColumn::make('titip')->label('Titip'),
                Tables\Columns\TextColumn::make('laku')->label('Laku'),
                Tables\Columns\TextColumn::make('sisa_jual')->label('Sisa'),
                Tables\Columns\TextColumn::make('status')->label('Status'),
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
            'index' => Pages\ListPenjualans::route('/'),
            'create' => Pages\CreatePenjualan::route('/create'),
            'view' => Pages\ViewPenjualan::route('/{record}'),
            'edit' => Pages\EditPenjualan::route('/{record}/edit'),
        ];
    }
}
