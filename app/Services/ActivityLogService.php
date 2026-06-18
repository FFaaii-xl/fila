<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActivityLogService
{
    protected string $table = 'activity_logs';

    /**
     * Check if table exists
     */
    private function tableExists(): bool
    {
        try {
            DB::table($this->table)->limit(1)->get();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Log user activity
     */
    public function log(int $userId, string $action, ?string $description = null, ?string $ipAddress = null): void
    {
        if (!$this->tableExists()) {
            return;
        }

        try {
            DB::table($this->table)->insert([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description ?? $action,
                'ip_address' => $ipAddress ?? request()->ip(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Activity log failed: ' . $e->getMessage());
        }
    }

    /**
     * Get recent activities for a user
     */
    public function getUserActivities(int $userId, int $limit = 10): \Illuminate\Support\Collection
    {
        if (!$this->tableExists()) {
            return collect();
        }

        try {
            return DB::table($this->table)
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get recent activities for online users
     */
    public function getRecentActivitiesForUsers(array $userIds, int $limit = 3): \Illuminate\Support\Collection
    {
        if (empty($userIds) || !$this->tableExists()) {
            return collect();
        }

        try {
            return DB::table($this->table)
                ->whereIn('user_id', $userIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('user_id')
                ->map(fn($items) => $items->take($limit)->values());
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get activity summary for dashboard
     */
    public function getActivitySummary(int $userId): array
    {
        $recent = $this->getUserActivities($userId, 5);
        
        return [
            'activities' => $recent,
            'last_action' => $recent->first()?->description ?? 'Online',
            'last_time' => $recent->first()?->created_at 
                ? Carbon::parse($recent->first()->created_at)->diffForHumans() 
                : 'Now',
        ];
    }

    /**
     * Predefined actions
     */
    public static function actions(): array
    {
        return [
            // Auth
            'login' => 'Login success',
            'logout' => 'Logout',
            'login_failed' => 'Login failed',
            
            // Admin
            'view_dashboard' => 'View dashboard',
            'view_settings' => 'View settings',
            'update_settings' => 'Update settings',
            'manage_users' => 'Manage users',
            
            // Pedagang
            'input_penjualan' => 'Input penjualan',
            'print_nota' => 'Print nota',
            'view_nota' => 'View nota',
            'settlement' => 'Settlement',
            
            // Produsen
            'view_sales' => 'View sales',
            'update_stok' => 'Update stok',
            
            // Umum
            'view_report' => 'View report',
            'export_data' => 'Export data',
        ];
    }
}
