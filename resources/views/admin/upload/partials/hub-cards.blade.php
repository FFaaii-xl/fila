{{-- 
    UPLOAD PENJUALAN - HUB CARDS (Enhanced UI with MoonShine Card)
    Native MoonShine v4 Card component
--}}

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5 px-1 md:px-0">
    {{-- BLOCK 1: UPLOAD FILE (Blue Accent) --}}
    @if(!($isPengurus ?? false))
    <x-moonshine::card title="Upload File" headerBleed classTop="!bg-gradient-to-r !from-blue-500 !to-blue-600">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center shadow-sm">
                <x-moonshine::icon icon="cloud-arrow-up" size="5" class="text-blue-500" />
            </div>
            <div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-white">Upload File</h4>
                <span class="text-[10px] text-slate-500 dark:text-slate-400">Kirim Laporan</span>
            </div>
        </div>
        <div class="hub-content">
            {!! $uploadForm !!}
        </div>
    </x-moonshine::card>
    @endif

    {{-- BLOCK 2: TEMPLATE MASTER (Emerald Accent) --}}
    @if(!($isPengurus ?? false))
    <x-moonshine::card title="Template Produk" headerBleed classTop="!bg-gradient-to-r !from-emerald-500 !to-emerald-600">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center shadow-sm">
                <x-moonshine::icon icon="document-duplicate" size="5" class="text-emerald-500" />
            </div>
            <div>
                <h4 class="text-sm font-bold text-slate-800 dark:text-white">Template Produk</h4>
                <span class="text-[10px] text-slate-500 dark:text-slate-400">Daftar Master</span>
            </div>
        </div>
        <div class="flex flex-col gap-2">
            {!! $downloadButton !!}
            @if(!empty($pullButton))
                {!! $pullButton !!}
            @endif
            @if($hasChanges ?? false)
            <div class="flex items-center gap-2 p-2.5 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200/50 dark:border-amber-500/20 mt-2">
                <x-moonshine::icon icon="exclamation-triangle" size="4" class="text-amber-500 flex-shrink-0" />
                <span class="text-[9px] font-semibold text-amber-600 dark:text-amber-400 leading-tight">Perubahan terdeteksi — gunakan template terbaru</span>
            </div>
            @else
            <p class="text-[9px] text-slate-500 dark:text-slate-400 text-center mt-2 px-2">
                Jika ada perubahan (Produk/Harga),<br>pastikan gunakan template terbaru
            </p>
            @endif
        </div>
    </x-moonshine::card>
    @endif

    {{-- BLOCK 3: RINGKASAN HARI INI (Metrics) --}}
    @if(isset($totalLaku))
    <x-moonshine::card title="Ringkasan Hari Ini" headerBleed classTop="!bg-gradient-to-r !from-amber-500 !to-orange-500">
        <div class="mb-4 pb-3 border-b border-slate-100 dark:border-white/5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center">
                        <x-moonshine::icon icon="fire" class="text-amber-500" size="5" />
                    </div>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        {{-- METRICS GRID --}}
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="bg-emerald-50 dark:bg-emerald-500/5 rounded-xl p-3 border border-emerald-100 dark:border-emerald-500/10">
                <div class="flex items-center gap-1.5 mb-1">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                    <span class="text-[8px] font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Laku</span>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-lg font-bold font-mono text-emerald-700 dark:text-emerald-400">{{ number_format($totalLaku) }}</span>
                    <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/20">{{ $globalEfficiency ?? 0 }}%</span>
                </div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-500/5 rounded-xl p-3 border border-blue-100 dark:border-blue-500/10">
                <div class="flex items-center gap-1.5 mb-1 justify-end">
                    <span class="text-[8px] font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Omset</span>
                    <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                </div>
                <div class="text-right">
                    <span class="text-lg font-bold font-mono text-blue-700 dark:text-blue-400">Rp</span>
                    <span class="text-lg font-bold font-mono text-blue-700 dark:text-blue-400 ml-1">{{ number_format($totalOmset ?? 0) }}</span>
                </div>
            </div>
        </div>

        {{-- LABA ROW --}}
        <div class="bg-gradient-to-r from-emerald-50 to-transparent dark:from-emerald-500/10 p-3 rounded-xl border border-emerald-100 dark:border-emerald-500/10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-moonshine::icon icon="arrow-trending-up" size="4" class="text-emerald-500" />
                    <span class="text-[9px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Laba Bersih</span>
                </div>
                <span class="text-sm font-bold font-mono text-emerald-700 dark:text-emerald-400">Rp {{ number_format($totalLaba ?? 0) }}</span>
            </div>
        </div>
    </x-moonshine::card>
    @endif
</div>