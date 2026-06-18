<x-filament-panels::page>
    <div class="kinetic-wrapper">
        @if (!auth()->user() || auth()->user()->owner_type === 'Pedagang')
            <div class='text-center py-10 text-red-500 font-bold'>Akses Ditolak: Pedagang tidak diizinkan mengakses halaman ini.</div>
        @else
            <!-- Filter & Search -->
            @include('admin.nota.filter', [
                'date' => $date,
                'search' => $search,
                'suggestions' => $suggestions,
                'backups' => $backups,
                'roleLabel' => $roleLabel,
            ])

            @if (!$hasData)
                <div class='text-center py-10 opacity-50'>Tidak ada data pada tanggal {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</div>
            @else
                <!-- The core Grid from Moonshine Print Layout -->
                @include('admin.nota.print', [
                    'date' => $date,
                    'search' => $search,
                    'groupedByProdusen' => $groupedByProdusen,
                    'masterPedagangsFormatted' => $masterPedagangsFormatted,
                    'liburPedagangs' => $liburPedagangs,
                ])
            @endif
        @endif
    </div>
</x-filament-panels::page>
