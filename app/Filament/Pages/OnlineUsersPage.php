<?php

namespace App\Filament\Pages;

use App\Services\ActivityLogService;
use App\Services\OnlineUsersService;
use App\Traits\Filament\HasRoleAuthorization;
use Carbon\Carbon;
use Filament\Pages\Page;

class OnlineUsersPage extends Page
{
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static string | \UnitEnum | null $navigationGroup = 'AI & Utilities';
    protected static ?int $navigationSort = 112;
    protected static ?string $title = 'Online Users';

    protected string $view = 'filament.pages.online-users-page';

    public static function canAccess(): bool
    {
        return (new static)->isAdminOrPengurus();
    }

    protected function getViewData(): array
    {
        $onlineUsersService = app(OnlineUsersService::class);
        $activityLogService = app(ActivityLogService::class);

        $users = $onlineUsersService->getOnlineUsers();
        $userIds = $users->pluck('user_id')->toArray();
        $activities = $activityLogService->getRecentActivitiesForUsers($userIds, 5);

        $usersWithActivities = $users->map(function ($user) use ($activities) {
            $user['activities'] = $activities->get($user['user_id'], collect())->toArray();
            return $user;
        })->toArray();

        return [
            'usersWithActivities' => $usersWithActivities,
            'onlineCount' => count($usersWithActivities),
        ];
    }
}
