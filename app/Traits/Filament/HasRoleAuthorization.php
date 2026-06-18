<?php

declare(strict_types=1);

namespace App\Traits\Filament;

use App\Models\User;

/**
 * HasRoleAuthorization Trait for Filament v5
 * 
 * Provides role-based authorization helpers for Filament resources.
 * 
 * Roles:
 * - Admin: Full access to all features
 * - Pengurus: Full access to all features (same as Admin)
 * - Produsen: Limited access - can only view/edit own data
 * - Pedagang: Limited access - can only view/edit own data
 * 
 * Usage in Resource:
 *   use HasRoleAuthorization;
 * 
 * Then in columns/actions:
 *   ->visible(fn () => $this->isAdminOrPengurus())
 */
trait HasRoleAuthorization
{
    /**
     * Get the current authenticated user
     */
    protected function getUser(): ?User
    {
        return auth()->user();
    }

    /**
     * Check if current user is Admin
     */
    protected function isAdmin(): bool
    {
        $user = $this->getUser();
        return $user && $user->owner_type === 'Admin';
    }

    /**
     * Check if current user is Admin or Pengurus
     */
    protected function isAdminOrPengurus(): bool
    {
        $user = $this->getUser();
        return $user && in_array($user->owner_type, ['Admin', 'Pengurus'], true);
    }

    /**
     * Check if current user is Produsen
     */
    protected function isProdusen(): bool
    {
        $user = $this->getUser();
        return $user && $user->owner_type === 'Produsen';
    }

    /**
     * Check if current user is Pedagang
     */
    protected function isPedagang(): bool
    {
        $user = $this->getUser();
        return $user && $user->owner_type === 'Pedagang';
    }

    /**
     * Get the current user's owner ID
     */
    protected function getOwnerId(): ?int
    {
        $user = $this->getUser();
        return $user?->owner_id;
    }

    /**
     * Get the current user's owner type
     */
    protected function getOwnerType(): ?string
    {
        $user = $this->getUser();
        return $user?->owner_type;
    }

    /**
     * Get user role label (Indonesian)
     */
    protected function getRoleLabel(): string
    {
        $user = $this->getUser();
        
        return match ($user?->owner_type) {
            'Admin' => 'Administrator',
            'Pengurus' => 'Pengurus Pasar',
            'Produsen' => 'Produsen',
            'Pedagang' => 'Pedagang',
            default => 'User',
        };
    }

    /**
     * Get user display name
     */
    protected function getUserDisplayName(): string
    {
        $user = $this->getUser();
        
        if (!$user) {
            return 'Guest';
        }

        return $user->name ?? ucfirst(strtolower($user->owner_type ?? 'User'));
    }

    /**
     * Check if user can view all records (Admin/Pengurus)
     * or only their own records (Produsen/Pedagang)
     */
    protected function canViewAll(): bool
    {
        return $this->isAdminOrPengurus();
    }

    /**
     * Check if user can edit all records (Admin/Pengurus)
     * or only their own records (Produsen/Pedagang)
     */
    protected function canEditAll(): bool
    {
        return $this->isAdminOrPengurus();
    }

    /**
     * Check if user can delete records
     * Only Admin/Pengurus can delete
     */
    protected function canDelete(): bool
    {
        return $this->isAdminOrPengurus();
    }

    /**
     * Check if user can access a specific record
     * 
     * @param mixed $record The model record
     * @return bool
     */
    protected function canAccessRecord($record): bool
    {
        // Admin/Pengurus can access all records
        if ($this->isAdminOrPengurus()) {
            return true;
        }

        // Check if record belongs to current user
        $user = $this->getUser();
        
        if (!$user || !$user->owner_type || !$user->owner_id) {
            return false;
        }

        // Get record owner info
        $recordType = class_basename($record);
        $recordId = $record->getKey();

        // Produsen can only access their own produk
        if ($user->owner_type === 'Produsen') {
            if ($recordType === 'Produk' && $record->produsen_id === $user->owner_id) {
                return true;
            }
            return false;
        }

        // Pedagang can only access their own data
        if ($user->owner_type === 'Pedagang') {
            if ($recordType === 'Pedagang' && $recordId === $user->owner_id) {
                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * Get navigation badge color based on role
     */
    protected function getRoleBadgeColor(): string
    {
        return match ($this->getOwnerType()) {
            'Admin' => 'danger',
            'Pengurus' => 'warning',
            'Produsen' => 'info',
            'Pedagang' => 'success',
            default => 'gray',
        };
    }

    /**
     * Check if user has any of the specified roles
     * 
     * @param array $roles Array of role names
     * @return bool
     */
    protected function hasAnyRole(array $roles): bool
    {
        $user = $this->getUser();
        return $user && in_array($user->owner_type, $roles, true);
    }

    /**
     * Apply owner filter to query based on user role
     * 
     * @param mixed $query The query builder
     * @param string $ownerColumn The owner_id column name
     * @param string|null $ownerType The owner_type to filter by (defaults to current user type)
     * @return mixed Modified query
     */
    protected function applyOwnerFilter($query, string $ownerColumn = 'owner_id', ?string $ownerType = null)
    {
        $user = $this->getUser();
        
        // Admin/Pengurus see all records
        if ($this->isAdminOrPengurus()) {
            return $query;
        }

        // Filter by user owner_id and owner_type
        $ownerType = $ownerType ?? $user?->owner_type;
        $ownerId = $user?->owner_id;

        if ($ownerType && $ownerId) {
            return $query->where($ownerColumn, $ownerId);
        }

        // Fallback: return empty result for unknown roles
        return $query->whereRaw('1 = 0');
    }

    /**
     * Get redirect URL based on user role
     */
    protected function getRoleRedirectUrl(): string
    {
        return match ($this->getOwnerType()) {
            'Admin', 'Pengurus' => '/admin',
            'Pedagang' => '/pedagang',
            'Produsen' => '/produsen',
            default => '/',
        };
    }
}
