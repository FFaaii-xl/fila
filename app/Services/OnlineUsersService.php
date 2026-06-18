<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OnlineUsersService
{
    // Perpanjang timeout dari 5 ke 30 menit
    private int $timeoutMinutes = 30;

    /**
     * Get online users with their latest activity
     */
    public function getOnlineUsers(): \Illuminate\Support\Collection
    {
        $cutoff = Carbon::now()->subMinutes($this->timeoutMinutes)->timestamp;

        $sessions = DB::table('sessions')
            ->where('last_activity', '>=', $cutoff)
            ->whereNotNull('user_id')
            ->orderBy('last_activity', 'desc')
            ->get();

        // [BUG_FIX] Deduplicate by user_id — keep only the most recent session per user.
        // This prevents counting the same user multiple times when they have multiple tabs open.
        $sessions = $sessions->unique('user_id')->values();

        $userIds = $sessions->pluck('user_id')->unique()->values();

        // Jika tidak ada sesi aktif, coba gunakan activity_logs sebagai fallback
        if ($userIds->isEmpty()) {
            return $this->getRecentActivityFallback();
        }

        $accounts = User::whereIn('id', $userIds)->get()->keyBy('id');

        return $sessions->map(function ($session) use ($accounts) {
            $account = $accounts->get($session->user_id);
            if (!$account) {
                return null;
            }

            return [
                'session_id' => $session->id,
                'user_id' => $session->user_id,
                'account' => $account,
                'role' => $this->getRoleLabel($account),
                'name' => $this->getUserName($account),
                'email' => $account->username ?? '',
                'last_activity' => Carbon::createFromTimestamp($session->last_activity),
                'last_activity_ago' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                'source' => 'session',
            ];
        })->filter()->values();
    }

    /**
     * Fallback: Get users from activity_logs (last 30 minutes)
     */
    private function getRecentActivityFallback(): \Illuminate\Support\Collection
    {
        $cutoff = Carbon::now()->subMinutes(30);

        try {
            $activities = DB::table('activity_logs')
                ->where('created_at', '>=', $cutoff)
                ->distinct('user_id')
                ->pluck('user_id');

            if ($activities->isEmpty()) {
                return collect();
            }

            $accounts = User::whereIn('id', $activities)->get()->keyBy('id');

            // Get last activity per user
            $lastActivities = DB::table('activity_logs')
                ->where('created_at', '>=', $cutoff)
                ->orderBy('created_at', 'desc')
                ->get()
                ->unique('user_id')
                ->values();

            return $lastActivities->map(function ($activity) use ($accounts) {
                $account = $accounts->get($activity->user_id);
                if (!$account) {
                    return null;
                }

                return [
                    'session_id' => 'activity-' . $activity->user_id,
                    'user_id' => $activity->user_id,
                    'account' => $account,
                    'role' => $this->getRoleLabel($account),
                    'name' => $this->getUserName($account),
                    'email' => $account->username ?? '',
                    'last_activity' => Carbon::parse($activity->created_at),
                    'last_activity_ago' => Carbon::parse($activity->created_at)->diffForHumans(),
                    'source' => 'activity_log',
                ];
            })->filter()->values();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get online users count
     */
    public function getOnlineCount(): int
    {
        $cutoff = Carbon::now()->subMinutes($this->timeoutMinutes)->timestamp;

        $count = DB::table('sessions')
            ->where('last_activity', '>=', $cutoff)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        // Fallback to activity_logs if no active sessions
        if ($count === 0) {
            try {
                $cutoff = Carbon::now()->subMinutes(30);
                $count = DB::table('activity_logs')
                    ->where('created_at', '>=', $cutoff)
                    ->distinct('user_id')
                    ->count('user_id');
            } catch (\Exception $e) {
                // Ignore
            }
        }

        return $count;
    }

    /**
     * Get role label with icon
     */
    private function getRoleLabel(User $account): array
    {
        $type = $account->owner_type;
        
        return match (true) {
            str_contains($type, 'Admin') => ['icon' => '👑', 'label' => 'Admin'],
            str_contains($type, 'Pengurus') => ['icon' => '👤', 'label' => 'Pengurus'],
            str_contains($type, 'Pedagang') => ['icon' => '🛒', 'label' => 'Pedagang'],
            str_contains($type, 'Produsen') => ['icon' => '🏭', 'label' => 'Produsen'],
            default => ['icon' => '👤', 'label' => 'Unknown'],
        };
    }

    /**
     * Get user display name
     */
    private function getUserName(User $account): string
    {
        $type = $account->owner_type;
        
        if (str_contains($type, 'Admin')) {
            return 'Admin';
        }

        if (str_contains($type, 'Pengurus') && $account->owner) {
            return $account->owner->nama ?? 'Pengurus';
        }

        if (str_contains($type, 'Pedagang') && $account->owner) {
            return 'Pedagang ' . ($account->owner->nama ?? 'ABC');
        }

        if (str_contains($type, 'Produsen') && $account->owner) {
            return 'Produsen ' . ($account->owner->nama ?? 'XYZ');
        }

        return $account->name ?? $account->username;
    }

    /**
     * Update last activity for current user (called on every authenticated request)
     */
    public function updateActivity(?int $userId): void
    {
        if (!$userId) {
            return;
        }

        try {
            $now = Carbon::now()->timestamp;
            
            // Update session last_activity
            DB::table('sessions')
                ->where('user_id', $userId)
                ->update(['last_activity' => $now]);
        } catch (\Exception $e) {
            // Silently fail - session update is not critical
        }
    }
}
