<?php

namespace App\Filament\Resources;

use App\Models\Produk;
use App\Models\Produsen;
use Filament\Forms\Components as FormComponents;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProdukResource extends Resource
{
    protected static ?string $model = Produk::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string | \UnitEnum | null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Katalog Produk';

    protected static ?string $pluralModelLabel = 'Katalog Produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Produk')
                    ->schema([
                        FormComponents\TextInput::make('nama')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Onde-onde')
                            ->helperText('Nama produk yang muncul di nota'),
                        FormComponents\Select::make('produsen_id')
                            ->label('Produsen')
                            ->relationship('produsen', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Pemilik produk ini'),
                        FormComponents\TextInput::make('harga_beli')
                            ->label('Harga Beli')
                            ->numeric()
                            ->required()
                            ->helperText('Harga setoran dari produsen'),
                        FormComponents\TextInput::make('harga_jual')
                            ->label('Harga Jual')
                            ->numeric()
                            ->required()
                            ->helperText('Harga jual ke pelanggan/pedagang'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isAdminOrPengurus = $user && in_array($user->owner_type, ['Admin', 'Pengurus'], true);

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->select('produk.*')
                    ->leftJoin('produsen', 'produk.produsen_id', '=', 'produsen.id')
                    ->addSelect('produsen.nama as produsen_nama');
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->visible(fn () => auth()->user()?->owner_type === 'Admin'),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('produsen_nama')
                    ->label('Produsen')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('produsen.nama', 'LIKE', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('H.TTP')
                    ->formatStateUsing(fn ($state) => alignUang($state, false))
                    ->html()
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Average::make()
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ),

                Tables\Columns\TextColumn::make('harga_jual')
                    ->label('H.JL')
                    ->formatStateUsing(fn ($state) => alignUang($state, false))
                    ->html()
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Average::make()
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('produsen_id')
                    ->label('Produsen')
                    ->relationship('produsen', 'nama')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('harga_beli')
                    ->label('H.TTP')
                    ->options(fn () => Produk::query()->distinct()->orderBy('harga_beli')->pluck('harga_beli', 'harga_beli')->mapWithKeys(fn ($v) => [$v => 'Rp ' . number_format((float) $v, 0, ',', '.')])->toArray())
                    ->searchable()
                    ->multiple(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->visible(fn () => $isAdminOrPengurus),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->owner_type === 'Admin'),
                ]),
            ])
            ->defaultSort('nama');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\ProdukResource\Pages\ListProduks::route('/'),
            'create' => \App\Filament\Resources\ProdukResource\Pages\CreateProduk::route('/create'),
            'edit' => \App\Filament\Resources\ProdukResource\Pages\EditProduk::route('/{record}/edit'),
        ];
    }
}
