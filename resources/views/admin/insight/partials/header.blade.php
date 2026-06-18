<div class="flex items-center justify-between p-4 bg-gradient-to-r from-primary/20 to-secondary/10 rounded-xl border border-primary/20">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-primary/30 flex items-center justify-center">
            <x-moonshine::icon icon="user-circle" size="6" />
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider opacity-50">Universal Insight</div>
            <div class="text-2xl font-bold">{{ $name }}</div>
            <div class="text-sm opacity-70">
                <span class="px-2 py-0.5 rounded bg-primary/30 text-xs uppercase">{{ $type }}</span>
                ID: {{ $entity->id ?? 'N/A' }}
            </div>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="/{{ $type === 'produsen' ? 'produsen' : ($type === 'produk' ? 'produk' : 'pedagang') }}" class="btn btn-secondary btn-sm">
            <x-moonshine::icon icon="arrow-left" size="4" />
            Kembali
        </a>
    </div>
</div>