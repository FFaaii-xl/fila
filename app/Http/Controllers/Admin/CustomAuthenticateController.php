<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\MerchantFinancialRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use MoonShine\Laravel\Http\Controllers\MoonShineController;

/**
 * Custom Authenticate Controller
 * Auto-detect user role based on owner_type from users2 table
 * NO role selector - security first
 */
class CustomAuthenticateController extends MoonShineController
{
    use MerchantFinancialRules;

    /**
     * Show login form
     * Auto-redirect if already authenticated
     */
    public function login(): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        // CEK: Jika sudah login, redirect langsung ke dashboard masing-masing
        if (Auth::guard('moonshine')->check()) {
            $user = Auth::guard('moonshine')->user();
            $role = $this->detectRole($user);
            
            return match ($role) {
                'produsen' => redirect('/producer-sales'),
                'pedagang' => redirect('/merchant-sales'),
                'admin', 'pengurus' => redirect('/dashboard'),
                default => redirect('/dashboard'),
            };
        }
        
        return view('admin.auth.login');
    }

    /**
     * Handle login - auto-detect role based on user data
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Use 'name' field for authentication (as per MoonShine config)
        $credentials = [
            'name' => $request->input('username'),
            'password' => $request->input('password'),
        ];

        // Try to authenticate using moonshine guard
        if (! Auth::guard('moonshine')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'username' => trans('moonshine::ui.wrong_credentials'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::guard('moonshine')->user();
        
        // SECURITY: Auto-detect role from owner_type
        // Role is determined by database, NOT by user input
        $role = $this->detectRole($user);
        
        // Redirect based on detected role
        return $this->redirectBasedOnRole($role);
    }

    /**
     * Detect role from user owner_type
     * This is SECURE because it reads from database, not from user input
     */
    protected function detectRole($user): string
    {
        if (!$user || !$user->owner_type) {
            return 'admin'; // Default fallback
        }

        return match ($user->owner_type) {
            'Pedagang' => 'pedagang',
            'Produsen' => 'produsen',
            'Pengurus' => 'pengurus',
            'Admin' => 'admin',
            default => 'admin',
        };
    }

    /**
     * Redirect user to appropriate portal based on role
     * OPTIMIZATION: Preload data for Pedagang to reduce first-page load time
     */
    protected function redirectBasedOnRole(string $role): RedirectResponse
    {
        // Ambil intended URL
        $intended = redirect()->getIntendedUrl();
        
        // Validasi: hanya redirect ke URL yang VALID dan AMAN
        if ($intended && $this->isValidAuthenticatedRoute($intended)) {
            // Clear intended URL dari session setelah digunakan
            redirect()->setIntendedUrl(null);
            return redirect($intended);
        }
        
        // OPTIMIZATION: Preload Pedagang data on login (Phase 1 - P3)
        if ($role === 'pedagang') {
            $this->preloadPedagangData();
            return redirect('/merchant-sales');
        }
        
        // Produsen preload
        if ($role === 'produsen') {
            $this->preloadProdusenData();
            return redirect('/producer-sales');
        }
        
        // Fallback ke dashboard
        return redirect('/dashboard');
    }

    /**
     * OPTIMIZATION Phase 56: Enhanced preload Pedagang data on login
     * Pre-calculate: modal_final, kas, tabungan, setoran for instant display
     * Uses FROZEN DOMAIN: getAdjustedMerchantModal() & getTieredMerchantKas()
     */
    protected function preloadPedagangData(): void
    {
        $user = Auth::guard('moonshine')->user();
        if (!$user || $user->owner_type !== 'Pedagang') {
            return;
        }

        $pedagangId = $user->owner_id;
        $today = now('Asia/Jakarta')->toDateString();
        $cacheKey = "pedagang_summary_{$pedagangId}_{$today}";
        $cacheTtl = 300; // 5 minutes

        // Skip if already cached
        if (Cache::has($cacheKey)) {
            return;
        }

        try {
            // 1. Get today's summary from sales_summaries
            $summary = DB::table('sales_summaries')
                ->where('type', 'pedagang')
                ->where('type_id', $pedagangId)
                ->where('date', $today)
                ->first();

            // 2. Get pedagango master data (tabungan_rate, nama)
            $pedagang = DB::table('pedagang')
                ->where('id', $pedagangId)
                ->whereNull('deleted_at')
                ->first(['nama', 'tabungan_rate']);

            // 3. Get tabungan balance
            $tabunganBalance = DB::table('pedagang')
                ->where('id', $pedagangId)
                ->value('tabungan') ?? 0;

            // 4. Check if already reported today
            $hasReportedToday = DB::table('penjualan')
                ->where('pedagang_id', $pedagangId)
                ->whereBetween('tanggal', [$today . ' 00:00:00', $today . ' 23:59:59'])
                ->whereNull('deleted_at')
                ->exists();

            // 5. Pre-calculate financial values (FROZEN DOMAIN logic)
            $rawModal = (float) ($summary->total_modal ?? 0);
            $itemCount = (int) ($summary->item_count ?? 0);
            $namaPedagang = $pedagang->nama ?? '';
            $tabunganRate = (float) ($pedagang->tabungan_rate ?? 0);

            // Apply ProUp via MerchantFinancialRules trait
            $modalFinal = $this->getAdjustedMerchantModal($rawModal, $itemCount, $namaPedagang);
            $kasFee = $this->getTieredMerchantKas($modalFinal);

            // 6. Cache latest item for date picker (global, not user-specific)
            Cache::remember('penjualan_latest_item', 7200, function () {
                return DB::table('penjualan')
                    ->whereNull('deleted_at')
                    ->latest('tanggal')
                    ->first();
            });

            // 7. Store comprehensive preloaded data in cache
            Cache::put($cacheKey, [
                'summary' => $summary,
                'loaded_at' => now('Asia/Jakarta')->toDateTimeString(),
                // Pre-calculated financial values
                'modal_final' => $modalFinal,
                'kas_fee' => $kasFee,
                'tabungan_rate' => $tabunganRate,
                'tabungan_balance' => $tabunganBalance,
                'setoran_total' => $modalFinal + $kasFee + $tabunganRate,
                'has_reported_today' => $hasReportedToday,
                'pedagang_nama' => $namaPedagang,
            ], $cacheTtl);
        } catch (\Exception $e) {
            // Silent fail - don't block login
            Log::warning('Pedagang preload failed: ' . $e->getMessage());
        }
    }

    /**
     * OPTIMIZATION: Preload Produsen summary data on login
     */
    protected function preloadProdusenData(): void
    {
        $user = Auth::guard('moonshine')->user();
        if (!$user || $user->owner_type !== 'Produsen') {
            return;
        }

        $produsenId = $user->owner_id;
        $today = now('Asia/Jakarta')->toDateString();
        $cacheKey = "produsen_summary_{$produsenId}_{$today}";
        $cacheTtl = 300; // 5 minutes

        // Skip if already cached
        if (Cache::has($cacheKey)) {
            return;
        }

        try {
            $summary = DB::table('sales_summaries')
                ->where('type', 'produsen')
                ->where('type_id', $produsenId)
                ->where('date', $today)
                ->first();

            Cache::put($cacheKey, [
                'summary' => $summary,
                'loaded_at' => now('Asia/Jakarta')->toDateTimeString(),
            ], $cacheTtl);
        } catch (\Exception $e) {
            Log::warning('Produsen preload failed: ' . $e->getMessage());
        }
    }

    /**
     * Validasi apakah intended URL valid untuk authenticated user
     */
    protected function isValidAuthenticatedRoute(string $url): bool
    {
        // Parse URL untuk dapat path
        $path = parse_url($url, PHP_URL_PATH);
        
        // Daftar route yang boleh di-intended (Authenticated routes)
        $allowedRoutes = [
            '/upload-penjualan', '/merchant-sales', '/producer-sales',
            '/nota-penjualan', '/tabungan-admin', '/financial-report',
            '/settings', '/insight', '/dashboard',
            '/pedagang', '/produsen', '/produk', '/account',
            '/detail-kas', '/log-saldo', '/report-multi-dimensi',
            '/catatan-setoran', '/cash-preparation', '/monitor-kiriman',
            '/stok-realtime', '/mutasi-harian-page', '/ai-dashboard',
            '/ai-sales-forecasting', '/ai-stock-recommendation',
            '/ai-merchant-segmentation', '/ai-product-recommendation',
            '/ai-chatbot', '/documentation-assistant', '/legacy-converter',
            '/health-check', '/profile-page', '/login', '/logout',
        ];
        
        // Check apakah path dimulai dengan salah satu allowed route
        foreach ($allowedRoutes as $route) {
            if (str_starts_with($path, $route)) {
                return true;
            }
        }
        
        // External URL atau route tidak dikenal -> reject
        return false;
    }

    /**
     * Logout with redirect to root
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('moonshine')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}