<?php

namespace App\Filament\Resources\Saldos;

use App\Filament\Resources\Saldos\Pages\CreateSaldo;
use App\Filament\Resources\Saldos\Pages\EditSaldo;
use App\Filament\Resources\Saldos\Pages\ListSaldos;
use App\Filament\Resources\Saldos\Pages\ViewSaldo;
use App\Filament\Resources\Saldos\Schemas\SaldoForm;
use App\Filament\Resources\Saldos\Schemas\SaldoInfolist;
use App\Filament\Resources\Saldos\Tables\SaldosTable;
use App\Models\Saldo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SaldoResource extends Resource
{
    protected static ?string $model = Saldo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return SaldoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SaldoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SaldosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSaldos::route('/'),
            'create' => CreateSaldo::route('/create'),
            'view' => ViewSaldo::route('/{record}'),
            'edit' => EditSaldo::route('/{record}/edit'),
        ];
    }
}
