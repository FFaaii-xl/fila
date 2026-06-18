<x-filament-panels::page>
    @include('admin.reports.multi-dimensi-report', [
        'bulan' => $bulan,
        'tahun' => $tahun,
        'waktu' => $waktu,
        'isi' => $isi,
        'filterNama' => $filterNama,
        'transaksiReport' => $transaksiReport,
        'produkReport' => $produkReport,
        'pedagangReport' => $pedagangReport,
    ])
</x-filament-panels::page>
