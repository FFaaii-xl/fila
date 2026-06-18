<?php

namespace App\Filament\Resources;

use App\Models\Pedagang;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\PedagangResource\Pages;
use UnitEnum;
use BackedEnum;

class PedagangResource extends Resource
{
    protected static ?string $model = Pedagang::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Pedagang';

    protected static ?string $modelLabel = 'Pedagang';

    protected static ?string $pluralModelLabel = 'Pedagang';

    protected static UnitEnum | string | null $navigationGroup = 'Data Master';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('nama')
                    ->label('Nama Pedagang')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                    ])
                    ->required(),

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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('gender')
                    ->label('JK')
                    ->format(fn (string $state): string => $state === 'male' ? 'L' : 'P')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tabungan_rate')
                    ->label('Rate')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tabungan')
                    ->label('Tabungan')
                    ->formatStateUsing(fn ($state) => alignUang($state))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPedagangs::route('/'),
            'create' => Pages\CreatePedagang::route('/create'),
            'view' => Pages\ViewPedagang::route('/{record}'),
            'edit' => Pages\EditPedagang::route('/{record}/edit'),
        ];
    }
}
