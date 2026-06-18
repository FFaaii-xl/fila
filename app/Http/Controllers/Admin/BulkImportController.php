<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ModelExport;
use App\Http\Controllers\Controller;
use App\Imports\PedagangImport;
use App\Imports\ProdukImport;
use App\Imports\ProdusenImport;
use App\Models\Account;
use App\Models\DetailKas;
use App\Models\LogProduk;
use App\Models\Pedagang;
use App\Models\Pembulatan;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\Produsen;
use App\Traits\HasNuclearPrefetch;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use MoonShine\Support\Enums\ToastType;

class BulkImportController extends Controller
{
    use HasNuclearPrefetch;

    public function import(Request $request, string $type)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);

        $file = $request->file('file');

        try {
            switch ($type) {
                case 'produk':
                    Excel::import(new ProdukImport, $file);
                    break;
                case 'pedagang':
                    Excel::import(new PedagangImport, $file);
                    break;
                case 'produsen':
                    Excel::import(new ProdusenImport, $file);
                    break;
                default:
                    return back()->with('toast', [
                        'type' => ToastType::ERROR->value,
                        'message' => 'Tipe import tidak valid.',
                    ]);
            }

            return back()->with('toast', [
                'type' => ToastType::SUCCESS->value,
                'message' => 'Data '.ucfirst($type).' berhasil diimport!',
            ]);

        } catch (Exception $e) {
            return back()->with('toast', [
                'type' => ToastType::ERROR->value,
                'message' => 'Error import: '.$e->getMessage(),
            ]);
        }
    }

    public function template(string $type)
    {
        // Menyediakan file CSV template sederhana
        $headers = [];
        $filename = "template_{$type}.csv";

        switch ($type) {
            case 'produk':
                $headers = ['Nama Produk', 'Nama Produsen', 'Harga Beli', 'Harga Jual'];
                break;
            case 'pedagang':
            case 'produsen':
                $headers = ['Nama', 'Gender', 'Tabungan', 'Bundle Ke']; // Bundle ke khusus produsen, biarkan saja sbg kombo
                if ($type === 'pedagang') {
                    $headers = ['Nama', 'Gender', 'Tabungan'];
                }
                break;
            default:
                abort(404);
        }

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    public function export(string $type)
    {
        $filename = "data_{$type}_".now()->format('Ymd_His').'.xlsx';
        $user = auth()->user();
        $counter = 0;

        switch ($type) {
            case 'produk':
                $data = Produk::query()
                    ->select('produk.*')
                    ->leftJoin('produsen', 'produk.produsen_id', '=', 'produsen.id')
                    ->addSelect('produsen.nama as produsen_nama')
                    ->get();

                $perfData = $this->getPerfData('produk', $user);

                $headers = ['#', 'ID', 'Nama Produk', 'Produsen', 'H.TTP', 'H.JL', 'Perf', 'T.TTP'];
                $map = function ($row) use (&$counter, $perfData) {
                    $counter++;
                    $perf = $perfData[$row->id] ?? null;
                    $perfPercent = $perf && $perf->titip > 0 ? round(($perf->laku / $perf->titip) * 100).'%' : '-';
                    $lastTtp = $perf ? $this->getRelativeLabel($perf->last_date) : '-';

                    return [
                        $counter,
                        $row->id,
                        $row->nama,
                        $row->produsen_nama,
                        $row->harga_beli,
                        $row->harga_jual,
                        $perfPercent,
                        $lastTtp,
                    ];
                };
                break;

            case 'pedagang':
                $data = Pedagang::query()
                    ->leftJoin('saldo', function ($join) {
                        $join->on('pedagang.id', '=', 'saldo.owner_id')
                            ->where('saldo.owner_type', '=', 'Pedagang');
                    })
                    ->select('pedagang.*', 'saldo.jumlah as saldo_jumlah')
                    ->hanyaAktif()
                    ->get();

                $perfData = $this->getPerfData('pedagang', $user);

                $headers = ['#', 'ID', 'Nama', 'JK', 'Rate', 'Tabungan', 'Saldo', 'Perf', 'T.TTP'];
                $map = function ($row) use (&$counter, $perfData) {
                    $counter++;
                    $perf = $perfData[$row->id] ?? null;
                    $perfPercent = $perf && $perf->titip > 0 ? round(($perf->laku / $perf->titip) * 100).'%' : '-';
                    $lastTtp = $perf ? $this->getRelativeLabel($perf->last_date) : '-';

                    return [
                        $counter,
                        $row->id,
                        $row->nama,
                        $row->gender === 'male' ? 'L' : 'P',
                        $row->tabungan_rate,
                        $row->tabungan,
                        $row->saldo_jumlah ?? 0,
                        $perfPercent,
                        $lastTtp,
                    ];
                };
                break;

            case 'produsen':
                $subQuery = DB::table('produk')
                    ->select('produsen_id')
                    ->selectRaw('COUNT(id) as produks_count')
                    ->selectRaw('GROUP_CONCAT(nama SEPARATOR ", ") as produks_names_raw')
                    ->whereNull('deleted_at')
                    ->groupBy('produsen_id');

                $data = Produsen::query()
                    ->leftJoinSub($subQuery, 'produk_stats', 'produsen.id', '=', 'produk_stats.produsen_id')
                    ->select('produsen.*', 'produk_stats.produks_count', 'produk_stats.produks_names_raw')
                    ->get();

                $perfData = $this->getPerfData('produsen', $user);

                $headers = ['#', 'ID', 'Produsen', 'Kel', 'JK', 'Rate', 'Total Tabungan', 'Produk', 'Perf', 'T.TTP'];
                $map = function ($row) use (&$counter, $perfData) {
                    $counter++;
                    $perf = $perfData[$row->id] ?? null;
                    $perfPercent = $perf && $perf->titip > 0 ? round(($perf->laku / $perf->titip) * 100).'%' : '-';
                    $lastTtp = $perf ? $this->getRelativeLabel($perf->last_date) : '-';

                    return [
                        $counter,
                        $row->id,
                        $row->nama,
                        $row->bundle_ke,
                        $row->gender === 'male' ? 'L' : 'P',
                        $row->tabungan_rate,
                        $row->tabungan,
                        $row->produks_names_raw ?? '-',
                        $perfPercent,
                        $lastTtp,
                    ];
                };
                break;

            case 'account':
                $data = Account::query()
                    ->leftJoin('pedagang', function ($join) {
                        $join->on('users2.owner_id', '=', 'pedagang.id')
                            ->where('users2.owner_type', '=', 'Pedagang');
                    })
                    ->leftJoin('produsen', function ($join) {
                        $join->on('users2.owner_id', '=', 'produsen.id')
                            ->where('users2.owner_type', '=', 'Produsen');
                    })
                    ->select('users2.*', 'pedagang.nama as pedagang_nama', 'produsen.nama as produsen_nama')
                    ->get();

                $headers = ['#', 'ID', 'Nama', 'Email', 'Tipe Owner', 'Owner Terkait'];
                $map = function ($row) use (&$counter) {
                    $counter++;
                    $ownerName = $row->pedagang_nama ?? $row->produsen_nama;
                    $ownerTerkait = $ownerName ? "{$ownerName} (ID: {$row->owner_id})" : "Admin/Internal (ID: {$row->owner_id})";

                    return [
                        $counter,
                        $row->id,
                        $row->name,
                        $row->email,
                        $row->owner_type,
                        $ownerTerkait,
                    ];
                };
                break;

            case 'detail-kas':
                $data = DetailKas::orderBy('tanggal', 'desc')->get();
                $headers = ['#', 'ID', 'Tanggal', 'Status', 'Keterangan', 'Jumlah'];
                $map = function ($row) use (&$counter) {
                    $counter++;

                    return [
                        $counter,
                        $row->id,
                        $row->tanggal?->format('Y-m-d'),
                        $row->status,
                        $row->keterangan,
                        $row->jumlah,
                    ];
                };
                break;

            case 'pembulatan':
                $data = Pembulatan::with('produsen')->get();
                $headers = ['#', 'ID', 'Produsen', 'Pembulatan Ke', 'Saldo Bulatan', 'Keterangan'];
                $map = function ($row) use (&$counter) {
                    $counter++;

                    return [
                        $counter,
                        $row->id,
                        $row->produsen?->nama,
                        $row->pembulatan_ke,
                        $row->jumlah,
                        $row->keterangan,
                    ];
                };
                break;

            case 'log-produk':
                $data = LogProduk::orderBy('created_at', 'desc')->limit(1000)->get();
                $headers = ['#', 'ID', 'Produk', 'Field', 'Lama', 'Baru', 'Keterangan', 'Waktu'];
                $map = function ($row) use (&$counter) {
                    $counter++;

                    return [
                        $counter,
                        $row->id,
                        $row->nama_produk,
                        $row->field_name,
                        $row->old_value,
                        $row->new_value,
                        $row->keterangan,
                        $row->created_at?->format('Y-m-d H:i:s'),
                    ];
                };
                break;

            case 'draft-penjualan':
                $data = Penjualan::with(['produk', 'pedagang'])->where('status', 'Draft')->get();
                $headers = ['#', 'ID', 'Tanggal', 'Pedagang', 'Produk', 'Titip', 'Laku', 'Sisa', 'HB', 'HJ'];
                $map = function ($row) use (&$counter) {
                    $counter++;

                    return [
                        $counter,
                        $row->id,
                        $row->tanggal,
                        $row->pedagang?->nama,
                        $row->produk?->nama,
                        $row->titip,
                        $row->laku,
                        $row->sisa_jual,
                        $row->harga_beli,
                        $row->harga_jual,
                    ];
                };
                break;

            default:
                abort(404);
        }

        return Excel::download(new ModelExport($data, $headers, $map), $filename);
    }

    private function getRelativeLabel(?string $dateVal): string
    {
        if (! $dateVal || $dateVal === '') {
            return '-';
        }

        $tz = 'Asia/Jakarta';
        $date = Carbon::parse($dateVal, $tz)->startOfDay();
        $today = now($tz)->startOfDay();
        $diff = (int) $date->diffInDays($today);

        return match (true) {
            $diff === 0 => 'Hari Ini',
            $diff === 1 => 'Kemarin',
            default => "{$diff} H",
        };
    }
}
