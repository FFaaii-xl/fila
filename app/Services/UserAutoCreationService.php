<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserAutoCreationService
{
    /**
     * Convert nama to unix-safe username
     */
    public function generateUsername(string $nama): string
    {
        // Remove special chars, convert spaces to underscores
        $username = Str::slug($nama, '_');
        
        // Ensure unique
        $original = $username;
        $counter = 1;
        
        while (User::where('username', $username)->exists()) {
            $username = $original . '_' . $counter;
            $counter++;
        }
        
        return strtolower($username);
    }

    /**
     * Generate email from username
     */
    public function generateEmail(string $username): string
    {
        return $username . '@citro.fun';
    }

    /**
     * Generate random password (12 chars)
     */
    public function generatePassword(): string
    {
        return Str::random(12);
    }

    /**
     * Create user for owner (Pedagang or Produsen)
     */
    public function createUserForOwner(string $ownerType, int $ownerId, string $nama): User
    {
        $username = $this->generateUsername($nama);
        $password = $this->generatePassword();
        $email = $this->generateEmail($username);

        $user = User::create([
            'name' => $nama,
            'username' => $username,
            'password' => $password, // Will be hashed by model
            'email' => $email,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
        ]);

        return $user;
    }

    /**
     * Reset password for user and return new password
     */
    public function resetPassword(User $user): string
    {
        $newPassword = $this->generatePassword();
        $user->password = $newPassword;
        $user->save();
        
        return $newPassword;
    }
}
