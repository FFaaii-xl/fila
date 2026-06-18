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
use Illuminate\Support\Facades\DB;

class ProdusenResource extends Resource
{
    protected static ?string $model = Produsen::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string | \UnitEnum | null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Data Produsen';

    protected static ?string $pluralModelLabel = 'Data Produsen';

    protected static ?string $modelLabel = 'Produsen';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Produsen')
                    ->schema([
                        FormComponents\TextInput::make('nama')
                            ->label('Nama Produsen')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Nama Produsen / Kelompok')
                            ->unique(ignoreRecord: true),
                        FormComponents\TextInput::make('bundle_ke')
                            ->label('Bundle Ke')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->helperText('Urutan kelompok dalam manifes'),
                        FormComponents\Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options(['male' => 'Laki-laki', 'female' => 'Perempuan'])
                            ->native(false),
                        FormComponents\TextInput::make('tabungan_rate')
                            ->label('Rate Tabungan')
                            ->numeric()
                            ->required()
                            ->helperText('Tabungan harian yang dipotong saat bayar'),
                        FormComponents\TextInput::make('tabungan')
                            ->label('Total Tabungan')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Total akumulasi tabungan saat ini'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isAdminOrPengurus = $user && in_array($user->owner_type, ['Admin', 'Pengurus'], true);

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // NUCLEAR ATOMIC JOINT (Subquery Optimized)
                $subQuery = DB::table('produk')
                    ->select('produsen_id')
                    ->selectRaw('COUNT(id) as produks_count')
                    ->selectRaw('GROUP_CONCAT(nama SEPARATOR ", ") as produks_names_raw')
                    ->whereNull('deleted_at')
                    ->groupBy('produsen_id');

                $query->leftJoinSub($subQuery, 'produk_stats', 'produsen.id', '=', 'produk_stats.produsen_id')
                    ->select('produsen.*', 'produk_stats.produks_count', 'produk_stats.produks_names_raw');
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->visible(fn () => auth()->user()?->owner_type === 'Admin'),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Produsen')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('bundle_ke')
                    ->label('Kel')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('gender')
                    ->label('JK')
                    ->formatStateUsing(fn ($state) => $state === 'male' ? 'L' : 'P')
                    ->badge()
                    ->color(fn ($state) => $state === 'male' ? 'info' : 'danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tabungan_rate')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state) => alignUang($state, false))
                    ->html()
                    ->sortable()
                    ->visible(fn () => $isAdminOrPengurus),

                Tables\Columns\TextColumn::make('tabungan')
                    ->label('Total Tabungan')
                    ->formatStateUsing(fn ($state) => alignUang($state, false))
                    ->html()
                    ->sortable()
                    ->visible(fn () => $isAdminOrPengurus)
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ),

                Tables\Columns\TextColumn::make('produks_names_raw')
                    ->label('Produk')
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('nama')
                    ->label('Nama Produsen')
                    ->options(fn () => Produsen::query()->distinct()->orderBy('nama')->pluck('nama', 'nama')->toArray())
                    ->searchable()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('bundle_ke')
                    ->label('Kelompok (Bundle)')
                    ->options(fn () => Produsen::query()->whereNotNull('bundle_ke')->distinct()->orderBy('bundle_ke')->pluck('bundle_ke', 'bundle_ke')->mapWithKeys(fn ($v) => [$v => "Grup {$v}"])->toArray())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('gender')
                    ->label('JK')
                    ->options(['male' => 'Laki-laki', 'female' => 'Perempuan']),
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
            'index' => \App\Filament\Resources\ProdusenResource\Pages\ListProdusens::route('/'),
            'create' => \App\Filament\Resources\ProdusenResource\Pages\CreateProdusen::route('/create'),
            'edit' => \App\Filament\Resources\ProdusenResource\Pages\EditProdusen::route('/{record}/edit'),
        ];
    }
}
