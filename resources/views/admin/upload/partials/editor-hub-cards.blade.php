{{-- 
    DRAFT EDITOR - HUB CARDS (MoonShine v4 Card Components)
--}}

@php
    $isPedagang = ($roleLabel ?? '') === 'Pedagang';
@endphp

@if(!$isPedagang)
{{-- FULL HUB FOR ADMIN/PENGURUS - MoonShine Cards Grid --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    {{-- BLOCK 1: TEMPLATE MASTER --}}
    <x-moonshine::card title="Template" headerBleed classTop="!bg-gradient-to-r !from-emerald-600 !to-emerald-700" class="!rounded-xl">
        <div class="flex items-center justify-between mb-2">
            <span class="text-[8px] font-mono text-slate-400">v.{{ $currentVersion ?? 'N/A' }}</span>
        </div>
        <div class="space-y-2">
            {!! str_replace('py-10', 'py-2', $downloadButton) !!}
            {!! str_replace('mt-2', '', $pullButton) !!}
            @if($hasChanges ?? false)
            <div class="flex items-center gap-2 p-2 rounded-lg bg-amber-500/20 border border-amber-500/30">
                <x-moonshine::icon icon="exclamation-triangle" size="3" class="text-amber-400 shrink-0" />
                <span class="text-[8px] text-amber-300">Update terdeteksi</span>
            </div>
            @endif
        </div>
    </x-moonshine::card>

    {{-- BLOCK 2: UPLOAD FILE --}}
    <x-moonshine::card title="Upload" headerBleed classTop="!bg-gradient-to-r !from-blue-600 !to-blue-700" class="!rounded-xl">
        @if($isLocked ?? false)
        <div class="bg-red-600/90 rounded-lg p-3 text-center border border-red-500/50">
            <x-moonshine::icon icon="lock-closed" size="5" class="text-white mx-auto mb-1" />
            <span class="text-[9px] font-bold text-white">TERKUNCI</span>
        </div>
        @else
        {!! $uploadForm !!}
        @endif
    </x-moonshine::card>

    {{-- BLOCK 3: INFO PEDAGANG - Custom Metrics Style --}}
    <x-moonshine::card title="{{ $pedagang->nama ?? 'N/A' }}" headerBleed classTop="!bg-gradient-to-r !from-amber-600 !to-orange-600" class="!rounded-xl">
        <div class="space-y-2">
            {{-- Row 1 --}}
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-cyan-500/20 rounded-lg p-2 text-center border border-cyan-500/30">
                    <span class="text-[6px] text-cyan-300 block uppercase">Titip</span>
                    <span class="text-[10px] font-bold font-mono text-cyan-200" x-text="totalTitip()">0</span>
                </div>
                <div class="bg-emerald-500/20 rounded-lg p-2 text-center border border-emerald-500/30">
                    <span class="text-[6px] text-emerald-300 block uppercase">Laku</span>
                    <span class="text-[10px] font-bold font-mono text-emerald-200" x-text="totalLaku()">0</span>
                </div>
            </div>
            {{-- Row 2 --}}
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-slate-500/20 rounded-lg p-2 text-center border border-slate-500/30">
                    <span class="text-[6px] text-slate-300 block uppercase">Bayar</span>
                    <span class="text-[10px] font-bold font-mono text-slate-200" x-text="'Rp ' + (totalBayar()/1000).toFixed(0)">Rp 0</span>
                </div>
                <div class="bg-amber-500/20 rounded-lg p-2 text-center border border-amber-500/30">
                    <span class="text-[6px] text-amber-300 block uppercase">Omset</span>
                    <span class="text-[10px] font-bold font-mono text-amber-200" x-text="'Rp ' + (totalOmset()/1000).toFixed(0)">Rp 0</span>
                </div>
            </div>
            {{-- Row 3 --}}
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-rose-500/20 rounded-lg p-2 text-center border border-rose-500/30">
                    <span class="text-[6px] text-rose-300 block">Return</span>
                    <span class="text-[9px] font-bold font-mono text-rose-200" x-text="totalSR()">0</span>
                </div>
                <div class="bg-slate-600/30 rounded-lg p-2 text-center border border-slate-500/30">
                    <span class="text-[6px] text-slate-300 block">Sisa</span>
                    <span class="text-[9px] font-bold font-mono text-slate-200" x-text="totalSJ()">0</span>
                </div>
                <div class="bg-emerald-500/20 rounded-lg p-2 text-center border border-emerald-500/30">
                    <span class="text-[6px] text-emerald-300 block">Laba</span>
                    <span class="text-[9px] font-bold font-mono text-emerald-200" x-text="'Rp ' + (calcLaba()/1000).toFixed(0)">Rp 0</span>
                </div>
            </div>
        </div>
    </x-moonshine::card>
</div>
@else
{{-- SIMPLIFIED HUB FOR PEDAGANG --}}
@if($isLocked ?? false)
<div class="mb-4">
    <x-moonshine::alert type="error" :removable="false" class="rounded-xl">
        <div class="flex items-center gap-3">
            <x-moonshine::icon icon="lock-closed" size="5" class="text-rose-500" />
            <div>
                <span class="font-bold">Laporan Terkunci</span>
                <p class="text-xs opacity-80">{{ $lockError ?? 'Nota sudah dicetak' }}</p>
            </div>
        </div>
    </x-moonshine::alert>
</div>
@endif
@endif
