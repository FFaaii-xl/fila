<div class="space-y-2">
    <div class="text-xs uppercase tracking-wider opacity-50 mb-3">Aksi Cepat</div>
    
    @if($type === 'pedagang')
    <a href="/merchant-sales?pedagang_id={{ $entity->id }}" class="flex items-center gap-2 p-3 bg-white/5 hover:bg-white/10 rounded-lg transition">
        <x-moonshine::icon icon="presentation-chart-line" size="4" />
        <span class="text-sm">Lihat Penjualan</span>
    </a>
    <a href="/tabungan-admin?owner_type=Pedagang" class="flex items-center gap-2 p-3 bg-white/5 hover:bg-white/10 rounded-lg transition">
        <x-moonshine::icon icon="banknotes" size="4" />
        <span class="text-sm">Riwayat Tabungan</span>
    </a>
    @elseif($type === 'produsen')
    <a href="/producer-sales?produsen_id={{ $entity->id }}" class="flex items-center gap-2 p-3 bg-white/5 hover:bg-white/10 rounded-lg transition">
        <x-moonshine::icon icon="chart-bar" size="4" />
        <span class="text-sm">Lihat Penjualan</span>
    </a>
    <a href="/tabungan-admin?owner_type=Produsen" class="flex items-center gap-2 p-3 bg-white/5 hover:bg-white/10 rounded-lg transition">
        <x-moonshine::icon icon="banknotes" size="4" />
        <span class="text-sm">Riwayat Tabungan</span>
    </a>
    @elseif($type === 'produk')
    <a href="/merchant-sales?produk_id={{ $entity->id }}" class="flex items-center gap-2 p-3 bg-white/5 hover:bg-white/10 rounded-lg transition">
        <x-moonshine::icon icon="shopping-cart" size="4" />
        <span class="text-sm">Lihat Distribusi</span>
    </a>
    @endif
    
    <a href="/universal-insight" class="flex items-center gap-2 p-3 bg-white/5 hover:bg-white/10 rounded-lg transition">
        <x-moonshine::icon icon="arrow-left" size="4" />
        <span class="text-sm">Kembali ke Daftar</span>
    </a>
</div>