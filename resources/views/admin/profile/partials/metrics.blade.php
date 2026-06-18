<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    @if($profile['type'] === 'pedagang')
        <!-- Pedagang Metrics -->
        <div class="bg-gradient-to-br from-emerald-950/60 to-emerald-900/40 rounded-xl p-4 border border-emerald-500/20 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                    <x-moonshine::icon icon="banknotes" size="6" class="text-emerald-400" />
                </div>
                <div>
                    <p class="text-white/60 text-xs uppercase tracking-wider" style="font-family: 'Outfit', sans-serif;">Saldo</p>
                    <p class="text-xl font-bold text-white" style="font-family: 'Space Mono', monospace;">
                        Rp {{ number_format($profile['saldo']->jumlah ?? 0) }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-amber-950/60 to-amber-900/40 rounded-xl p-4 border border-amber-500/20 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center">
                    <x-moonshine::icon icon="currency-dollar" size="6" class="text-amber-400" />
                </div>
                <div>
                    <p class="text-white/60 text-xs uppercase tracking-wider" style="font-family: 'Outfit', sans-serif;">Tabungan</p>
                    <p class="text-xl font-bold text-white" style="font-family: 'Space Mono', monospace;">
                        Rp {{ number_format($profile['tabungan'] ?? 0) }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-violet-950/60 to-violet-900/40 rounded-xl p-4 border border-violet-500/20 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-violet-500/20 flex items-center justify-center">
                    <x-moonshine::icon icon="shopping-cart" size="6" class="text-violet-400" />
                </div>
                <div>
                    <p class="text-white/60 text-xs uppercase tracking-wider" style="font-family: 'Outfit', sans-serif;">Omset Bulan Ini</p>
                    <p class="text-xl font-bold text-white" style="font-family: 'Space Mono', monospace;">
                        Rp {{ number_format($profile['monthly_sales']->total_omset ?? 0) }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-blue-950/60 to-blue-900/40 rounded-xl p-4 border border-blue-500/20 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center">
                    <x-moonshine::icon icon="check-circle" size="6" class="text-blue-400" />
                </div>
                <div>
                    <p class="text-white/60 text-xs uppercase tracking-wider" style="font-family: 'Outfit', sans-serif;">Total Laku (30d)</p>
                    <p class="text-xl font-bold text-white" style="font-family: 'Space Mono', monospace;">
                        {{ number_format($profile['monthly_sales']->total_laku ?? 0) }}
                    </p>
                </div>
            </div>
        </div>
        
    @elseif($profile['type'] === 'produsen')
        <!-- Produsen Metrics -->
        <div class="bg-gradient-to-br from-blue-950/60 to-blue-900/40 rounded-xl p-4 border border-blue-500/20 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center">
                    <x-moonshine::icon icon="cube" size="6" class="text-blue-400" />
                </div>
                <div>
                    <p class="text-white/60 text-xs uppercase tracking-wider" style="font-family: 'Outfit', sans-serif;">Jumlah Produk</p>
                    <p class="text-xl font-bold text-white" style="font-family: 'Space Mono', monospace;">
                        {{ $profile['produk_count'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-violet-950/60 to-violet-900/40 rounded-xl p-4 border border-violet-500/20 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-violet-500/20 flex items-center justify-center">
                    <x-moonshine::icon icon="shopping-cart" size="6" class="text-violet-400" />
                </div>
                <div>
                    <p class="text-white/60 text-xs uppercase tracking-wider" style="font-family: 'Outfit', sans-serif;">Total Titip</p>
                    <p class="text-xl font-bold text-white" style="font-family: 'Space Mono', monospace;">
                        {{ number_format($profile['monthly_sales']->total_titip ?? 0) }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-emerald-950/60 to-emerald-900/40 rounded-xl p-4 border border-emerald-500/20 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                    <x-moonshine::icon icon="check-circle" size="6" class="text-emerald-400" />
                </div>
                <div>
                    <p class="text-white/60 text-xs uppercase tracking-wider" style="font-family: 'Outfit', sans-serif;">Total Laku</p>
                    <p class="text-xl font-bold text-white" style="font-family: 'Space Mono', monospace;">
                        {{ number_format($profile['monthly_sales']->total_laku ?? 0) }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-amber-950/60 to-amber-900/40 rounded-xl p-4 border border-amber-500/20 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center">
                    <x-moonshine::icon icon="currency-dollar" size="6" class="text-amber-400" />
                </div>
                <div>
                    <p class="text-white/60 text-xs uppercase tracking-wider" style="font-family: 'Outfit', sans-serif;">Total Modal</p>
                    <p class="text-xl font-bold text-white" style="font-family: 'Space Mono', monospace;">
                        Rp {{ number_format($profile['monthly_sales']->total_modal ?? 0) }}
                    </p>
                </div>
            </div>
        </div>
        
    @elseif($profile['type'] === 'admin' || $profile['type'] === 'pengurus')
        <!-- Admin/Pengurus Metrics -->
        <div class="bg-gradient-to-br from-amber-950/60 to-amber-900/40 rounded-xl p-4 border border-amber-500/20 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-amber-500/20 flex items-center justify-center">
                    <x-moonshine::icon icon="users" size="6" class="text-amber-400" />
                </div>
                <div>
                    <p class="text-white/60 text-xs uppercase tracking-wider" style="font-family: 'Outfit', sans-serif;">Pedagang</p>
                    <p class="text-xl font-bold text-white" style="font-family: 'Space Mono', monospace;">
                        {{ $profile['stats']['total_pedagang'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-violet-950/60 to-violet-900/40 rounded-xl p-4 border border-violet-500/20 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-violet-500/20 flex items-center justify-center">
                    <x-moonshine::icon icon="building-office-2" size="6" class="text-violet-400" />
                </div>
                <div>
                    <p class="text-white/60 text-xs uppercase tracking-wider" style="font-family: 'Outfit', sans-serif;">Produsen</p>
                    <p class="text-xl font-bold text-white" style="font-family: 'Space Mono', monospace;">
                        {{ $profile['stats']['total_produsen'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-emerald-950/60 to-emerald-900/40 rounded-xl p-4 border border-emerald-500/20 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                    <x-moonshine::icon icon="currency-dollar" size="6" class="text-emerald-400" />
                </div>
                <div>
                    <p class="text-white/60 text-xs uppercase tracking-wider" style="font-family: 'Outfit', sans-serif;">Omset Hari Ini</p>
                    <p class="text-xl font-bold text-white" style="font-family: 'Space Mono', monospace;">
                        Rp {{ number_format($profile['stats']['today_sales']->total_omset ?? 0) }}
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-rose-950/60 to-rose-900/40 rounded-xl p-4 border border-rose-500/20 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-rose-500/20 flex items-center justify-center">
                    <x-moonshine::icon icon="clock" size="6" class="text-rose-400" />
                </div>
                <div>
                    <p class="text-white/60 text-xs uppercase tracking-wider" style="font-family: 'Outfit', sans-serif;">Pending</p>
                    <p class="text-xl font-bold text-white" style="font-family: 'Space Mono', monospace;">
                        {{ $profile['stats']['pending_reports'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>