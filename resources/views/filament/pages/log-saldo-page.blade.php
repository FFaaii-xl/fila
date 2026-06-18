<x-filament-panels::page>
    {{-- Include the existing custom Blade view from Moonshine --}}
    @include('admin.saldo.log-saldo', [
        'logs' => $logs,
        'tanggal' => $tanggal,
    ])
</x-filament-panels::page>
