<div class="bg-gradient-to-br from-violet-950/60 via-purple-950/40 to-indigo-950/60 rounded-xl p-6 mb-6 border border-purple-500/20 shadow-xl shadow-purple-900/20">
    <div class="flex items-center gap-6">
        <!-- Avatar -->
        <div class="relative">
            <div class="w-24 h-24 rounded-2xl overflow-hidden bg-gradient-to-br from-purple-600 to-violet-700 flex items-center justify-center shadow-lg shadow-purple-500/30 border-2 border-purple-400/30">
                @if($profile['entity']->avatar ?? false)
                    <img src="{{ Storage::url($profile['entity']->avatar) }}" class="w-full h-full object-cover" alt="{{ $profile['entity']->nama ?? 'Avatar' }}">
                @else
                    <span class="text-4xl font-black text-white/90" style="font-family: 'Playfair Display', serif;">
                        {{ strtoupper(substr($profile['entity']->nama ?? $user->name, 0, 1)) }}
                    </span>
                @endif
            </div>
            <!-- Role Badge -->
            <div class="absolute -bottom-2 -right-2 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider shadow-lg
                @if($user->owner_type === 'Admin') bg-amber-500 text-black border-2 border-amber-400
                @elseif($user->owner_type === 'Pengurus') bg-purple-500 text-white border-2 border-purple-400
                @elseif($user->owner_type === 'Pedagang') bg-emerald-500 text-white border-2 border-emerald-400
                @else bg-blue-500 text-white border-2 border-blue-400
                @endif"
                style="font-family: 'Outfit', sans-serif;">
                {{ $user->owner_type }}
            </div>
        </div>
        
        <!-- Info -->
        <div class="flex-1">
            <h2 class="text-2xl font-black text-white mb-1" style="font-family: 'Playfair Display', serif;">
                {{ $profile['entity']->nama ?? $user->name }}
            </h2>
            @if($profile['type'] === 'pedagang' && $profile['entity']->alamat)
                <p class="text-purple-300 text-sm mb-2">
                    <x-moonshine::icon icon="map-pin" size="4" class="inline mr-1" />
                    {{ $profile['entity']->alamat }}
                </p>
            @endif
            @if($profile['entity']->no_hp)
                <p class="text-white/60 text-sm">
                    <x-moonshine::icon icon="phone" size="4" class="inline mr-1" />
                    {{ $profile['entity']->no_hp }}
                </p>
            @endif
            <div class="flex items-center gap-4 mt-3">
                <span class="text-xs text-white/50">
                    <x-moonshine::icon icon="calendar" size="3" class="inline mr-1" />
                    Bergabung: {{ $profile['entity']->created_at?->format('d M Y') ?? 'N/A' }}
                </span>
                @if($profile['type'] === 'produsen')
                    <span class="text-xs text-white/50">
                        <x-moonshine::icon icon="cube" size="3" class="inline mr-1" />
                        {{ $profile['produk_count'] ?? 0 }} Produk
                    </span>
                @endif
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="flex flex-col gap-2">
            <a href="/insight/{{ $profile['type'] }}/{{ $profile['entity']->id }}" 
               class="px-4 py-2 bg-purple-600/30 hover:bg-purple-500/50 border border-purple-500/30 rounded-lg text-white text-sm font-semibold transition-all hover:scale-105 flex items-center gap-2">
                <x-moonshine::icon icon="eye" size="4" />
                Lihat Detail
            </a>
            @if($profile['type'] === 'pedagang')
                <a href="/tabungan-admin?pedagang={{ $profile['entity']->id }}" 
                   class="px-4 py-2 bg-emerald-600/30 hover:bg-emerald-500/50 border border-emerald-500/30 rounded-lg text-white text-sm font-semibold transition-all hover:scale-105 flex items-center gap-2">
                    <x-moonshine::icon icon="banknotes" size="4" />
                    Tabungan
                </a>
            @endif
        </div>
    </div>
</div>