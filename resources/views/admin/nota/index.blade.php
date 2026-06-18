@include('admin.nota.partials.styles')

<style>
    /* Override MoonShine layout constraints for wide view */
    .moonshine-layout-content { 
        padding-left: 10px !important; 
        padding-right: 10px !important; 
        padding-top: 0 !important; 
        margin-top: 0 !important; 
    }
    @media print {
        /* NUCLEAR RESET: Sembunyikan semua kecuali nota-print-wrapper */
        body > *:not(#nota-print-wrapper) {
            display: none !important;
        }

        /* Bypass seluruh kontainer MoonShine agar tidak mempengaruhi posisi */
        html, body, #moonshine-layout, 
        .moonshine-layout-container, 
        .moonshine-layout-main, 
        .moonshine-layout-content {
            margin: 0 !important;
            padding: 0 !important;
            display: contents !important; /* Membuat kontainer ini seolah tidak ada */
        }
        
        #nota-print-wrapper {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
        }
    }
</style>

<div id="nota-print-wrapper" class="nota-print-wrapper">
    <div id="nota-ajax-container">
        @include('admin.nota.partials.content')
    </div>
</div>

{{-- Bagian Khusus Admin - Tidak Tercetak --}}
@if(isset($liburPedagangs) && $liburPedagangs->isNotEmpty())
<div class="no-print mt-10 p-4 border-t-2 border-dashed border-gray-300">
    <h3 class="text-lg font-bold mb-2 text-gray-700">Pedagang Libur / Tidak Lap Hari Ini:</h3>
    <div class="flex flex-wrap gap-2">
        @foreach($liburPedagangs as $p)
            <span class="bg-gray-200 px-3 py-1 rounded-full text-sm text-gray-600">
                P. {{ $p->nama }}
            </span>
        @endforeach
    </div>
    <p class="mt-2 text-xs text-gray-500 italic">*Daftar ini berisi pedagang yang aktif dalam 2 bulan terakhir namun tidak ada transaksi hari ini.</p>
</div>
@endif
