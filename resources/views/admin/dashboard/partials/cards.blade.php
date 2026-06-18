<div class="triple-threat-hub">
    <!-- BLOCK 0: WELCOME & PERFORMANCE -->
    <x-moonshine::layout.box class="hub-block" style="border-left: 3px solid rgba(139, 92, 246, 0.4) !important;">
        <div class="hub-header">
            <div class="hub-icon-box pr-icon">
                <x-moonshine::icon icon="identification" size="5" />
            </div>
            <div class="hub-title-group">
                <h4 class="hub-label">Selamat Datang</h4>
                <span class="hub-value text-primary">{{ ($user->gender ?? 'male') === 'female' ? 'Bu' : 'Pak' }} {{ explode(' ', $user->name ?? 'User')[0] }}!</span>
            </div>
        </div>
        
        <div class="hub-content mt-2">
            <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 mb-2">
                <div class="flex flex-col">
                    <span class="text-[7px] font-black uppercase tracking-widest opacity-40">Omset</span>
                    <span class="text-[10px] font-mono font-black">Rp {{ number_format($avg['avg_omset'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-[7px] font-black uppercase tracking-widest opacity-40">Laku %</span>
                    <span class="text-[10px] font-mono font-black text-emerald-500">{{ $avg['health_percent'] ?? 0 }}%</span>
                </div>
            </div>

            <div class="pt-1.5 border-t dark:border-white/5 border-slate-100">
                <div class="flex items-center justify-between text-[9px] mb-1">
                    <span class="font-black uppercase tracking-widest opacity-30">Performa Toko</span>
                    <input type="date" value="{{ request('d', now()->toDateString()) }}" 
                        onchange="window.location.href='?d=' + this.value"
                        class="bg-transparent border border-white/10 rounded px-1.5 py-0.5 text-[9px] font-mono text-emerald-400 focus:ring-0 cursor-pointer text-left"
                        style="color-scheme: dark;">
                </div>
                <div class="flex items-center justify-between text-[9px] mb-1.5">
                    <span class="font-mono font-black text-emerald-400">{{ number_format($avg['avg_laku'] ?? 0, 1, ',', '.') }}</span>
                    <span class="text-white/30">/</span>
                    <span class="font-mono text-white/50">{{ number_format($avg['avg_titip'] ?? 0, 1, ',', '.') }}</span>
                </div>
                <div class="h-1 dark:bg-white/5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full transition-all duration-1000" style="width: {{ $avg['health_percent'] ?? 0 }}%"></div>
                </div>
            </div>
        </div>
    </x-moonshine::layout.box>

    <!-- BLOCK 1: PUSAT SETORAN (Control) -->
    <x-moonshine::layout.box class="hub-block" style="border-left: 3px solid rgba(59, 130, 246, 0.4) !important;">
        <div class="hub-header">
            <div class="hub-icon-box" :class="state === 'draft' ? 'bl-icon' : 'em-icon'">
                <template x-if="state === 'draft'">
                    <x-moonshine::icon icon="bolt" size="5" />
                </template>
                <template x-if="state === 'pending'">
                    <x-moonshine::icon icon="banknotes" size="5" />
                </template>
                <template x-if="state === 'ok'">
                    <x-moonshine::icon icon="shield-check" size="5" />
                </template>
            </div>
            <div class="hub-title-group">
                <h4 class="hub-label">Pusat Setoran</h4>
                <span class="hub-value"
                    x-text="state === 'draft' ? 'Menunggu Data' : (state === 'pending' ? 'Siap Bayar' : 'Sudah Dibayar')"></span>
            </div>
        </div>
        <div class="hub-content">
            <div class="hub-action flex flex-col gap-2">
                @if(auth()->user() && auth()->user()->owner_type !== 'Pengurus')
                <!-- PRIMARY ACTIONS (Full Width) -->
                <div class="flex flex-col gap-2">
                    <div x-show="state === 'draft'" class="flex items-center gap-2 px-3 py-2 bg-white/5 rounded-xl border border-white/10 mb-1">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" x-model="requireLock" class="w-4 h-4 rounded border-white/20 bg-black/40 text-blue-500 focus:ring-offset-0 focus:ring-blue-500 transition-all">
                            <span class="text-[10px] font-bold uppercase tracking-widest text-white/40 group-hover:text-white/80 transition-colors">Hanya Laporan Terkunci</span>
                        </label>
                    </div>
                    <button x-show="state === 'draft'" @click="handleAction('transact')" :disabled="loading"
                        class="btn-hub btn-blue w-full flex items-center justify-center gap-2">
                        <svg x-show="actionLoading === 'transact'" width="14" height="14" class="animate-spin h-3.5 w-3.5 text-white"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="actionLoading === 'transact' ? 'MEMPROSES...' : 'PROSES TRANSAKSI'"></span>
                    </button>
                    <button x-show="state === 'pending'" @click="handleAction('pay')" :disabled="loading"
                        class="btn-hub btn-emerald w-full flex items-center justify-center gap-2">
                        <svg x-show="actionLoading === 'pay'" width="14" height="14" class="animate-spin h-3.5 w-3.5 text-white"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="actionLoading === 'pay' ? 'MEMBAYAR...' : 'BAYAR PRODUSEN'"></span>
                    </button>
                    <button x-show="state === 'ok'" @click="handleAction('rollback')" :disabled="loading"
                        class="btn-hub btn-rose w-full flex items-center justify-center gap-2">
                        <svg x-show="actionLoading === 'rollback'" width="14" height="14" class="animate-spin h-3.5 w-3.5 text-white"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="actionLoading === 'rollback' ? 'MEMBATALKAN...' : 'BATALKAN BAYAR'"></span>
                    </button>
                </div>

                <!-- SECONDARY TOOLS (Horizontal Row) -->
                <div class="flex flex-row flex-wrap items-center justify-center gap-1 mt-0.5">
                    <button x-show="state === 'pending'" @click="handleAction('reset')" :disabled="loading"
                        class="btn-hub btn-rose flex items-center justify-center gap-1.5 px-3 py-[5px] min-w-[68px]">
                        <x-moonshine::icon icon="arrow-path" size="4" />
                        <span class="text-[9px]">RESET</span>
                    </button>

                    <button @click="handleAction('toggle_public_access')" 
                        :class="tables.is_public ? 'bg-rose-500/20 border-rose-500/30 text-rose-500' : 'bg-white/5 border-white/10 text-white/40'"
                        class="btn-hub flex items-center justify-center gap-1.5 border transition-all px-3 py-1.5 min-w-[70px]">
                        <x-moonshine::icon icon="globe-alt" size="4" />
                        <span class="text-[9px]" x-text="tables.is_public ? 'OFF' : 'ON PUBLIK'"></span>
                    </button>

                    <a x-show="tables.is_public" :href="'/nota/public/' + currentDate" target="_blank"
                        class="btn-hub flex items-center justify-center gap-1.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 hover:scale-105 transition-all px-3 py-1.5">
                        <x-moonshine::icon icon="eye" size="4" />
                        <span class="text-[9px]">WEB</span>
                    </a>

                    <button x-show="state === 'pending' || state === 'ok'" @click="toggleLainLainHub()" :disabled="loading"
                        class="btn-hub flex items-center justify-center gap-1.5 px-3 py-1.5 min-w-[70px]"
                        style="background: #1e293b !important; border: 1px solid rgba(255,255,255,0.1) !important;">
                        <x-moonshine::icon icon="chevron-down" size="4" class="text-indigo-400 transition-transform" ::class="showLainHub ? 'rotate-180' : ''" />
                        <span class="text-[9px] text-indigo-100" x-text="state === 'ok' ? 'LAIN' : 'ADJ'"></span  >
                    </button>

                    <a x-show="state === 'ok'" :href="'/admin/page/nota-penjualan?date=' + currentDate" 
                        class="btn-hub flex items-center justify-center gap-1.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-500/70 hover:text-emerald-500 transition-all px-3 py-1.5 min-w-[70px]">
                        <x-moonshine::icon icon="document-text" size="4" />
                        <span class="text-[9px]">NOTA</span>
                    </a>
                </div>
                @else
                <div class="text-center py-4 px-2 bg-white/5 rounded-xl border border-white/10">
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest flex items-center justify-center gap-2">
                        <x-moonshine::icon icon="eye" size="4" /> Mode Lihat Saja (Read-Only)
                    </span>
                </div>
                @endif
            </div>
        </div>
    </x-moonshine::layout.box>

    <!-- BLOCK 3: FINANCIAL HUB (Metrics) -->
    <x-moonshine::layout.box class="hub-block" style="border-left: 3px solid rgba(16, 185, 129, 0.4) !important;">
        <div class="space-y-2.5 flex flex-col justify-center h-full">
            <!-- MARKET DATE row -->
            <div class="flex items-center justify-between border-b dark:border-white/5 border-slate-100 pb-2">
                <div class="flex items-center gap-2">
                    <x-moonshine::icon icon="calendar" class="text-amber-500" size="4" />
                    <span
                        class="text-[9px] font-black uppercase tracking-widest opacity-40 dark:text-white text-slate-900">Tanggal
                        Pasar</span>
                </div>
                <div class="relative">
                    <input type="date" x-model="currentDate" 
                        @change="window.location.href='?d=' + $event.target.value"
                        class="bg-transparent border-none p-0 text-[11px] font-mono font-black text-amber-500 focus:ring-0 cursor-pointer uppercase text-right"
                        style="color-scheme: dark; -webkit-appearance: none;">
                </div>
            </div>

            <!-- MAIN METRICS grid -->
            <div class="grid grid-cols-2 gap-x-6 gap-y-3">
                <div class="flex flex-col">
                    <div class="flex items-center gap-1.5 mb-1">
                        <div class="w-1 h-1 rounded-full bg-emerald-500"></div>
                        <span
                            class="text-[8px] font-black uppercase tracking-widest opacity-40 dark:text-white text-slate-900">Saldo
                            Tersedia</span>
                    </div>
                    <span class="text-[12px] font-mono font-black dark:text-white text-slate-900"
                        x-text="formatCurrency(metrics.saldo)"></span>
                </div>
                <div class="flex flex-col items-end">
                    <div class="flex items-center gap-1.5 mb-1">
                        <span
                            class="text-[8px] font-black uppercase tracking-widest opacity-40 dark:text-white text-slate-900">Kebutuhan</span>
                        <div class="w-1 h-1 rounded-full bg-rose-500"></div>
                    </div>
                    <span class="text-[12px] font-mono font-black dark:text-rose-400 text-rose-600"
                        x-text="formatCurrency(metrics.required)"></span>
                </div>
            </div>

            <!-- STATUS row -->
            <div class="pt-2 border-t dark:border-white/5 border-slate-100 flex flex-col gap-2">
                <div class="flex items-center justify-between p-2 rounded-xl transition-all"
                    :class="metrics.diff < 0 ? 'dark:bg-rose-500/10 bg-rose-50 ring-1 ring-rose-500/30' : 'dark:bg-emerald-500/5 bg-emerald-50'">
                    <div class="flex items-center gap-2">
                        <x-moonshine::icon icon="scale" size="4" 
                            ::class="metrics.diff < 0 ? 'text-rose-500 animate-pulse' : 'text-emerald-500'" />
                        <span class="text-[8px] font-black uppercase tracking-widest leading-none"
                            :class="metrics.diff < 0 ? 'text-rose-500' : 'text-emerald-600'">Selisih Saldo</span>
                    </div>
                    <span class="text-[12px] font-mono font-black"
                        :class="metrics.diff < 0 ? 'text-rose-500 animate-pulse-critical' : 'text-emerald-600'"
                        x-text="formatCurrency(metrics.diff)"></span>
                </div>

                <!-- [NUCLEAR_WATCHDOG] Reconciliation Audit -->
                <div x-show="state === 'ok'" class="flex items-center justify-between p-2 rounded-xl border transition-all"
                    :class="metrics.reconciliation && metrics.reconciliation.status === 'Mismatch' ? 'bg-amber-500/10 border-amber-500/30' : 'bg-emerald-500/5 border-emerald-500/20'">
                    <div class="flex items-center gap-2">
                        <x-moonshine::icon icon="shield-check" size="4"
                            ::class="metrics.reconciliation && metrics.reconciliation.status === 'Mismatch' ? 'text-amber-500 animate-pulse' : 'text-emerald-500'" />
                        <span class="text-[8px] font-black uppercase tracking-widest leading-none"
                            :class="metrics.reconciliation && metrics.reconciliation.status === 'Mismatch' ? 'text-amber-500' : 'text-emerald-600'">Audit Sistem</span>
                    </div>
                    <span class="text-[10px] font-black tracking-tight"
                        :class="metrics.reconciliation && metrics.reconciliation.status === 'Mismatch' ? 'text-amber-500' : 'text-emerald-600'"
                        x-text="metrics.reconciliation && metrics.reconciliation.status === 'Mismatch' ? 'SELISIH ' + formatCurrency(metrics.reconciliation.discrepancy) : 'SINKRON (OK)'"></span>
                </div>
            </div>
        </div>
    </x-moonshine::layout.box>
</div>
