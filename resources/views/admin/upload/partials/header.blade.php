{{--
UPLOAD PENJUALAN - HEADER & COUNTDOWN (Enhanced UI)
Native MoonShine v4 architecture with improved styling.
--}}

{{-- COUNTDOWN BAR - Simplified for Pedagang --}}
@php
    $isPedagang = ($roleLabel ?? '') === 'Pedagang';
@endphp

<x-moonshine::card headerBleed classTop="!bg-gradient-to-r !from-slate-700 !to-slate-800" classBody="!p-0" class="!mb-4 !rounded-xl shadow-sm" x-data="timelineIntel({
        deadline: '{{ $deadlineTime }}',
        active: {{ $deadlineActive ? 'true' : 'false' }},
        serverTime: '{{ $serverTime }}',
        isExempt: {{ $isExempt ? 'true' : 'false' }}
    })">

    <div class="flex items-center justify-between px-4 py-3 relative gap-4 w-full">
        {{-- Datepicker --}}
        <form method="GET"
            class="flex items-center gap-2 flex-shrink-0 pr-4 border-r dark:border-white/10 border-slate-200/50">
            <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-white/5 flex items-center justify-center">
                <x-moonshine::icon icon="calendar" size="4" class="text-slate-500" />
            </div>
            <input type="date" name="tanggal" value="{{ $tanggal }}" onchange="this.form.submit()"
                class="bg-transparent border dark:border-white/10 border-slate-200/50 rounded-lg px-3 py-2 text-xs font-mono font-bold focus:ring-2 focus:ring-primary/30 focus:outline-none transition-all text-slate-700 dark:text-white/80"
                style="color-scheme: dark;">
            @if($pedagangId)
                <input type="hidden" name="pedagang_id" value="{{ $pedagangId }}">
            @endif
        </form>

        @if(!$isPedagang)
        {{-- Full Countdown for Admin/Pengurus --}}
        <div class="flex items-center gap-3 flex-grow justify-end">
            <div class="relative flex items-center">
                <div class="w-3 h-3 rounded-full transition-colors duration-500"
                    :class="active ? (urgency === 'high' ? 'bg-rose-500 animate-pulse' : (urgency === 'mid' ? 'bg-amber-500' : 'bg-emerald-500')) : 'bg-slate-400'">
                </div>
                <div x-show="active && urgency === 'high'"
                    class="absolute inset-0 w-full h-full rounded-full bg-rose-500 animate-ping opacity-30"></div>
            </div>
            <div class="flex flex-col justify-center gap-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[10px] font-bold uppercase tracking-wider"
                        :class="active ? 'text-primary' : 'text-slate-400'">Batas Pengiriman</span>
                    <span class="text-xs font-mono font-bold text-slate-600 dark:text-slate-300" x-text="deadline"></span>
                    @isset($roleLabel)
                        <span class="text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full border border-slate-200 dark:border-white/10 bg-slate-50 dark:bg-white/5 text-slate-600 dark:text-white/60">
                            {{ $roleLabel }}
                        </span>
                    @endisset
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Countdown</span>
                    <span class="text-xs font-bold font-mono leading-none px-2.5 py-1.5 rounded-lg border transition-all duration-500"
                        :class="active ? (urgency === 'high' ? 'text-rose-500 bg-rose-500/10 border-rose-500/20' : (urgency === 'mid' ? 'text-amber-500 bg-amber-500/10 border-amber-500/20' : 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20')) : 'text-slate-400 bg-slate-100 dark:bg-white/5 border-slate-200 dark:border-white/10'"
                        x-text="timeLeft"></span>
                    <template x-if="isExempt">
                        <span class="text-[8px] italic text-slate-400 opacity-60 ml-1">(non aktif)</span>
                    </template>
                </div>
            </div>
        </div>
        @else
        {{-- Simplified for Pedagang - Just status indicator --}}
        <div class="flex items-center gap-2 flex-grow justify-end">
            <div class="relative flex items-center">
                <div class="w-3 h-3 rounded-full transition-colors duration-500"
                    :class="active ? (urgency === 'high' ? 'bg-rose-500 animate-pulse' : (urgency === 'mid' ? 'bg-amber-500' : 'bg-emerald-500')) : 'bg-slate-400'">
                </div>
            </div>
            <span class="text-xs font-mono font-bold text-slate-600 dark:text-slate-300" x-text="timeLeft"></span>
        </div>
        @endif
    </div>
</x-moonshine::card>

{{-- PAGE HEADER (contextual — compact & centered) --}}
@if(!$pedagangId)
    {{-- LIBRARY MODE --}}
    <div class="flex items-center justify-center gap-4 mb-4 px-2">
        <div class="flex-1 h-px bg-gradient-to-r from-transparent via-slate-300 dark:via-white/10 to-transparent"></div>
        <div class="text-center">
            <h2 class="text-sm font-bold uppercase tracking-widest text-slate-800 dark:text-white">Daftar Pedagang</h2>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono uppercase tracking-wider mt-1">{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d M Y') }}</p>
        </div>
        <div class="flex-1 h-px bg-gradient-to-r from-transparent via-slate-300 dark:via-white/10 to-transparent"></div>
    </div>
@else
    {{-- EDITOR MODE --}}
    <div class="flex items-center justify-between gap-4 mb-4 px-2">
        <div class="flex items-center gap-2 flex-shrink-0">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $isLocked ? 'bg-rose-100 dark:bg-rose-500/10' : 'bg-emerald-100 dark:bg-emerald-500/10' }}">
                <x-moonshine::icon icon="{{ $isLocked ? 'lock-closed' : 'lock-open' }}" size="4"
                    class="{{ $isLocked ? 'text-rose-500' : 'text-emerald-500' }}" />
            </div>
            <span
                class="px-3 py-1.5 text-[9px] font-bold uppercase tracking-wider rounded-lg {{ $isLocked ? 'bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-500/20' : 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20' }}">
                {{ $isLocked ? 'Terkunci' : 'Aktif' }}
            </span>
        </div>
        <div class="text-center">
            <h2 class="text-sm font-bold uppercase tracking-widest text-slate-800 dark:text-white">Editor Penjualan</h2>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono uppercase tracking-wider mt-1">Input Data • {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d M Y') }}</p>
        </div>
        <div class="w-[100px] flex-shrink-0"></div>
    </div>
@endif

{{-- LOCK ALERT --}}
@if($isLocked && $lockError)
    <x-moonshine::alert type="error" :removable="false" class="mb-4 rounded-xl shadow-sm">
        <div class="flex items-center gap-2">
            <x-moonshine::icon icon="lock-closed" size="4" class="text-rose-500" />
            <span class="font-semibold">{{ $lockError }}</span>
        </div>
    </x-moonshine::alert>
@endif

{{-- COUNTDOWN SCRIPT --}}
<script>
    document.addEventListener("alpine:init", () => {
        Alpine.data("timelineIntel", (config) => ({
            deadline: config.deadline,
            active: config.active,
            isExempt: config.isExempt,
            currentTime: "00:00:00",
            timeLeft: "00:00:00",
            urgency: "low",

            init() {
                this.update();
                setInterval(() => this.update(), 1000);
            },

            update() {
                const now = new Date();
                this.currentTime = now.toTimeString().split(" ")[0];

                const [h, m] = this.deadline.split(":");
                const target = new Date();
                target.setHours(parseInt(h), parseInt(m), 0, 0);

                let diff = target - now;

                if (diff < 0) {
                    this.timeLeft = "EXPIRED";
                    this.urgency = "high";
                    return;
                }

                const hh = Math.floor(diff / 3600000);
                const mm = Math.floor((diff % 3600000) / 60000);
                const ss = Math.floor((diff % 60000) / 1000);

                this.timeLeft = [hh, mm, ss].map(v => v.toString().padStart(2, "0")).join(":");

                if (!this.active) {
                    this.urgency = "low";
                    return;
                }

                if (diff < 600000) this.urgency = "high";
                else if (diff < 1800000) this.urgency = "mid";
                else this.urgency = "low";
            }
        }));
    });
</script>