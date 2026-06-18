<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Penjualan;
use App\Models\Produk;
use App\Traits\CitroNumeric;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection; // TAMBAH INI
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas; // TAMBAH INI
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

final class PenjualanImport implements SkipsEmptyRows, ToCollection, WithCalculatedFormulas, WithHeadingRow, WithValidation
{
    use CitroNumeric;

    private $pedagangId;

    private $tanggal;

    private $produkCache;

    public $rowsImported = 0;

    public $rowsSkipped = 0;

    private $produkByIdCache;

    public function __construct($pedagangId = null, $tanggal = null)
    {
        $this->pedagangId = $pedagangId;
        // Gunakan tanggal yang dipilih Admin, atau hari ini jika kosong. Paksa ke jam 00:00:00.
        $this->tanggal = $tanggal ? Carbon::parse($tanggal)->startOfDay() : now()->startOfDay();

        // Pre-load produk: Gunakan UPPERCASE key untuk case-insensitive matching
        $allProduk = Produk::all();

        $this->produkCache = $allProduk->mapWithKeys(function ($item) {
            return [strtoupper(trim($item->nama)) => $item];
        });

        $this->produkByIdCache = $allProduk->keyBy('id');
    }

    public function collection(Collection $rows)
    {
        $payload = [];
        $produkIdsToFetch = [];
        $timestampStr = date('d/m/Y H:i');
        $dbTimestamp = now();
        $dateFormatted = $this->tanggal->format('Y-m-d 00:00:00');
        $pedagangId = $this->pedagangId;

        if (! $pedagangId && auth()->check()) {
            $user = auth()->user();
            if (isset($user->owner_type) && in_array($user->owner_type, ['Pedagang', 'App\Models\Pedagang'], true)) {
                $pedagangId = $user->owner_id;
            }
        }

        // --- STEP 1: GATHER DATA & PRODUCT IDs ---
        foreach ($rows as $row) {
            $produkId = $row['id'] ?? $row['produk_id'] ?? null;
            $namaProduk = trim($row['produk'] ?? $row['nama_produk'] ?? '');

            if (empty($produkId) && empty($namaProduk)) {
                $this->rowsSkipped++;

                continue;
            }

            $produk = null;
            if (! empty($produkId)) {
                $produk = $this->produkByIdCache->get($produkId);
            }
            if (! $produk && ! empty($namaProduk)) {
                $produk = $this->produkCache->get(strtoupper($namaProduk));
            }

            if (! $produk) {
                Log::warning("Produk tidak ditemukan saat import Excel: ID='{$produkId}', Nama='{$namaProduk}'");
                $this->rowsSkipped++;

                continue;
            }

            $titip = $this->cleanNumeric($row['ttp'] ?? $row['t'] ?? $row['titip'] ?? $row['titipan'] ?? 0);
            $sisaReturn = $this->cleanNumeric($row['s_r'] ?? $row['sr'] ?? $row['sisa_return'] ?? $row['sisa_retrn'] ?? $row['retur'] ?? $row['return'] ?? $row['rtn'] ?? 0);
            $sisaJual = $this->cleanNumeric($row['s_j'] ?? $row['sj'] ?? $row['sisa_jual'] ?? $row['sisajual'] ?? 0);
            $lakuDefault = max(0, $titip - ($sisaReturn + $sisaJual));
            $laku = isset($row['lk']) ? $this->cleanNumeric($row['lk']) : (isset($row['l']) ? $this->cleanNumeric($row['l']) : (isset($row['laku']) ? $this->cleanNumeric($row['laku']) : $lakuDefault));

            if ($titip === 0 && $sisaReturn === 0 && $sisaJual === 0 && $laku === 0) {
                $this->rowsSkipped++;

                continue;
            }

            // --- INTEGRITY SHIELD: Laku + Sisa Jual + Retur <= Titip ---
            $totalSisa = $sisaReturn + $sisaJual;
            if (($laku + $totalSisa) > $titip) {
                // Prioritaskan Titip sebagai jangkar. Sesuaikan Laku agar pas.
                $laku = max(0, $titip - $totalSisa);
                Log::warning("Import Integrity Check: Laku disesuaikan pada baris produk {$namaProduk} karena melebihi Titip.");
            }

            $isMobile = isset($row['t']) || isset($row['sr']);
            $source = $isMobile ? 'Mobile' : 'Excel';
            $ketSisa = $sisaReturn > 0 ? " [SR:{$sisaReturn}]" : '';

            $payload[$produk->id] = [
                'id' => null, // Wajib ada agar struktur kolom seragam saat upsert
                'produk_id' => $produk->id,
                'pedagang_id' => $pedagangId,
                'tanggal' => $dateFormatted,
                'titip' => $titip,
                'laku' => $laku,
                'sisa_jual' => $sisaJual,
                'harga_beli' => (float) $produk->harga_beli,
                'harga_jual' => (float) $produk->harga_jual,
                'status' => 'Draft',
                'keterangan' => "Bulk Transmit {$source} ({$timestampStr}){$ketSisa}",
                'created_at' => $dbTimestamp,
                'updated_at' => $dbTimestamp,
            ];

            $produkIdsToFetch[] = $produk->id;
        }

        if (empty($payload)) {
            return;
        }

        // --- STEP 2: PRE-FETCH EXISTING RECORD IDs (NUCLEAR OPTIMIZATION) ---
        $existing = Penjualan::where('pedagang_id', $pedagangId)
            ->where('tanggal', $dateFormatted)
            ->whereIn('produk_id', array_unique($produkIdsToFetch))
            ->get(['id', 'produk_id'])
            ->keyBy('produk_id');

        // --- STEP 3: SYNTHESIZE UPSERT PAYLOAD ---
        $upsertData = [];
        foreach ($payload as $prodId => $data) {
            if ($existing->has($prodId)) {
                $data['id'] = $existing->get($prodId)->id;
            }
            $upsertData[] = $data;
            $this->rowsImported++;
        }

        // --- STEP 4: EXECUTE NUCLEAR UPSERT (CONCURRENT) ---
        if (! empty($upsertData)) {
            $tasks = [];
            foreach (array_chunk($upsertData, 500) as $chunk) {
                $tasks[] = function () use ($chunk) {
                    Penjualan::upsert($chunk, ['id'], [
                        'titip', 'laku', 'sisa_jual', 'harga_beli', 'harga_jual',
                        'status', 'keterangan', 'updated_at',
                    ]);
                };
            }

            if (! empty($tasks)) {
                // Fallback 'sync' untuk Laragon Windows, 'process' untuk Linux Server
                Concurrency::driver(app()->environment('production') ? 'process' : 'sync')->run($tasks);
            }
        }
    }

    /**
     * 1. ANTI-ERROR: Aturan validasi per kolom
     */
    public function rules(): array
    {
        return [
            // Flexible headers
            '*.produk' => ['nullable', 'string'],
            '*.nama_produk' => ['nullable', 'string'],

            // Validasi Angka (Support shorthand mobile & standard Excel)
            '*.t' => ['nullable', 'numeric', 'min:0'],
            '*.ttp' => ['nullable', 'numeric', 'min:0'],
            '*.titip' => ['nullable', 'numeric', 'min:0'],

            '*.sr' => ['nullable', 'numeric', 'min:0'],
            '*.s_r' => ['nullable', 'numeric', 'min:0'],
            '*.sisa_return' => ['nullable', 'numeric', 'min:0'],

            '*.sj' => ['nullable', 'numeric', 'min:0'],
            '*.s_j' => ['nullable', 'numeric', 'min:0'],
            '*.sisa_jual' => ['nullable', 'numeric', 'min:0'],

            '*.l' => ['nullable', 'numeric', 'min:0'],
            '*.lk' => ['nullable', 'numeric', 'min:0'],
            '*.laku' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * 2. ANTI-ERROR: Abaikan baris yang benar-benar kosong
     */
    public function isEmptyRow(array $row): bool
    {
        return empty(array_filter($row));
    }

    /**
     * 3. ANTI-ERROR: Validasi keberadaan produk di database (via Cache)
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($validator->getData() as $key => $row) {
                $namaProduk = trim($row['produk'] ?? $row['nama_produk'] ?? '');
                if (! empty($namaProduk) && ! $this->produkCache->has($namaProduk)) {
                    $validator->errors()->add($key, "Produk '{$namaProduk}' tidak ditemukan di katalog.");
                }
            }
        });
    }

    /**
     * Set data mapping heading
     * Excel: No, Nama Produk, Harga titip, Titip, Sisa Return, Sisa Jual, laku, bayar
     */
    public function headingRow(): int
    {
        return 1;
    }
}
