<?php

namespace App\Filament\Resources\Penjualans\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PenjualanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('produk_id')
                    ->relationship('produk', 'id')
                    ->required(),
                TextInput::make('titip')
                    ->required()
                    ->numeric(),
                TextInput::make('laku')
                    ->numeric()
                    ->default(null),
                TextInput::make('sisa_jual')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('harga_jual')
                    ->required()
                    ->numeric(),
                TextInput::make('harga_beli')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('tanggal')
                    ->required(),
                Select::make('status')
                    ->options(['Pending' => 'Pending', 'Ok' => 'Ok', 'Draft' => 'Draft', 'Fase1' => 'Fase1'])
                    ->default('Fase1')
                    ->required(),
                Select::make('pedagang_id')
                    ->relationship('pedagang', 'id')
                    ->required(),
                TextInput::make('keterangan')
                    ->default(null),
            ]);
    }
}
