<?php

namespace App\Filament\Resources\Saldos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SaldoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('jumlah')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('owner_type')
                    ->required(),
                TextInput::make('owner_id')
                    ->required()
                    ->numeric(),
            ]);
    }
}
