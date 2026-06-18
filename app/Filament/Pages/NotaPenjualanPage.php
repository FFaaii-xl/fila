<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\DetailTabungan;
use App\Models\Pedagang;
use App\Models\Pembulatan;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Traits\Filament\HasRoleAuthorization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NotaPenjualanPage extends Page
{
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static string | \UnitEnum | null $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Nota Penjualan';

    protected string $view = 'filament.pages.nota-penjualan-page';

    public static function canAccess(): bool
    {
        return !(new static)->isPedagang(); // Pedagang cannot access
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        
        $latestDate = Cache::remember('nota_latest_any_date', 3600, function () {
            return DB::table('penjualan')
                ->whereNull('deleted_at')
                ->max('tanggal');
        });
        
        $roleLabel = $this->getRoleLabel();
        $date = request('date', $latestDate ? substr($latestDate, 0, 10) : date('Y-m-d'));
        $start = $date.' 00:00:00';
        $end = $date.' 23:59:59';

        $cacheKey = 'nota_search_suggestions_'.($user->owner_type ?? 'Admin').'_'.($user->owner_id ?? 0);
        $allSuggestions = Cache::remember($cacheKey, 3600, function () use ($user) {
            $produsenQuery = DB::table('produsen')->select('nama')->distinct()->orderBy('nama');
            if ($user && $user->owner_type === 'Produsen') {
                $produsenQuery->where('id', $user->owner_id);
            }
            $prodS_list = $produsenQuery->pluck('nama')->toArray();

            $produkQuery = DB::table('produk')->select('nama')->distinct()->orderBy('nama');
            if ($user && $user->owner_type === 'Produsen') {
                $produkQuery->where('produsen_id', $user->owner_id);
            }
            $pdkS_list = $produkQuery->pluck('nama')->toArray();

            return array_values(array_unique(array_merge($prodS_list, $pdkS_list)));
        });

        // Get Backups
        $backupFolder = 'nota_logs/'.date('Y-m-d', strtotime($date));
        $backups = [];
        if (Storage::disk('local')->exists($backupFolder)) {
            $files = Storage::disk('local')->files($backupFolder);
            rsort($files); // latest first
            foreach ($files as $file) {
                $time = filemtime(storage_path('app/'.$file));
                $size = round(filesize(storage_path('app/'.$file)) / 1024, 2);
                $backups[] = (object) [
                    'name' => basename($file),
                    'time' => date('H:i:s', $time),
                    'path' => encrypt($file),
                    'size' => $size.' KB',
                ];
            }
        }

        $produsenProdukIds = [];
        if ($user && $user->owner_type === 'Produsen') {
            $produsenProdukIds = Produk::where('produsen_id', $user->owner_id)->pluck('id')->toArray();
        }

        $search = request('search');
        $penjualans = DB::table('penjualan as p')
            ->join('produk as pdk', 'p.produk_id', '=', 'pdk.id')
            ->join('produsen as prd', 'pdk.produsen_id', '=', 'prd.id')
            ->join('pedagang as ped', 'p.pedagang_id', '=', 'ped.id')
            ->whereNull('p.deleted_at')
            ->whereIn('p.status', ['Draft', 'Pending', 'Ok'])
            ->whereBetween('p.tanggal', [$start, $end])
            ->select([
                'p.id',
                'p.produk_id',
                'p.pedagang_id',
                'p.titip',
                'p.laku',
                'p.sisa_jual',
                'p.harga_jual',
                'p.harga_beli',
                'p.tanggal',
                'p.status',
                'pdk.nama as produk_nama',
                'pdk.produsen_id',
                'prd.nama as produsen_nama',
                'prd.bundle_ke',
                'prd.gender as produsen_gender',
                'prd.tabungan as produsen_tabungan',
                'prd.tabungan_rate as produsen_tabungan_rate',
                'ped.nama as pedagang_nama',
                'ped.gender as pedagang_gender',
            ])
            ->when($produsenProdukIds, function ($q) use ($produsenProdukIds) {
                $q->whereIn('p.produk_id', $produsenProdukIds);
            })
            ->when($search, function ($query, $search) {
                $query->whereAny(['pdk.nama', 'prd.nama'], 'like', "%{$search}%");
            })
            ->orderBy('prd.bundle_ke', 'asc')
            ->orderBy('prd.nama', 'asc')
            ->get();

        $produsenIds = $penjualans->pluck('produsen_id')->unique()->toArray();

        $allTransaksis = Transaksi::where('owner_type', 'Produsen')
            ->whereIn('owner_id', $produsenIds)
            ->whereBetween('tanggal', [$start, $end])
            ->with(['details'])
            ->get();

        $transaksiIds = $allTransaksis->pluck('id')->toArray();
        $detailTabungans = DetailTabungan::whereIn('transaksi_id', $transaksiIds)
            ->get()
            ->keyBy('transaksi_id');

        $allTransaksis = $allTransaksis->groupBy('owner_id');

        $allPembulatans = Pembulatan::whereIn('produsen_id', $produsenIds)
            ->get()
            ->keyBy('produsen_id');

        $masterPedagangsFormatted = DB::table('penjualan as p')
            ->join('pedagang as ped', 'p.pedagang_id', '=', 'ped.id')
            ->whereNull('p.deleted_at')
            ->whereIn('p.status', ['Draft', 'Pending', 'Ok'])
            ->whereBetween('p.tanggal', [$start, $end])
            ->select('ped.id', 'ped.nama', 'ped.gender')
            ->distinct()
            ->orderBy('ped.nama')
            ->get()
            ->map(function ($p) {
                return (object) [
                    'id' => $p->id,
                    'nama' => $p->nama,
                    'gender' => $p->gender,
                    'display_name' => ($p->gender === 'female' ? 'B. ' : 'P. ').$p->nama,
                ];
            });

        $masterPedagangIds = $masterPedagangsFormatted->pluck('id')->toArray();

        $liburPedagangs = Pedagang::hanyaAktif()
            ->whereNotExists(function ($q) use ($start, $end) {
                $q->select(DB::raw(1))
                  ->from('penjualan')
                  ->whereColumn('penjualan.pedagang_id', 'pedagang.id')
                  ->whereBetween('tanggal', [$start, $end])
                  ->whereNull('deleted_at');
            })
            ->orderBy('nama')
            ->get();

        $groupedByProdusen = collect();
        if ($penjualans->isNotEmpty()) {
            foreach ($penjualans->groupBy('produsen_id') as $produsenId => $items) {
                $first = $items->first();
                $penjualanDict = [];
                $totalTitip = 0;
                $totalLaku = 0;
                $totalRetur = 0;
                $totalSetoran = 0;
                $isPrintedAll = true;

                foreach ($items as $item) {
                    $labaItem = ($item->harga_jual - $item->harga_beli) * $item->laku;
                    $setoranItem = $item->laku * $item->harga_beli;
                    $returItem = max(0, $item->titip - $item->laku - $item->sisa_jual);

                    $key = $item->pedagang_id.'_'.$item->produk_id;
                    $penjualanDict[$key] = [
                        'titip' => $item->titip,
                        'laku' => $item->laku,
                        'sisa_jual' => $item->sisa_jual,
                        'retur' => $returItem,
                        'setoran' => $setoranItem,
                        'laba' => $labaItem,
                    ];

                    $totalTitip += $item->titip;
                    $totalLaku += $item->laku;
                    $totalRetur += $returItem;
                    $totalSetoran += $setoranItem;
                    if ($item->status !== 'Ok') {
                        $isPrintedAll = false;
                    }
                }

                $pembulatan = 0;
                if ($allPembulatans->has($produsenId)) {
                    $pembulatanObj = $allPembulatans->get($produsenId);
                    $pembulatan = $pembulatanObj->nilai_pembulatan;
                } else {
                    $remainder = $totalSetoran % 500;
                    if ($remainder > 0) {
                        $pembulatan = ($remainder >= 250) ? (500 - $remainder) : -$remainder;
                    }
                }

                $transaksis = $allTransaksis->get($produsenId);
                $tabunganAmount = 0;
                if ($transaksis) {
                    foreach ($transaksis as $trx) {
                        if ($detailTabungans->has($trx->id)) {
                            $tabunganAmount += $detailTabungans->get($trx->id)->jumlah;
                        }
                    }
                } else {
                    if ($first->produsen_tabungan && $first->produsen_tabungan_rate > 0) {
                        $tabunganAmount = $totalSetoran * ($first->produsen_tabungan_rate / 100);
                    }
                }

                $totalDibayarkan = $totalSetoran + $pembulatan - $tabunganAmount;

                $groupedByProdusen->push((object) [
                    'id' => $produsenId,
                    'nama' => $first->produsen_nama,
                    'gender' => $first->produsen_gender,
                    'bundle_ke' => $first->bundle_ke,
                    'items' => collect($items)->unique('produk_id')->values(),
                    'dict' => $penjualanDict,
                    'total_titip' => $totalTitip,
                    'total_laku' => $totalLaku,
                    'total_retur' => $totalRetur,
                    'total_setoran' => $totalSetoran,
                    'pembulatan' => $pembulatan,
                    'tabungan' => $tabunganAmount,
                    'total_dibayarkan' => $totalDibayarkan,
                    'is_printed_all' => $isPrintedAll,
                ]);
            }
        }

        return [
            'date' => $date,
            'search' => $search,
            'suggestions' => $allSuggestions,
            'backups' => $backups,
            'roleLabel' => $roleLabel,
            'groupedByProdusen' => $groupedByProdusen,
            'masterPedagangsFormatted' => $masterPedagangsFormatted,
            'liburPedagangs' => $liburPedagangs,
            'hasData' => $penjualans->isNotEmpty()
        ];
    }
}
