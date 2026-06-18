<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Admin;
use App\Models\Pedagang;
use App\Models\Pengurus;
use App\Models\Penjualan;
use App\Services\SalesService;
use App\Services\SettingsService;
use App\Services\TemplateVersionManager;
use App\Traits\MerchantFinancialRules;
use App\Traits\Filament\HasRoleAuthorization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UploadPenjualanPage extends Page
{
    use MerchantFinancialRules;
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static string | \UnitEnum | null $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Upload Penjualan';

    protected string $view = 'filament.pages.upload-penjualan-page';

    public static function canAccess(): bool
    {
        return !(new static)->isProdusen(); // Produsen cannot access
    }

    protected function getViewData(): array
    {
        $versionManager = app(TemplateVersionManager::class);
        $status = $versionManager->getRecentlyChangedData();

        $history = $versionManager->getHistory();
        $currentVersion = $status['version_date'];
        $hasChanges = $status['has_changes'];

        $user = auth()->user();
        
        $isAdmin = $this->isAdmin();
        $isPengurus = $this->hasAnyRole(['Pengurus']);
        $isPedagang = $this->isPedagang();

        // --- 1. Manual Matrix Setup & Session Persistence ---
        $pedagangId = (int) (request('pedagang_id') ?? ($isPedagang ? $user->owner_id : null));
        $tanggal = request('tanggal') ?? now()->toDateString();

        // --- ERROR/WARNING/SUCCESS MANIFEST (Available via session in blade) ---

        // --- CITROROSO LOCKDOWN (UI Logic) ---
        $lockError = null;
        if (! $isAdmin && ! $isPengurus && $pedagangId) {
            // Status Lock
            $isPaid = Penjualan::where('pedagang_id', $pedagangId)
                ->whereBetween('tanggal', ["$tanggal 00:00:00", "$tanggal 23:59:59"])
                ->where('status', 'Ok')
                ->exists();

            if ($isPaid) {
                $lockError = 'Nota Sudah Dicetak. Laporan Terkunci.';
            } else {
                // Deadline Lock
                $settings = app(SettingsService::class);
                if ($settings->get('submission_deadline_active') && $tanggal === now()->toDateString()) {
                    $deadline = $settings->get('submission_deadline_time', '14:00');
                    if (now()->format('H:i') > $deadline) {
                        $lockError = "Batas waktu pengisian (Deadline: {$deadline}) telah lewat.";
                    }
                }
            }
        }
        $isLocked = ! is_null($lockError);
        // -------------------------

        $settings = app(SettingsService::class);
        $deadlineTime = $settings->get('submission_deadline_time', '14:00');
        $deadlineActive = $settings->get('submission_deadline_active', false);
        $serverTime = now()->toDateTimeString();
        $isExempt = $isAdmin || $isPengurus;
        $roleLabel = $this->getRoleLabel();
        $modeLabel = $pedagangId ? 'Editor' : 'Library';

        $pedagang = $pedagangId ? Pedagang::find($pedagangId) : null;
        $salesService = app(SalesService::class);

        // Data for Library View
        $sentMerchants = collect([]);
        $notSentMerchants = collect([]);

        if (! $pedagangId) {
            $hubData = $salesService->getDashboardHubData($tanggal);
            $sortBy = request('sort_by', 'nama');
            $sortOrder = request('sort_order', 'asc');

            $sentMerchants = $hubData['pedagang']->map(function ($m) {
                return (object) [
                    'pedagang_id' => $m->id,
                    'nama' => $m->nama,
                    'sku_count' => $m->produk_count,
                    'total_titip' => $m->titip,
                    'total_laku' => $m->laku,
                    'total_modal' => (float) $m->setoran_modal,
                    'total_omset' => (float) $m->total_omset,
                    'raw_modal' => (float) $m->setoran_modal,
                    'total_laba' => (float) $m->total_omset - (float) $m->setoran_modal,
                    'sent_at' => $m->sent_at ?? null,
                ];
            });

            // Apply Sorting
            if ($sortBy === 'waktu') {
                $sentMerchants = $sortOrder === 'desc'
                    ? $sentMerchants->sortByDesc('sent_at')
                    : $sentMerchants->sortBy('sent_at');
            } else {
                $sentMerchants = $sortOrder === 'desc'
                    ? $sentMerchants->sortByDesc('nama')
                    : $sentMerchants->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE);
            }

            $notSentMerchants = $hubData['belum_kirim']->pluck('nama');
        }

        // Load Items for Matrix View
        $items = $this->getMatrixItems($pedagangId, $tanggal);
        
        $allProducts = Cache::remember('all_products_matrix', 86400, function () {
            return DB::table('produk as pdk')
                ->join('produsen as prd', 'pdk.produsen_id', '=', 'prd.id')
                ->whereNull('pdk.deleted_at')
                ->select(['pdk.id', 'pdk.nama', 'pdk.harga_beli', 'pdk.harga_jual', 'prd.nama as produsen_nama'])
                ->get();
        });

        return [
            'history' => $history,
            'currentVersion' => $currentVersion,
            'hasChanges' => $hasChanges,
            'deadlineTime' => $deadlineTime,
            'deadlineActive' => $deadlineActive,
            'serverTime' => $serverTime,
            'isExempt' => $isExempt,
            'roleLabel' => $roleLabel,
            'modeLabel' => $modeLabel,
            'pedagangId' => $pedagangId,
            'tanggal' => $tanggal,
            'isLocked' => $isLocked,
            'lockError' => $lockError,
            'pedagang' => $pedagang,
            'sentMerchants' => $sentMerchants,
            'notSentMerchants' => $notSentMerchants,
            'items' => $items,
            'allProducts' => $allProducts,
            'isAdmin' => $isAdmin,
            'isPengurus' => $isPengurus,
            'isPedagang' => $isPedagang,
            'sortBy' => request('sort_by', 'nama'),
            'sortOrder' => request('sort_order', 'asc'),
        ];
    }

    private function getMatrixItems($pedagangId, $selectedDate): Collection
    {
        if (! $pedagangId) {
            return collect([]);
        }

        $todayItems = DB::table('penjualan as p')
            ->join('produk as pdk', 'p.produk_id', '=', 'pdk.id')
            ->join('produsen as prd', 'pdk.produsen_id', '=', 'prd.id')
            ->whereNull('p.deleted_at')
            ->whereBetween('p.tanggal', ["$selectedDate 00:00:00", "$selectedDate 23:59:59"])
            ->where('p.pedagang_id', $pedagangId)
            ->select([
                'p.id',
                'p.produk_id',
                'pdk.nama as produk_nama',
                'prd.nama as produsen_nama',
                'prd.gender as produsen_gender',
                'pdk.created_at as produk_created_at',
                'p.titip',
                'p.laku',
                'p.sisa_jual',
                'p.harga_jual',
                'p.harga_beli',
                'p.status',
            ])
            ->get();

        $result = collect([]);

        foreach ($todayItems as $item) {
            $result->push([
                'id' => $item->id,
                'produk_id' => $item->produk_id,
                'nama' => $item->produk_nama,
                'produsen_nama' => $item->produsen_nama,
                'produsen_gender' => $item->produsen_gender,
                'created_at' => $item->produk_created_at,
                'titip' => (int) $item->titip,
                'sr' => (int) ($item->titip - $item->laku - $item->sisa_jual),
                'sj' => (int) $item->sisa_jual,
                'laku' => (int) $item->laku,
                'bayar' => (float) ($item->laku * $item->harga_beli),
                'rowOmset' => (float) ($item->laku * $item->harga_jual),
                'harga_beli' => (float) $item->harga_beli,
                'harga_jual' => (float) $item->harga_jual,
                'status' => $item->status,
            ]);
        }

        $path = "pedagang_sorts/{$pedagangId}.json";
        $sortOrder = Storage::exists($path) ? json_decode(Storage::get($path), true) : [];

        if (! empty($sortOrder)) {
            $orderMap = array_flip($sortOrder);
            return $result->sortBy(fn ($item) => $orderMap[$item['produk_id']] ?? 9999)->values();
        }

        return $result->sortBy('nama')->values();
    }
}
