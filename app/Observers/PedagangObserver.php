<?php

namespace App\Observers;

use App\Models\Pedagang;
use App\Services\UserAutoCreationService;
use Filament\Notifications\Notification;

class PedagangObserver
{
    public function __construct(
        protected UserAutoCreationService $userService
    ) {}

    public function created(Pedagang $pedagang): void
    {
        // Auto create user for this pedagang
        $user = $this->userService->createUserForOwner(
            'Pedagang',
            $pedagang->id,
            $pedagang->nama
        );

        // Get the plain password (stored temporarily)
        $plainPassword = $user->getAttributes()['password'] ?? '';

        // Send notification to admin
        Notification::make()
            ->title('User Baru Dibuat!')
            ->body("User untuk {$pedagang->nama} telah dibuat.\nUsername: {$user->username}\nPassword: {$plainPassword}")
            ->success()
            ->persistent()
            ->send();
    }
}
