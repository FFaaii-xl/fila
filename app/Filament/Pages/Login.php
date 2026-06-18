<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            \Filament\Forms\Components\TextInput::make('username')
                ->label('Username')
                ->required()
                ->autocomplete(),

            \Filament\Forms\Components\TextInput::make('password')
                ->label('Password')
                ->required()
                ->password()
                ->autocomplete('current-password'),
        ];
    }
}
