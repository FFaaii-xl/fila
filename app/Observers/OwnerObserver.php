<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Account;
use Illuminate\Support\Str;

class OwnerObserver
{
    /**
     * Handle the owner "created" event.
     */
    public function created($owner): void
    {
        // Hindari pembuatan ganda jika sudah ada (safety)
        $exists = Account::where('owner_type', $this->getOwnerType($owner))
            ->where('owner_id', $owner->id)
            ->exists();

        if (! $exists) {
            Account::create([
                'name' => $owner->nama,
                'email' => Str::slug($owner->nama).'.'.$owner->id.'@citroroso.com',
                'password' => bcrypt(Str::random(16)), // Password acak sementara
                'owner_type' => $this->getOwnerType($owner),
                'owner_id' => $owner->id,
            ]);
        }
    }

    /**
     * Handle the owner "deleted" event.
     * Opsional: Hapus akun jika pedagang/produsen dihapus
     */
    public function deleted($owner): void
    {
        Account::where('owner_type', $this->getOwnerType($owner))
            ->where('owner_id', $owner->id)
            ->delete();
    }

    private function getOwnerType($owner): string
    {
        $class = get_class($owner);
        if (str_contains($class, 'Pedagang')) {
            return 'Pedagang';
        }
        if (str_contains($class, 'Produsen')) {
            return 'Produsen';
        }

        return 'User';
    }
}
