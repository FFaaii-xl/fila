<?php

namespace App\Filament\Resources;

use App\Models\Pedagang;
use App\Models\Produsen;
use App\Models\User;
use App\Services\UserAutoCreationService;
use Filament\Forms\Components as FormComponents;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-circle';

    protected static string | \UnitEnum | null $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Manajemen User';

    protected static ?string $pluralModelLabel = 'User';

    protected static ?string $modelLabel = 'User';

    protected static ?int $navigationSort = 99;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi User')
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

                        FormComponents\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        FormComponents\Select::make('owner_type')
                            ->label('Tipe Owner')
                            ->options([
                                'Pedagang' => 'Pedagang',
                                'Produsen' => 'Produsen',
                                'Admin' => 'Admin',
                                'Pengurus' => 'Pengurus',
                            ])
                            ->required()
                            ->live(),

                        FormComponents\Select::make('owner_id')
                            ->label('Owner')
                            ->options(function (callable $get) {
                                $type = $get('owner_type');
                                if ($type === 'Pedagang') {
                                    return Pedagang::pluck('nama', 'id');
                                } elseif ($type === 'Produsen') {
                                    return Produsen::pluck('nama', 'id');
                                }
                                return [];
                            })
                            ->visible(fn (callable $get) => in_array($get('owner_type'), ['Pedagang', 'Produsen'])),

                        FormComponents\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('owner_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Admin' => 'danger',
                        'Pengurus' => 'warning',
                        'Pedagang' => 'success',
                        'Produsen' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->formatStateUsing(function (User $record): string {
                        if ($record->owner_type === 'Pedagang') {
                            return $record->owner?->nama . ' (Pedagang)' ?? '-';
                        } elseif ($record->owner_type === 'Produsen') {
                            return $record->owner?->nama . ' (Produsen)' ?? '-';
                        }
                        return $record->owner_type ?? '-';
                    })
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('owner_type')
                    ->label('Tipe Owner')
                    ->options([
                        'Admin' => 'Admin',
                        'Pengurus' => 'Pengurus',
                        'Pedagang' => 'Pedagang',
                        'Produsen' => 'Produsen',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $newPassword = app(UserAutoCreationService::class)->resetPassword($record);
                        Notification::make()
                            ->title('Password Di-reset!')
                            ->body("Password baru untuk {$record->name}: {$newPassword}")
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\UserResource\Pages\ListUsers::route('/'),
            'edit' => \App\Filament\Resources\UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
