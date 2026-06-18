<div class="bg-gradient-to-br from-slate-900/60 to-slate-800/40 rounded-xl p-5 border border-white/10 shadow-lg">
    <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4 flex items-center gap-2">
        <x-moonshine::icon icon="clock" size="5" class="text-cyan-400" />
        Aktivitas Terbaru
    </h3>
    
    <div class="space-y-3">
        @if($profile['type'] === 'pedagang')
            @forelse($profile['recent_transactions'] as $transaction)
                <div class="flex items-center gap-4 p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-colors">
                    <div class="w-10 h-10 rounded-lg bg-violet-500/20 flex items-center justify-center flex-shrink-0">
                        <x-moonshine::icon icon="shopping-bag" size="5" class="text-violet-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-medium text-sm truncate">{{ $transaction->produk_nama ?? 'Produk' }}</p>
                        <p class="text-white/50 text-xs">{{ \Carbon\Carbon::parse($transaction->tanggal)->format('d M Y, H:i') }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-emerald-400 font-mono text-sm font-bold">+{{ number_format($transaction->laku) }}</p>
                        <p class="text-white/40 text-xs">{{ $transaction->status ?? 'Ok' }}</p>
                    </div>
                </div>
            @empty
                <div class="text-center text-white/40 py-8">
                    <x-moonshine::icon icon="inbox" size="8" class="mx-auto mb-2 opacity-50" />
                    <p>Belum ada aktivitas</p>
                </div>
            @endforelse
            
        @elseif($profile['type'] === 'produsen')
            @forelse($profile['recent_transactions'] as $transaction)
                <div class="flex items-center gap-4 p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-colors">
                    <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                        <x-moonshine::icon icon="truck" size="5" class="text-blue-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-medium text-sm truncate">{{ $transaction->produk_nama ?? 'Produk' }}</p>
                        <p class="text-white/50 text-xs">
                            → {{ $transaction->pedagang_nama ?? 'Pedagang' }}
                            <span class="mx-1">•</span>
                            {{ \Carbon\Carbon::parse($transaction->tanggal)->format('d M Y') }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-blue-400 font-mono text-sm font-bold">{{ number_format($transaction->laku) }}</p>
                        <p class="text-white/40 text-xs">{{ $transaction->status ?? 'Ok' }}</p>
                    </div>
                </div>
            @empty
                <div class="text-center text-white/40 py-8">
                    <x-moonshine::icon icon="inbox" size="8" class="mx-auto mb-2 opacity-50" />
                    <p>Belum ada aktivitas</p>
                </div>
            @endforelse
            
        @elseif($profile['type'] === 'admin' || $profile['type'] === 'pengurus')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-amber-500/10 border border-amber-500/20 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <x-moonshine::icon icon="user-group" size="5" class="text-amber-400" />
                        <span class="text-amber-300 text-sm font-semibold">Pedagang</span>
                    </div>
                    <p class="text-white text-2xl font-bold font-mono">{{ $profile['stats']['total_pedagang'] ?? 0 }}</p>
                    <p class="text-white/50 text-xs mt-1">Total Pedagang</p>
                </div>
                
                <div class="bg-violet-500/10 border border-violet-500/20 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <x-moonshine::icon icon="building-office-2" size="5" class="text-violet-400" />
                        <span class="text-violet-300 text-sm font-semibold">Produsen</span>
                    </div>
                    <p class="text-white text-2xl font-bold font-mono">{{ $profile['stats']['total_produsen'] ?? 0 }}</p>
                    <p class="text-white/50 text-xs mt-1">Total Produsen</p>
                </div>
                
                <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <x-moonshine::icon icon="cube" size="5" class="text-emerald-400" />
                        <span class="text-emerald-300 text-sm font-semibold">Produk</span>
                    </div>
                    <p class="text-white text-2xl font-bold font-mono">{{ $profile['stats']['total_produk'] ?? 0 }}</p>
                    <p class="text-white/50 text-xs mt-1">Total Produk</p>
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-t border-white/10">
                <a href="/dashboard" class="flex items-center justify-center gap-2 py-3 bg-violet-600/30 hover:bg-violet-500/50 border border-violet-500/30 rounded-lg text-white font-semibold transition-all">
                    <x-moonshine::icon icon="home" size="5" />
                    Kembali ke Dashboard
                </a>
            </div>
        @endif
    </div>
</div>