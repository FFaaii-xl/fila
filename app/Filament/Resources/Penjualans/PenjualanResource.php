<?php

namespace App\Filament\Resources\Penjualans;

use App\Filament\Resources\Penjualans\Pages;
use App\Models\Penjualan;
use App\Models\Saldo;
use Filament\Forms\Components as FormComponents;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Builder;

class PenjualanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Draft Penjualan';

    protected static ?string $pluralModelLabel = 'Draft Penjualan';

    protected static ?string $modelLabel = 'Penjualan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Data Penjualan')
                    ->schema([
                        FormComponents\Select::make('produk_id')
                            ->label('Produk')
                            ->relationship('produk', 'nama')
                            ->searchable()
                            ->required(),
                        FormComponents\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->default(now())
                            ->required(),
                        FormComponents\Grid::make(2)
                            ->schema([
                                FormComponents\TextInput::make('titip')
                                    ->numeric()
                                    ->required(),
                                FormComponents\TextInput::make('laku')
                                    ->numeric(),
                            ]),
                        FormComponents\Grid::make(2)
                            ->schema([
                                FormComponents\TextInput::make('sisa_jual')
                                    ->numeric(),
                            ]),
                        FormComponents\Grid::make(2)
                            ->schema([
                                FormComponents\TextInput::make('harga_beli')
                                    ->numeric(),
                                FormComponents\TextInput::make('harga_jual')
                                    ->numeric(),
                            ]),
                        FormComponents\Textarea::make('keterangan')
                            ->columnSpanFull(),
                        FormComponents\Select::make('status')
                            ->options([
                                'Draft' => 'Draft',
                                'Ok' => 'Sudah Sesuai',
                                'Fase1' => 'Fase 1',
                            ])
                            ->default('Draft')
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($user) {
                // NUCLEAR ATOMIC JOINT: Resolusi Produk tanpa hidrasi objek tambahan ( Cold Steel Tier )
                $query->leftJoin('produk', 'penjualan.produk_id', '=', 'produk.id')
                    ->select('penjualan.*', 'produk.nama as produk_nama');

                // By default just show Draft in this resource (based on Moonshine logic)
                $query->where('penjualan.status', 'Draft');

                // Scoping Absolute for Pedagang
                if ($user && $user->owner_type === 'Pedagang') {
                    $query->where('penjualan.pedagang_id', $user->owner_id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('produk_nama')
                    ->label('Produk')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('titip')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('laku')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sisa_jual')
                    ->label('Sisa')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('H. Beli')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('harga_jual')
                    ->label('H. Jual')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('saldo')
                    ->label(function () use ($user) {
                        if ($user && $user->owner_type === 'Pedagang') {
                            $saldo = Saldo::where('owner_type', 'Pedagang')
                                ->where('owner_id', $user->owner_id)
                                ->value('jumlah') ?? 0;
                            return 'Saldo: ' . number_format($saldo, 0, ',', '.');
                        }
                        return 'Saldo: -';
                    })
                    ->color('gray')
                    ->icon('heroicon-o-wallet')
                    ->visible(fn () => $user && $user->owner_type === 'Pedagang'),

                \Filament\Tables\Actions\Action::make('commit_all')
                    ->label('Commit All')
                    ->color('success')
                    ->icon('heroicon-o-check-badge')
                    ->requiresConfirmation()
                    ->action(function () {
                        // Logic to commit all
                    }),
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
            'index' => Pages\ListPenjualans::route('/'),
            'create' => Pages\CreatePenjualan::route('/create'),
            'edit' => Pages\EditPenjualan::route('/{record}/edit'),
        ];
    }
}
