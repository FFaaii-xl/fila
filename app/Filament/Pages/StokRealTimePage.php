<?php

namespace App\Filament\Pages;

use App\Traits\Filament\HasRoleAuthorization;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class StokRealTimePage extends Page
{
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';
    protected static ?int $navigationSort = 33;
    protected static ?string $title = 'Stok Real-Time';

    public static function getNavigationGroup(): ?string
    {
        return 'Operasional';
    }

    protected string $view = 'filament.pages.stok-realtime-page';

    public static function canAccess(): bool
    {
        // Fitur khusus Produsen
        return (new static)->isProdusen();
    }

    protected function getViewData(): array
    {
        $produsenId = $this->getOwnerId();
        $tanggal = request('date', now()->toDateString());
        
        return $this->getLiveStockData($produsenId, $tanggal);
    }

    private function getLiveStockData(int $produsenId, string $tanggal): array
    {
        $start = $tanggal . ' 00:00:00';
        $end = $tanggal . ' 23:59:59';

        // Get live stock data dari tabel penjualan
        $stoks = DB::table('penjualan as p')
            ->join('produk as pd', 'p.produk_id', '=', 'pd.id')
            ->join('pedagang as ped', 'p.pedagang_id', '=', 'ped.id')
            ->where('pd.produsen_id', $produsenId)
            ->whereBetween('p.tanggal', [$start, $end])
            ->whereNull('p.deleted_at')
            ->select([
                'p.id',
                'p.pedagang_id',
                'ped.nama as pedagang_nama',
                'pd.id as produk_id',
                'pd.nama as produk_nama',
                'p.titip',
                'p.laku',
                'p.sisa_jual',
                'p.updated_at',
            ])
            ->orderBy('ped.nama')
            ->orderBy('pd.nama')
            ->get();

        return [
            'tanggal' => $tanggal,
            'total_pedagang' => $stoks->pluck('pedagang_id')->unique()->count(),
            'total_sisa' => $stoks->sum('sisa_jual'),
            'total_produk' => $stoks->pluck('produk_id')->unique()->count(),
            'total_laku' => $stoks->sum('laku'),
            'stoks' => $stoks,
        ];
    }
}
