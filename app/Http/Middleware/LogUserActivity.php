<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use App\Services\OnlineUsersService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    public function __construct(
        private ActivityLogService $activityLog,
        private OnlineUsersService $onlineUsers
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Update session & log activity if user is authenticated
        if ($request->user()) {
            $userId = $request->user()->id ?? null;
            
            // Update session last_activity (keep user "online")
            if ($userId) {
                $this->onlineUsers->updateActivity($userId);
            }
            
            // Log the activity
            $this->logActivity($request);
        }

        return $response;
    }

    /**
     * Log user activity based on request
     */
    private function logActivity(Request $request): void
    {
        $userId = $request->user()->id ?? null;
        if (!$userId) {
            return;
        }

        $action = $this->determineAction($request);
        $description = $this->getDescription($request, $action);

        $this->activityLog->log($userId, $action, $description);
    }

    /**
     * Determine action based on route
     */
    private function determineAction(Request $request): string
    {
        $route = $request->route()?->getName() ?? $request->path();
        
        return match (true) {
            // Auth
            str_contains($route, 'login') && $request->isMethod('post') => 'login',
            str_contains($route, 'logout') => 'logout',
            
            // Dashboard
            str_contains($route, 'dashboard') => 'view_dashboard',
            
            // Penjualan
            str_contains($route, 'penjualan') && $request->isMethod('post') => 'input_penjualan',
            str_contains($route, 'penjualan') => 'view_penjualan',
            
            // Nota
            str_contains($route, 'nota') && str_contains($route, 'print') => 'print_nota',
            str_contains($route, 'nota') => 'view_nota',
            
            // Settlement
            str_contains($route, 'settlement') => 'settlement',
            
            // Sales
            str_contains($route, 'sales') => 'view_sales',
            
            // Reports
            str_contains($route, 'report') => 'view_report',
            str_contains($route, 'export') => 'export_data',
            
            // Settings
            str_contains($route, 'settings') && $request->isMethod('post') => 'update_settings',
            str_contains($route, 'settings') => 'view_settings',
            
            default => 'browse',
        };
    }

    /**
     * Get detailed description
     */
    private function getDescription(Request $request, string $action): string
    {
        $actions = ActivityLogService::actions();
        
        // Customize description based on context
        if ($action === 'input_penjualan' && $request->has('items')) {
            $count = count($request->input('items', []));
            return "Input penjualan - {$count} items";
        }

        if ($action === 'print_nota' && $request->route('id')) {
            return "Print nota #{$request->route('id')}";
        }

        return $actions[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }
}