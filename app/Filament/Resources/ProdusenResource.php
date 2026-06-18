<?php

namespace App\Filament\Resources;

use App\Models\Produsen;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\ProdusenResource\Pages;
use UnitEnum;
use BackedEnum;

class ProdusenResource extends Resource
{
    protected static ?string $model = Produsen::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Produsen';

    protected static ?string $modelLabel = 'Produsen';

    protected static ?string $pluralModelLabel = 'Produsen';

    protected static UnitEnum | string | null $navigationGroup = 'Data Master';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('nama')
                    ->label('Nama Produsen')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                    ]),

                Forms\Components\TextInput::make('bundle_ke')
                    ->label('Bundle Ke')
                    ->numeric(),

                Forms\Components\TextInput::make('tabungan_rate')
                    ->label('Rate Tabungan (%)')
                    ->numeric()
                    ->default(0),

                Forms\Components\TextInput::make('tabungan')
                    ->label('Tabungan')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('nama')->label('Nama')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('gender')
                    ->label('JK')
                    ->format(fn (string $state): string => $state === 'male' ? 'L' : 'P'),
                Tables\Columns\TextColumn::make('bundle_ke')->label('Bundle')->sortable(),
                Tables\Columns\TextColumn::make('tabungan')->label('Tabungan')
                    ->formatStateUsing(fn ($state) => alignUang($state)),
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
            'index' => Pages\ListProdusens::route('/'),
            'create' => Pages\CreateProdusen::route('/create'),
            'view' => Pages\ViewProdusen::route('/{record}'),
            'edit' => Pages\EditProdusen::route('/{record}/edit'),
        ];
    }
}
