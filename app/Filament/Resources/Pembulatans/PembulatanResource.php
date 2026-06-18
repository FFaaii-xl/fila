<?php

namespace App\Filament\Resources\Pembulatans;

use App\Filament\Resources\Pembulatans\Pages;
use App\Models\Pembulatan;
use Filament\Forms\Components as FormComponents;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PembulatanResource extends Resource
{
    protected static ?string $model = Pembulatan::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-scale';

    protected static string | \UnitEnum | null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Pengaturan Pembulatan';

    protected static ?string $pluralModelLabel = 'Pengaturan Pembulatan';

    protected static ?string $modelLabel = 'Pembulatan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Pembulatan')
                    ->schema([
                        FormComponents\Select::make('produsen_id')
                            ->label('Produsen')
                            ->relationship('produsen', 'nama')
                            ->searchable()
                            ->required(),
                        FormComponents\TextInput::make('pembulatan_ke')
                            ->label('Pembulatan Ke (Misal: 50000)')
                            ->numeric()
                            ->default(50000)
                            ->required(),
                        FormComponents\TextInput::make('jumlah')
                            ->label('Saldo Bulatan (Remnant)')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        FormComponents\TextInput::make('keterangan')
                            ->label('Keterangan')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('produsen.nama')
                    ->label('Produsen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pembulatan_ke')
                    ->label('Pembulatan Ke')
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Saldo Bulatan')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPembulatans::route('/'),
            'create' => Pages\CreatePembulatan::route('/create'),
            'edit' => Pages\EditPembulatan::route('/{record}/edit'),
        ];
    }
}
