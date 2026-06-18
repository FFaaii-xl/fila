<?php

namespace App\Filament\Pages;

use App\Models\Pedagang;
use App\Traits\Filament\HasRoleAuthorization;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class MonitorKirimanPage extends Page
{
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-truck';
    protected static string | \UnitEnum | null $navigationGroup = 'High Priority';
    protected static ?int $navigationSort = 32;
    protected static ?string $title = 'Monitor Kiriman';

    public static function getNavigationGroup(): ?string
    {
        return 'Operasional';
    }

    protected string $view = 'filament.pages.monitor-kiriman-page';

    public static function canAccess(): bool
    {
        return (new static)->isAdminOrPengurus();
    }

    protected function getViewData(): array
    {
        $tanggal = request('date', now()->toDateString());
        return $this->getKirimanData($tanggal);
    }

    private function getKirimanData(string $tanggal): array
    {
        $start = $tanggal . ' 00:00:00';
        $end = $tanggal . ' 23:59:59';

        // Get active pedagang (yang jual dalam 14 hari terakhir)
        $activePedagangIds = Pedagang::getActivePedagangIds(14);

        // Get kiriman hari ini
        $kiriman = DB::table('penjualan as p')
            ->leftJoin('pedagang as ped', 'p.pedagang_id', '=', 'ped.id')
            ->whereBetween('p.tanggal', [$start, $end])
            ->whereNull('p.deleted_at')
            ->select([
                'p.pedagang_id',
                'ped.nama as pedagang_nama',
                DB::raw('SUM(p.titip) as total_titip'),
                DB::raw('SUM(p.laku) as total_laku'),
                DB::raw('SUM(p.laku * p.harga_beli) as total_modal'),
                DB::raw('SUM(p.laku * p.harga_jual) as total_omset'),
                DB::raw('COUNT(DISTINCT p.produk_id) as item_count'),
                DB::raw('MAX(p.created_at) as sent_at'),
                DB::raw('MAX(p.status) as status'),
            ])
            ->groupBy('p.pedagang_id', 'ped.nama')
            ->orderBy('ped.nama')
            ->get();

        $pedagangIdsWithData = $kiriman->pluck('pedagang_id')->toArray();

        // Get pedagang yang belum kirim
        $belumKirim = DB::table('pedagang')
            ->whereIn('id', $activePedagangIds)
            ->whereNotIn('id', $pedagangIdsWithData)
            ->whereNull('deleted_at')
            ->select('id', 'nama')
            ->orderBy('nama')
            ->get();

        return [
            'tanggal' => $tanggal,
            'total_pedagang' => count($activePedagangIds),
            'sudah_kirim' => count($pedagangIdsWithData),
            'belum_kirim' => $belumKirim->count(),
            'total_titip' => $kiriman->sum('total_titip'),
            'pedagang' => $kiriman,
            'belum_kirim_list' => $belumKirim,
        ];
    }
}
