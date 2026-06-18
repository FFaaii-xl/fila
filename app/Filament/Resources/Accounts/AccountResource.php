<?php

namespace App\Filament\Resources\Accounts;

use App\Filament\Resources\Accounts\Pages;
use App\Models\Pedagang;
use App\Models\Produsen;
use App\Models\User;
use Filament\Forms\Components as FormComponents;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountResource extends Resource
{
    // Use the User model for users2
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Manajemen Akun Login';

    protected static ?string $pluralModelLabel = 'Manajemen Akun Login';

    protected static ?string $modelLabel = 'Akun Login';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Akun')
                    ->schema([
                        FormComponents\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        FormComponents\TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        FormComponents\Select::make('virtual_role')
                            ->label('Akses / Profil Terkait')
                            ->options(function () {
                                return [
                                    'Internal Team' => [
                                        'Admin:0' => 'Admin (Super User/Akses Penuh)',
                                        'Pengurus:0' => 'Pengurus / Kasir',
                                    ],
                                    'Pedagang' => Pedagang::orderBy('nama')->pluck('nama', 'id')->mapWithKeys(fn ($name, $id) => ["Pedagang:$id" => "Pedagang: $name"])->toArray(),
                                    'Produsen' => Produsen::orderBy('nama')->pluck('nama', 'id')->mapWithKeys(fn ($name, $id) => ["Produsen:$id" => "Produsen: $name"])->toArray(),
                                ];
                            })
                            ->searchable()
                            ->required()
                            // Set initial value based on owner
                            ->formatStateUsing(fn (?User $record) => $record ? ($record->owner_type . ':' . $record->owner_id) : 'Pengurus:0')
                            ->dehydrated(false) // Don't save this field directly
                            ->afterStateHydrated(function (FormComponents\Select $component, ?string $state, ?User $record) {
                                if ($record) {
                                    $component->state($record->owner_type . ':' . $record->owner_id);
                                }
                            })
                            ->live()
                            ->afterStateUpdated(function (string $state, FormComponents\Select $component) {
                                // Extract and set owner variables dynamically if needed by other form parts
                            }),
                        FormComponents\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->leftJoin('pedagang', function ($join) {
                    $join->on('users2.owner_id', '=', 'pedagang.id')
                        ->where('users2.owner_type', '=', 'Pedagang');
                })
                ->leftJoin('produsen', function ($join) {
                    $join->on('users2.owner_id', '=', 'produsen.id')
                        ->where('users2.owner_type', '=', 'Produsen');
                })
                ->select('users2.*', 'pedagang.nama as pedagang_nama', 'produsen.nama as produsen_nama');
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner_type')
                    ->label('Tipe Owner')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner_id')
                    ->label('Owner Terkait')
                    ->formatStateUsing(function (string $state, User $record) {
                        $name = $record->pedagang_nama ?? $record->produsen_nama;
                        if ($name) {
                            return "{$name} (ID: {$record->owner_id})";
                        }
                        return "Admin/Internal (ID: {$record->owner_id})";
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Handle the virtual_role field
                        if (isset($data['virtual_role'])) {
                            [$type, $id] = explode(':', $data['virtual_role']);
                            $data['owner_type'] = $type;
                            $data['owner_id'] = (int) $id;
                            unset($data['virtual_role']);
                        }
                        return $data;
                    }),
                
                \Filament\Tables\Actions\Action::make('ganti_password')
                    ->label('Password')
                    ->icon('heroicon-o-key')
                    ->color('primary')
                    ->form([
                        FormComponents\TextInput::make('new_password')
                            ->label('Password Baru')
                            ->password()
                            ->required()
                            ->rule(Password::default()),
                        FormComponents\TextInput::make('new_password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->required()
                            ->same('new_password'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'password' => Hash::make($data['new_password']),
                        ]);
                    })
                    ->successNotificationTitle('Password berhasil diubah'),
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
