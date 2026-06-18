<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolePrefixMiddleware
{
    /**
     * NUCLEAR_PREFIX_ENFORCER: Memastikan user berada di jalur URL yang sesuai dengan perannya.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->path();

        // [OPTIMASI REMOTE DB]: Jangan cek auth jika berada di halaman publik
        // Ini mencegah loading lama di landing page karena kueri remote
        $publicPaths = ['/', 'catalog', 'producers', 'statistics', 'login', 'logout'];
        if (in_array($path, $publicPaths, true) || $request->is('nota/public/*')) {
            return $next($request);
        }

        $user = auth('moonshine')->user();

        if ($user) {
            $path = $request->path();
            $ownerType = $user->owner_type;

            // [CITROROSO_ROUTING_UNIFICATION]:
            // Kita izinkan semua peran berada di /admin agar Dashboard.php bisa mengelola tampilan secara sentral.
            // Tidak perlu lagi me-redirect ke /pedagang atau /produsen yang menyebabkan 404.
        }

        return $next($request);
    }
}
