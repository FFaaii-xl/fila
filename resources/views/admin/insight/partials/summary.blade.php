<div class="space-y-3">
    <div class="grid grid-cols-2 gap-3">
        @foreach($data['metrics'] as $key => $value)
            <div class="p-3 bg-white/5 rounded-lg">
                <div class="text-xs uppercase tracking-wider opacity-50 mb-1">{{ str_replace('_', ' ', $key) }}</div>
                <div class="text-lg font-bold font-mono">{{ is_numeric($value) ? number_format($value) : $value }}</div>
            </div>
        @endforeach
    </div>
    
    @if(isset($data['saldo']))
    <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg">
        <div class="text-xs uppercase tracking-wider opacity-50 mb-1">Saldo</div>
        <div class="text-xl font-bold text-emerald-400">Rp {{ number_format($data['saldo']->jumlah ?? 0) }}</div>
    </div>
    @endif
    
    @if(isset($data['recent_transactions']) && $data['recent_transactions']->count() > 0)
    <div class="mt-4">
        <div class="text-xs uppercase tracking-wider opacity-50 mb-2">Aktivitas Terbaru</div>
        <div class="space-y-1">
            @foreach($data['recent_transactions']->take(5) as $tx)
            <div class="flex justify-between text-xs p-2 bg-white/5 rounded">
                <span>{{ date('d/m', strtotime($tx->tanggal)) }}</span>
                <span class="font-mono">{{ $tx->titip ?? 0 }} / {{ $tx->laku ?? 0 }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>