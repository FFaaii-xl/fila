<?php

namespace App\Filament\Resources;

use App\Models\Pedagang;
use Filament\Forms\Components as FormComponents;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PedagangResource extends Resource
{
    protected static ?string $model = Pedagang::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Data Pedagang';

    protected static ?string $pluralModelLabel = 'Data Pedagang';

    protected static ?string $modelLabel = 'Pedagang';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Pedagang')
                    ->schema([
                        FormComponents\TextInput::make('nama')
                            ->label('Nama Pedagang')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Bpk. Slamet')
                            ->unique(ignoreRecord: true)
                            ->helperText('Gunakan nama lengkap sesuai KTP jika perlu'),
                        FormComponents\Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options(['male' => 'Laki-laki', 'female' => 'Perempuan'])
                            ->required()
                            ->native(false),
                        FormComponents\TextInput::make('tabungan_rate')
                            ->label('Rate Tabungan')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Jumlah tabungan harian yang ditarik otomatis'),
                        FormComponents\TextInput::make('tabungan')
                            ->label('Total Tabungan (Berjalan)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->default(0)
                            ->helperText('Saldo tabungan saat ini (Read-Only)'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isAdminOrPengurus = $user && in_array($user->owner_type, ['Admin', 'Pengurus'], true);

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // NUCLEAR ATOMIC JOINT: Resolusi Saldo tanpa hidrasi objek tambahan
                $query->leftJoin('saldo', function ($join) {
                    $join->on('pedagang.id', '=', 'saldo.owner_id')
                        ->where('saldo.owner_type', '=', 'Pedagang');
                })
                ->select('pedagang.*', 'saldo.jumlah as saldo_jumlah', 'saldo.id as saldo_id');
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->visible(fn () => auth()->user()?->owner_type === 'Admin'),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

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
                    ->label('Tabungan')
                    ->formatStateUsing(fn ($state) => alignUang($state, true))
                    ->html()
                    ->sortable()
                    ->visible(fn () => $isAdminOrPengurus)
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ),

                Tables\Columns\TextColumn::make('saldo_jumlah')
                    ->label('Saldo')
                    ->formatStateUsing(fn ($state) => alignUang($state ?? 0, true))
                    ->html()
                    ->sortable()
                    ->visible(fn () => $isAdminOrPengurus)
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('nama')
                    ->label('Nama Pedagang')
                    ->options(fn () => Pedagang::query()->distinct()->orderBy('nama')->pluck('nama', 'nama')->toArray())
                    ->searchable()
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
            'index' => \App\Filament\Resources\PedagangResource\Pages\ListPedagangs::route('/'),
            'create' => \App\Filament\Resources\PedagangResource\Pages\CreatePedagang::route('/create'),
            'edit' => \App\Filament\Resources\PedagangResource\Pages\EditPedagang::route('/{record}/edit'),
        ];
    }
}
