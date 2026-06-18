<?php

namespace App\Filament\Resources\DetailKas;

use App\Filament\Resources\DetailKas\Pages;
use App\Models\DetailKas;
use Filament\Forms\Components as FormComponents;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DetailKasResource extends Resource
{
    protected static ?string $model = DetailKas::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static string | \UnitEnum | null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Kas Harian & Operasional';

    protected static ?string $pluralModelLabel = 'Kas Harian & Operasional';

    protected static ?string $modelLabel = 'Detail Kas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Kas')
                    ->schema([
                        FormComponents\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default(now())
                            ->required(),
                        FormComponents\TextInput::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Misal: Beli ATK, Bayar Listrik')
                            ->required()
                            ->maxLength(255),
                        FormComponents\TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->required(),
                        FormComponents\Select::make('status')
                            ->options([
                                'Paid Out' => 'Paid Out',
                                'Ok' => 'Ok',
                                'Draft' => 'Draft',
                                'Pending' => 'Pending',
                                'Canceled' => 'Canceled',
                            ])
                            ->default('Paid Out')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('keterangan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jumlah')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                    ),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paid Out', 'Ok' => 'success',
                        'Draft' => 'gray',
                        'Pending' => 'warning',
                        'Canceled' => 'danger',
                        default => 'primary',
                    }),
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
            ])
            ->defaultSort('tanggal', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDetailKas::route('/'),
            'create' => Pages\CreateDetailKas::route('/create'),
            'edit' => Pages\EditDetailKas::route('/{record}/edit'),
        ];
    }
}
