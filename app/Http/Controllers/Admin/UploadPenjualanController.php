<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\PenjualanTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\PenjualanImport;
use App\Models\Admin;
use App\Models\Pedagang;
use App\Models\Pengurus;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Services\BackupService;
use App\Services\LegacyFormatDetector;
use App\Services\SalesService;
use App\Services\SettingsService;
use App\Services\TemplateVersionManager;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use MoonShine\Support\Enums\ToastType;

class UploadPenjualanController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required',
            'file.*' => 'mimes:xlsx,xls,csv|max:10240',
            'pedagang_id' => 'nullable|exists:pedagang,id',
            'tanggal' => 'nullable|date',
        ]);

        $user = auth()->user();
        $isMerchant = in_array($user->owner_type ?? '', ['Pedagang', Pedagang::class, 'App\Models\Pedagang'], true);
        $isAdmin = in_array($user->owner_type ?? '', ['Admin', Admin::class, 'App\Models\Admin'], true);

        // Security: If merchant, force their own ID
        $forcedPedagangId = $isMerchant ? $user->owner_id : $request->pedagang_id;
        $files = is_array($request->file('file')) ? $request->file('file') : [$request->file('file')];
        $tanggal = $request->tanggal ?? date('Y-m-d');

        $results = [
            'success' => [],
            'failed' => [],
            'total_rows' => 0,
        ];

        // Pre-fetch merchants for Admin Bulk Mode (Sorted by length DESC to avoid partial collision)
        $allPedagangs = ($isAdmin && empty($forcedPedagangId))
            ? Pedagang::all()->sortByDesc(fn ($p) => strlen($p->nama))
            : collect();

        // --- LEGACY FORMAT AUTO-DETECTION ENGINE ---
        $legacyDetector = app(LegacyFormatDetector::class);
        $legacyConverted = [];

        foreach ($files as $file) {
            // [STABILITY_GUARD] Pastikan file valid dan path tidak kosong
            if (! $file || ! $file->isValid() || empty($file->getPathname())) {
                $results['failed'][] = ($file ? $file->getClientOriginalName() : 'File tidak dikenal').' (Gagal upload atau file rusak)';

                continue;
            }

            $currentPedagangId = $forcedPedagangId;
            $filename = $file->getClientOriginalName();
            $filenameClean = strtoupper(trim(pathinfo($filename, PATHINFO_FILENAME)));
            $merchantName = null;

            // === AUTO-DETECT LEGACY FORMAT ===
            $activeFile = $file;
            if ($legacyDetector->isLegacyFormat($file)) {
                $conversion = $legacyDetector->convertToKinetic($file);

                if ($conversion['file'] === null) {
                    $results['failed'][] = "{$filename} (Format lama terdeteksi, tapi konversi gagal — tidak ada data valid)";

                    continue;
                }

                $activeFile = $conversion['file'];
                $stats = $conversion['stats'];
                $legacyConverted[] = "{$filename} ({$stats['rows']} baris"
                    .($stats['missing'] > 0 ? ", {$stats['missing']} produk tidak ditemukan" : '')
                    .')';
            }

            // Admin Bulk Mode: Detect by filename if no ID provided (Python-Inspired Logic)
            if ($isAdmin && ! $currentPedagangId) {
                $pedagang = $this->findMerchantByFilename($filename, $allPedagangs);

                if (! $pedagang) {
                    $results['failed'][] = "{$filename} (Pedagang tidak ditemukan di database)";
                    Log::warning("Bulk Upload: Merchant not found for file '{$filename}'");

                    continue;
                }
                $currentPedagangId = $pedagang->id;
                $merchantName = $pedagang->nama;
            }

            if (! $currentPedagangId) {
                $results['failed'][] = "{$filename} (ID Pedagang tidak ditentukan)";

                continue;
            }

            // --- OLD FILE GUARD (CITROROSO ENGINE v4) ---
            // 1. Metadata Detection (Inspection of internal docProps/core.xml)
            $isOldMetadata = false;
            $metaModifiedDate = null;

            if ($file->getClientOriginalExtension() === 'xlsx') {
                try {
                    $zip = new \ZipArchive;
                    $filePath = $activeFile->getRealPath() ?: $activeFile->getPathname();
                    if (! empty($filePath) && $zip->open($filePath) === true) {
                        $coreProps = $zip->getFromName('docProps/core.xml');
                        if ($coreProps) {
                            $xml = simplexml_load_string($coreProps);
                            $namespaces = $xml->getNamespaces(true);
                            $dcterms = $xml->children($namespaces['dcterms']);
                            $metaModifiedDate = (string) $dcterms->modified;

                            if ($metaModifiedDate) {
                                $metaTs = strtotime($metaModifiedDate);
                                $metaDate = date('Y-m-d', $metaTs);

                                // Jika tanggal metadata file tidak sama dengan tanggal target pasar (Ketat terhadap Tahun)
                                if ($metaDate !== $tanggal && ! $request->has('force_old')) {
                                    $isOldMetadata = true;
                                }
                            }
                        }
                        $zip->close();
                    }
                } catch (Exception $e) {
                    Log::error('Metadata Inspection Failed: '.$e->getMessage());
                }
            }

            // 2. Filename Date Detection (Advanced Pattern Matching)
            $isOldFilename = false;
            $monthMap = [
                'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04', 'mei' => '05', 'jun' => '06',
                'jul' => '07', 'agu' => '08', 'sep' => '09', 'okt' => '10', 'nov' => '11', 'des' => '12',
                'may' => '05', 'aug' => '08', 'oct' => '10', 'dec' => '12',
            ];

            // Pattern: DD [Spasi/./_] (Mei/05)
            // Kami mengabaikan tahun karena sering terjadi typo (misal 2025 padahal 2026)
            if (preg_match('/(\d{1,2})[\s\._\-]+([a-zA-Z]{3,}|0[1-9]|1[0-2])/', $filename, $matches)) {
                $d = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $mRaw = strtolower(substr($matches[2], 0, 3));
                $m = is_numeric($matches[2]) ? str_pad($matches[2], 2, '0', STR_PAD_LEFT) : ($monthMap[$mRaw] ?? null);

                $targetD = date('d', strtotime($tanggal));
                $targetM = date('m', strtotime($tanggal));

                if ($m && ($d !== $targetD || $m !== $targetM) && ! $request->has('force_old')) {
                    $isOldFilename = true;
                }
            }

            if (($isOldMetadata || $isOldFilename) && ! $request->has('force_old')) {
                $reason = $isOldMetadata ? 'Metadata tanggal edit berbeda ('.date('d-m-Y', strtotime($metaModifiedDate)).')' : 'Nama file mengandung tanggal berbeda';
                $results['failed'][] = "{$filename} (TERDETEKSI TANGGAL BERBEDA: {$reason}. Gunakan opsi 'Paksa' jika yakin)";
                Log::warning("Date Mismatch Blocked: '{$filename}' matched as different. Meta: ".($metaModifiedDate ?? 'N/A'));

                continue;
            }
            // -------------------------

            // --- CITROROSO LOCKDOWN ---
            if ($error = $this->isSessionLocked((int) $currentPedagangId, $tanggal)) {
                $results['failed'][] = "{$filename} ({$error})";

                continue;
            }
            // -------------------------

            try {
                $import = new PenjualanImport($currentPedagangId, $tanggal);
                Excel::import($import, $activeFile);

                // [BACKUP_SHIELD] Automatic preservation of uploaded source
                app(BackupService::class)->saveUpload($file, $currentPedagangId, $tanggal);

                if (! $merchantName) {
                    $merchantName = Pedagang::find($currentPedagangId)->nama ?? 'ID:'.$currentPedagangId;
                }

                $results['success'][] = "{$merchantName} ({$import->rowsImported} item)";
                $results['total_rows'] += $import->rowsImported;
            } catch (ValidationException $e) {
                $failures = $e->failures();
                foreach ($failures as $failure) {
                    $results['failed'][] = "{$filename} [Row {$failure->row()}]: ".implode(', ', $failure->errors());
                }
            } catch (Exception $e) {
                $results['failed'][] = "{$filename} (Error: {$e->getMessage()})";
            }
        }

        if (! empty($results['success'])) {
            // Global Cleanup (Nuclear Efficiency)
            Cache::forget('active_pedagangs_list');
            Cache::forget("ops_status_{$tanggal}");
            Cache::forget("dashboard_hub_{$tanggal}");

            // Re-sync summaries if status was OK (though usually Draft, we do this for safety)
            app(SalesService::class)->refreshSummary($tanggal);
        }

        // Cleanup temporary legacy-converted files
        $legacyDetector->cleanup();

        // Prepare Response Message
        if (empty($results['success']) && empty($results['failed'])) {
            return citro_toast('Tidak ada data yang diproses.', 'info')->back();
        }

        if (! empty($results['failed'])) {
            toast('Ada kegagalan dalam upload. Cek manifest.', ToastType::ERROR);
            session()->flash('upload_manifest_errors', collect($results['failed']));
        }

        // Flash legacy conversion notice
        if (! empty($legacyConverted)) {
            $convMsg = 'Format lama terdeteksi & otomatis dikonversi: '.implode(', ', $legacyConverted);
            toast($convMsg, ToastType::WARNING);
        }

        if (! empty($results['success'])) {
            $successCount = count($results['success']);
            $totalRows = (int) $results['total_rows'];

            // List detailed success (Merchant Name (X item))
            $detailedMsg = implode(', ', $results['success']);
            $msg = "Berhasil memproses {$successCount} Merchant: {$detailedMsg}. Total: {$totalRows} baris.";

            $type = ($totalRows > 0) ? 'success' : 'warning';

            return citro_toast($msg, $type)->back();
        }

        return back();
    }

    public function downloadTemplate(TemplateVersionManager $versionManager)
    {
        $latestEntry = $versionManager->updateVersion();
        $fileName = "Form_Penjualan_{$latestEntry['version_date']}.xlsx";

        return Excel::download(new PenjualanTemplateExport($latestEntry), $fileName);
    }

    public function saveDraft(Request $request)
    {
        $request->validate([
            'pedagang_id' => 'required|exists:pedagang,id',
            'tanggal' => 'required|date',
            'items' => 'required|array',
            'items.*.produk_id' => 'required|exists:produk,id',
            'items.*.titip' => 'required|integer|min:0',
            'items.*.laku' => 'required|integer|min:0',
            'items.*.sj' => 'required|integer|min:0',
        ]);

        // --- CITROROSO LOCKDOWN ---
        if ($error = $this->isSessionLocked((int) $request->pedagang_id, $request->tanggal)) {
            return response()->json(['success' => false, 'message' => $error], 200); // Send as 200 but success:false for UI toast
        }
        // -------------------------

        try {
            DB::beginTransaction();

            foreach ($request->items as $item) {
                $titip = (int) $item['titip'];
                $laku = (int) $item['laku'];
                $sj = (int) $item['sj'];
                $sr = $titip - $laku - $sj;

                if (($laku + $sr + $sj) > $titip || $sr < 0) {
                    $produk = Produk::find($item['produk_id']);
                    throw new Exception('Produk '.($produk->nama ?? $item['produk_id']).': Total (Laku + Sisa + Retur) tidak boleh melebihi Titip.');
                }

                $produk = Produk::find($item['produk_id']);

                Penjualan::updateOrCreate(
                    [
                        'pedagang_id' => $request->pedagang_id,
                        'produk_id' => $item['produk_id'],
                        'tanggal' => $request->tanggal,
                    ],
                    [
                        'titip' => $titip,
                        'laku' => $laku,
                        'sisa_jual' => $sj,
                        'harga_beli' => $produk->harga_beli,
                        'harga_jual' => $produk->harga_jual,
                        'status' => 'Draft',
                    ]
                );
            }

            Cache::forget("ops_status_{$request->tanggal}");
            Cache::forget("dashboard_hub_{$request->tanggal}");

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Draf penjualan berhasil disimpan!']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function lockDraft(Request $request)
    {
        $request->validate([
            'pedagang_id' => 'required|exists:pedagang,id',
            'tanggal' => 'required|date',
            'force' => 'nullable|boolean',
        ]);

        if ($error = $this->isSessionLocked((int) $request->pedagang_id, $request->tanggal)) {
            return response()->json(['success' => false, 'message' => $error], 200);
        }

        $pedagangId = (int) $request->pedagang_id;
        $tanggal = $request->tanggal;

        // --- TACTICAL ISOLATION CHECK (PENGAMAN SALAH INPUT) ---
        if (! $request->force) {
            $otherMerchantsCount = DB::table('penjualan')
                ->where('tanggal', $tanggal)
                ->where('pedagang_id', '!=', $pedagangId)
                ->distinct()
                ->count('pedagang_id');

            // Kita hanya jalankan cek jika sudah ada minimal 3 pedagang lain yang input (biar tidak false positive di awal hari)
            if ($otherMerchantsCount >= 3) {
                $currentProducts = DB::table('penjualan')
                    ->where('pedagang_id', $pedagangId)
                    ->where('tanggal', $tanggal)
                    ->pluck('produk_id');

                $otherProducts = DB::table('penjualan')
                    ->where('tanggal', $tanggal)
                    ->where('pedagang_id', '!=', $pedagangId)
                    ->pluck('produk_id')
                    ->unique();

                $isolatedProductIds = $currentProducts->diff($otherProducts);

                if ($isolatedProductIds->isNotEmpty()) {
                    $uniqueNames = DB::table('produk')
                        ->whereIn('id', $isolatedProductIds)
                        ->pluck('nama')
                        ->toArray();

                    return response()->json([
                        'success' => false,
                        'is_warning' => true,
                        'message' => 'PRODUK ISOLASI TERDETEKSI!',
                        'unique_products' => $uniqueNames,
                        'details' => 'Produk di atas hanya ada di pedagang ini (pedagang lain tidak ada). Pastikan tidak salah ketik/salah input nama pedagang.',
                    ], 200);
                }
            }
        }

        try {
            DB::table('penjualan')
                ->where('pedagang_id', $request->pedagang_id)
                ->where('tanggal', $request->tanggal)
                ->where('status', 'Draft')
                ->update([
                    'keterangan' => 'Locked',
                    'updated_at' => now(),
                ]);

            // [BACKUP_SHIELD] Automatic snapshot of manual input on Lock
            app(BackupService::class)->saveManual((int) $request->pedagang_id, $request->tanggal);

            Cache::forget("ops_status_{$request->tanggal}");
            Cache::forget("dashboard_hub_{$request->tanggal}");

            app(SalesService::class)->refreshSummary($request->tanggal);

            return response()->json(['success' => true, 'message' => 'Laporan berhasil DIKUNCI! Data siap diproses admin.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function pullLastTemplate(Request $request)
    {
        $request->validate([
            'pedagang_id' => 'required|exists:pedagang,id',
            'tanggal' => 'required|date',
        ]);

        $pedagangId = $request->pedagang_id;
        $tanggal = $request->tanggal;

        // --- CITROROSO LOCKDOWN ---
        if ($error = $this->isSessionLocked((int) $pedagangId, $tanggal)) {
            return citro_toast($error, 'error')->back();
        }
        // -------------------------

        // 1. Cari tanggal terakhir
        $latestDate = DB::table('penjualan')
            ->whereNull('deleted_at')
            ->where('pedagang_id', $pedagangId)
            ->where('tanggal', '<', $tanggal)
            ->orderBy('tanggal', 'desc')
            ->value('tanggal');

        if (! $latestDate) {
            return citro_toast('Tidak ditemukan transaksi sebelumnya untuk pedagang ini.', 'warning')->back();
        }

        // 2. Ambil semua produk dari tanggal terakhir
        $items = DB::table('penjualan')
            ->whereNull('deleted_at')
            ->where('tanggal', $latestDate)
            ->where('pedagang_id', $pedagangId)
            ->get();

        // 3. Merge ke hari ini (Zero Fill)
        $count = 0;
        foreach ($items as $item) {
            // INSERT IGNORE / updateOrInsert: Kita tidak ingin menimpa data yang SUDAH ADA hari ini
            $exists = DB::table('penjualan')
                ->where('pedagang_id', $pedagangId)
                ->where('produk_id', $item->produk_id)
                ->where('tanggal', $tanggal)
                ->exists();

            if (! $exists) {
                DB::table('penjualan')->insert([
                    'pedagang_id' => $pedagangId,
                    'produk_id' => $item->produk_id,
                    'tanggal' => $tanggal,
                    'titip' => 0,
                    'laku' => 0,
                    'sisa_jual' => 0,
                    'harga_beli' => $item->harga_beli,
                    'harga_jual' => $item->harga_jual,
                    'status' => 'Draft',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }
        }

        Cache::forget("ops_status_{$tanggal}");
        Cache::forget("dashboard_hub_{$tanggal}");

        return citro_toast("Berhasil memuat {$count} produk baru dari template terakhir ({$latestDate}).", 'success')->back();
    }

    public function saveSortOrder(Request $request)
    {
        $request->validate([
            'pedagang_id' => 'required|integer',
            'sort_order' => 'required|array',
        ]);

        $path = "pedagang_sorts/{$request->pedagang_id}.json";
        Storage::put($path, json_encode($request->sort_order));

        return response()->json(['success' => true]);
    }

    /**
     * Sinkronisasi Manual Sales Summary (Level Admin)
     * Digunakan setelah edit database langsung via SQL editor
     */
    public function sync(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $service = app(SalesService::class);

        for ($i = 0; $i <= $days; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $service->refreshSummary($date);
            Cache::forget("ops_status_{$date}");
            Cache::forget("dashboard_hub_{$date}");
        }

        // Invalidate common caches
        Cache::forget('active_pedagangs_list');

        return citro_toast("Rekonstruksi data ($days hari) berhasil silakukan!", 'success')->back();
    }

    /**
     * CITROROSO AUTO-RENAME ENGINE (Python Port)
     * Detects merchant by analyzing filename patterns
     */
    private function findMerchantByFilename(string $filename, $merchants)
    {
        $filenameLower = strtolower(pathinfo($filename, PATHINFO_FILENAME));

        // CITROROSO ALIAS & PHONETIC MAP (Heritage Typo Guard)
        $aliasMap = [
            'siti' => ['busiti', 'bu siti', 'mrs siti', 'siti'],
            'bu siti' => ['busiti', 'bu siti', 'siti'],
            'busiti' => ['busiti', 'bu siti', 'siti'],
            'rudi' => ['rudy', 'rudie'],
            'yusuf' => ['m yusuf', 'moh yusuf', 'mohammad yusuf'],
            'sutris' => ['p sutris', 'pak sutris'],
            'man' => ['p. man', 'p man', 'pak man'],
            'sholikin' => ['sholihin', 'sholikin', 'solikin', 'solihin'],
            'joko' => ['joko sutopo', 'pak joko'],
            'weryadi' => ['weryadi9meo', 'weryadi'],
        ];

        // LOGIKA 0: Alias Match (Prioritas Utama)
        foreach ($merchants as $m) {
            $name = strtolower($m->nama);
            if (isset($aliasMap[$name])) {
                foreach ($aliasMap[$name] as $alias) {
                    if (str_contains($filenameLower, strtolower($alias))) {
                        return $m;
                    }
                }
            }
        }

        // LOGIKA 1: Pencarian Kata Utuh (Regex Word Boundary)
        foreach ($merchants as $m) {
            $name = strtolower($m->nama);
            if (preg_match("/\b".preg_quote($name, '/')."\b/i", $filenameLower)) {
                return $m;
            }
        }

        // LOGIKA 2: Substring Match (Fallback)
        // Merchants sudah disort dari nama terpanjang di controller
        foreach ($merchants as $m) {
            $name = strtolower($m->nama);
            if (str_contains($filenameLower, $name)) {
                return $m;
            }
        }

        // LOGIKA 3: Fuzzy Matching untuk Typo
        $cleanName = preg_replace('/[0-9]+.*/', '', $filenameLower);
        $cleanName = trim(preg_replace('/[^a-z]/', ' ', $cleanName));

        if (empty($cleanName)) {
            return null;
        }

        $matches = [];
        foreach ($merchants as $m) {
            $target = strtolower($m->nama);
            $dist = levenshtein($cleanName, $target);
            $maxLen = max(strlen($cleanName), strlen($target));

            if ($maxLen === 0) {
                continue;
            }

            $ratio = 1 - ($dist / $maxLen);

            if ($ratio >= 0.7) { // Diperketat ke 0.7
                $matches[] = [
                    'merchant' => $m,
                    'ratio' => $ratio,
                ];
            }
        }

        if (empty($matches)) {
            return null;
        }

        usort($matches, fn ($a, $b) => $b['ratio'] <=> $a['ratio']);

        if (count($matches) > 1) {
            $best = $matches[0];
            $second = $matches[1];

            if (($best['ratio'] - $second['ratio']) < 0.1) {
                Log::warning("Bulk Upload Ambiguity: '{$filename}' hampir mirip antara {$best['merchant']->nama} dan {$second['merchant']->nama}.");

                return null;
            }
        }

        return $matches[0]['merchant'];
    }

    /**
     * CITROROSO LOCKDOWN SYSTEM (HHR v3)
     */
    private function isSessionLocked(int $pedagangId, string $tanggal): ?string
    {
        $user = auth()->user();
        $isAdmin = in_array($user->owner_type ?? '', ['Admin', Admin::class, 'App\Models\Admin'], true);
        $isPengurus = in_array($user->owner_type ?? '', ['Pengurus', Pengurus::class, 'App\Models\Pengurus'], true);

        // 1. Admin & Pengurus are EXEMPT for operational flexibility
        if ($isAdmin || $isPengurus) {
            return null;
        }

        // 2. STATUS LOCK: Block if already Paid/Settled by Admin OR LOCKED by merchant
        $statusCheck = Penjualan::where('pedagang_id', $pedagangId)
            ->whereBetween('tanggal', ["$tanggal 00:00:00", "$tanggal 23:59:59"])
            ->select('status', 'keterangan')
            ->get();

        if ($statusCheck->contains(fn ($p) => in_array($p->status, ['Ok', 'Pending'], true))) {
            return 'Laporan Sedang Diproses/Selesai. Terkunci.';
        }

        if ($statusCheck->contains(fn ($p) => $p->status === 'Draft' && $p->keterangan === 'Locked')) {
            return 'Laporan sudah dikunci. Silakan hubungi admin untuk pembukaan kunci.';
        }

        // 3. DEADLINE LOCK: Block if deadline passed (Only for TODAY)
        $settings = app(SettingsService::class);
        if ($settings->get('submission_deadline_active') && $tanggal === date('Y-m-d')) {
            $deadline = $settings->get('submission_deadline_time', '14:00');
            if (date('H:i') > $deadline) {
                return "Batas waktu pengisian (Deadline: {$deadline}) telah lewat.";
            }
        }

        return null;
    }
}
