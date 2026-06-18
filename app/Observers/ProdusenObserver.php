<?php

namespace App\Observers;

use App\Models\Produsen;
use App\Services\UserAutoCreationService;
use Filament\Notifications\Notification;

class ProdusenObserver
{
    public function __construct(
        protected UserAutoCreationService $userService
    ) {}

    public function created(Produsen $produsen): void
    {
        // Auto create user for this produsen
        $user = $this->userService->createUserForOwner(
            'Produsen',
            $produsen->id,
            $produsen->nama
        );

        // Get the plain password
        $plainPassword = $user->getAttributes()['password'] ?? '';

        // Send notification to admin
        Notification::make()
            ->title('User Baru Dibuat!')
            ->body("User untuk {$produsen->nama} telah dibuat.\nUsername: {$user->username}\nPassword: {$plainPassword}")
            ->success()
            ->persistent()
            ->send();
    }
}
