<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OnlineUsersService;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnlineUsersController extends Controller
{
    public function __construct(
        private OnlineUsersService $onlineUsers,
        private ActivityLogService $activityLog
    ) {}

    /**
     * API: Get online users (JSON)
     */
    public function apiIndex(): JsonResponse
    {
        $users = $this->onlineUsers->getOnlineUsers();
        $userIds = $users->pluck('user_id')->toArray();
        $activities = $this->activityLog->getRecentActivitiesForUsers($userIds, 3);

        $usersWithActivities = $users->map(function ($user) use ($activities) {
            $user['activities'] = $activities->get($user['user_id'], collect())->toArray();
            return $user;
        });

        return response()->json([
            'count' => $users->count(),
            'users' => $usersWithActivities,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Page: Online Users List
     */
    public function index(Request $request)
    {
        $users = $this->onlineUsers->getOnlineUsers();
        $userIds = $users->pluck('user_id')->toArray();
        $activities = $this->activityLog->getRecentActivitiesForUsers($userIds, 5);

        $usersWithActivities = $users->map(function ($user) use ($activities) {
            $user['activities'] = $activities->get($user['user_id'], collect())->toArray();
            return $user;
        });

        // Filter by role if requested
        $role = $request->get('role');
        if ($role) {
            $usersWithActivities = $usersWithActivities->filter(function ($user) use ($role) {
                return strtolower($user['role']['label']) === strtolower($role);
            })->values();
        }

        // Search by name
        $search = $request->get('search');
        if ($search) {
            $usersWithActivities = $usersWithActivities->filter(function ($user) use ($search) {
                return stripos($user['name'], $search) !== false 
                    || stripos($user['email'], $search) !== false;
            })->values();
        }

        return view('admin.online-users', [
            'onlineUsers' => $usersWithActivities,
            'onlineCount' => $users->count(),
            'filters' => [
                'role' => $role,
                'search' => $search,
            ],
        ]);
    }
}
