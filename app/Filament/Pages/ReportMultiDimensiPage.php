<?php

namespace App\Filament\Pages;

use App\Traits\Filament\HasRoleAuthorization;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportMultiDimensiPage extends Page
{
    use HasRoleAuthorization;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';
    protected static string | \UnitEnum | null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 15;
    protected static ?string $title = 'Multi Dimensi';

    protected string $view = 'filament.pages.report-multi-dimensi-page';

    public static function canAccess(): bool
    {
        return (new static)->isAdminOrPengurus();
    }

    protected function getViewData(): array
    {
        $bulan = (int) request('bulan', now()->month);
        $tahun = (int) request('tahun', now()->year);
        $waktu = request('waktu', 'Per Hari');
        $isi = request('isi', 'Keseluruhan');
        $filterNama = request('filter_nama', '');

        // Build date boundaries
        $startOfMonth = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $endOfMonth = Carbon::create($tahun, $bulan, 1)->endOfMonth()->endOfDay();

        // === MAIN REPORT: Transaksi ===
        $transaksiReport = $this->buildTransaksiReport($waktu, $isi, $bulan, $tahun, $filterNama);

        // === SUB-REPORT: Rangkuman Produk ===
        $produkReport = [];
        if (in_array($isi, ['Keseluruhan', 'Produk'], true)) {
            $produkReport = $this->buildProdukReport($bulan, $tahun, $filterNama);
        }

        // === SUB-REPORT: Rangkuman Pedagang ===
        $pedagangReport = [];
        if (in_array($isi, ['Keseluruhan', 'Pedagang'], true)) {
            $pedagangReport = $this->buildPedagangReport($bulan, $tahun, $filterNama);
        }

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'waktu' => $waktu,
            'isi' => $isi,
            'filterNama' => $filterNama,
            'transaksiReport' => $transaksiReport,
            'produkReport' => $produkReport,
            'pedagangReport' => $pedagangReport,
        ];
    }

    private function buildTransaksiReport(string $waktu, string $isi, int $bulan, int $tahun, string $filterNama): array
    {
        $query = DB::table('transaksi')
            ->select([
                DB::raw('YEAR(tanggal) as year'),
                DB::raw('MONTH(tanggal) as month'),
                DB::raw('DAY(tanggal) as day'),
                DB::raw('SUM(pembulatan) as total_pembulatan'),
                DB::raw('SUM(kas) as total_kas'),
                DB::raw('SUM(kemarin) as total_kemarin'),
                DB::raw('SUM(jumlah) as total_jumlah'),
                'owner_type',
            ])
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('status', 'Ok')->orWhere('status', 'Paid Out');
            });

        // Filter by owner type
        if (in_array($isi, ['Produsen', 'Produk'], true)) {
            $query->where('owner_type', 'Produsen');
            if ($filterNama && $isi === 'Produsen') {
                $id = DB::table('produsen')->where('nama', 'like', "%{$filterNama}%")->value('id');
                if ($id) {
                    $query->where('owner_id', $id);
                }
            }
        } elseif ($isi === 'Pedagang') {
            $query->where('owner_type', 'Pedagang');
            if ($filterNama) {
                $id = DB::table('pedagang')->where('nama', 'like', "%{$filterNama}%")->value('id');
                if ($id) {
                    $query->where('owner_id', $id);
                }
            }
        }

        // Group by time
        if ($waktu === 'Per Hari') {
            $query->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
                ->groupBy('year', 'month', 'day', 'owner_type');
        } else {
            $query->whereYear('tanggal', $tahun)
                ->groupBy('year', 'month', 'owner_type');
        }

        $query->orderBy('year')->orderBy('month')->orderBy('day');

        $rows = $query->get();

        // Enrich with tabungan data
        $tabunganData = $this->getTabunganSums($waktu, $bulan, $tahun, $filterNama, $isi);

        $result = [];
        foreach ($rows as $row) {
            $key = $waktu === 'Per Hari'
                ? "{$row->day}-{$row->owner_type}"
                : "{$row->month}-{$row->owner_type}";

            $tabKey = $waktu === 'Per Hari'
                ? "{$row->day}-{$row->owner_type}"
                : "{$row->month}-{$row->owner_type}";

            $tabungan = $tabunganData[$tabKey] ?? 0;

            // Calculate penjualan (bayar) based on owner type
            if ($row->owner_type === 'Produsen') {
                $penjualan = $row->total_jumlah + $row->total_kas + $tabungan
                    + $row->total_kemarin - $row->total_pembulatan;
            } else {
                $penjualan = $row->total_jumlah - $row->total_kas - $tabungan;
            }

            $result[] = (object) [
                'year' => $row->year,
                'month' => $row->month,
                'day' => $row->day ?? null,
                'owner_type' => $row->owner_type,
                'total_jumlah' => $row->total_jumlah,
                'total_kas' => $row->total_kas,
                'total_kemarin' => $row->total_kemarin,
                'total_pembulatan' => $row->total_pembulatan,
                'tabungan' => $tabungan,
                'penjualan' => $penjualan,
                'label' => $waktu === 'Per Hari'
                    ? sprintf('%02d/%02d', $row->day, $row->month)
                    : Carbon::create($row->year, $row->month, 1)->format('M Y'),
            ];
        }

        return $result;
    }

    private function getTabunganSums(string $waktu, int $bulan, int $tahun, string $filterNama, string $isi): array
    {
        $dateFormat = $waktu === 'Per Hari'
            ? 'DAY(transaksi.tanggal)'
            : 'MONTH(transaksi.tanggal)';

        $query = DB::table('detail_tabungan')
            ->join('transaksi', 'transaksi.id', '=', 'detail_tabungan.transaksi_id')
            ->select([
                'detail_tabungan.owner_type',
                DB::raw('SUM(detail_tabungan.jumlah) as total_jumlah'),
            ])
            ->selectRaw("{$dateFormat} as period_key")
            ->whereNull('detail_tabungan.deleted_at')
            ->where('detail_tabungan.keterangan', 'not like', '%finalize%');

        if ($waktu === 'Per Hari') {
            $query->whereYear('transaksi.tanggal', $tahun)
                ->whereMonth('transaksi.tanggal', $bulan);
        } else {
            $query->whereYear('transaksi.tanggal', $tahun);
        }

        if (in_array($isi, ['Produsen', 'Produk'], true) && $filterNama) {
            $id = DB::table('produsen')->where('nama', 'like', "%{$filterNama}%")->value('id');
            if ($id) {
                $query->where('detail_tabungan.owner_id', $id);
            }
        } elseif ($isi === 'Pedagang' && $filterNama) {
            $id = DB::table('pedagang')->where('nama', 'like', "%{$filterNama}%")->value('id');
            if ($id) {
                $query->where('detail_tabungan.owner_id', $id);
            }
        }

        $query->groupBy('period_key', 'detail_tabungan.owner_type');

        $result = [];
        foreach ($query->get() as $row) {
            $result["{$row->period_key}-{$row->owner_type}"] = (float) $row->total_jumlah;
        }

        return $result;
    }

    private function buildProdukReport(int $bulan, int $tahun, string $filterNama): array
    {
        $query = DB::table('transaksi')
            ->join('penjualan_transaksi', 'transaksi.id', '=', 'penjualan_transaksi.transaksi_id')
            ->join('penjualan', 'penjualan_transaksi.penjualan_id', '=', 'penjualan.id')
            ->join('produk', 'penjualan.produk_id', '=', 'produk.id')
            ->join('produsen', 'produk.produsen_id', '=', 'produsen.id')
            ->select([
                'produk.nama AS nama_produk',
                'produsen.nama AS nama_produsen',
                DB::raw('SUM(penjualan.laku * penjualan.harga_beli) AS omset'),
                DB::raw('SUM(penjualan.laku) AS total_laku'),
                DB::raw('COUNT(DISTINCT DATE(penjualan.tanggal)) AS hari'),
            ])
            ->whereNull('transaksi.deleted_at')
            ->whereNull('penjualan.deleted_at')
            ->whereMonth('transaksi.tanggal', $bulan)
            ->whereYear('transaksi.tanggal', $tahun)
            ->groupBy('produk.nama', 'produsen.nama');

        if ($filterNama) {
            $query->whereAny(['produk.nama', 'produsen.nama'], 'like', "%{$filterNama}%");
        }

        $query->orderByDesc('omset');

        return $query->get()->toArray();
    }

    private function buildPedagangReport(int $bulan, int $tahun, string $filterNama): array
    {
        $query = DB::table('transaksi')
            ->join('penjualan_transaksi', 'transaksi.id', '=', 'penjualan_transaksi.transaksi_id')
            ->join('penjualan', 'penjualan_transaksi.penjualan_id', '=', 'penjualan.id')
            ->join('pedagang', 'transaksi.owner_id', '=', 'pedagang.id')
            ->select([
                'pedagang.nama as nama_pedagang',
                DB::raw('SUM(penjualan.laku * penjualan.harga_jual) as omset'),
                DB::raw('SUM(penjualan.laku * penjualan.harga_beli) as modal'),
                DB::raw('SUM(penjualan.laku * (penjualan.harga_jual - penjualan.harga_beli)) as laba'),
                DB::raw('COUNT(DISTINCT transaksi.id) as jumlah_transaksi'),
            ])
            ->where('transaksi.owner_type', 'Pedagang')
            ->whereNull('transaksi.deleted_at')
            ->whereNull('penjualan.deleted_at')
            ->whereMonth('transaksi.tanggal', $bulan)
            ->whereYear('transaksi.tanggal', $tahun)
            ->groupBy('transaksi.owner_id', 'pedagang.nama');

        if ($filterNama) {
            $query->where('pedagang.nama', 'like', "%{$filterNama}%");
        }

        $query->orderByDesc('omset');

        return $query->get()->toArray();
    }
}
