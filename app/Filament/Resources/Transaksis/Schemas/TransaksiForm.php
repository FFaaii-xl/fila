<?php

namespace App\Filament\Resources\Transaksis\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransaksiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('tanggal')
                    ->required(),
                TextInput::make('jumlah')
                    ->required()
                    ->numeric(),
                TextInput::make('owner_type')
                    ->required(),
                TextInput::make('owner_id')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options([
            'Pending' => 'Pending',
            'Canceled' => 'Canceled',
            'Paid Out' => 'Paid out',
            'Ok' => 'Ok',
            'Draft' => 'Draft',
        ])
                    ->default('Ok')
                    ->required(),
                TextInput::make('keterangan')
                    ->default(null),
                TextInput::make('kemarin')
                    ->numeric()
                    ->default(null),
                TextInput::make('pembulatan')
                    ->numeric()
                    ->default(null),
                TextInput::make('kas')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
