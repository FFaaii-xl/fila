@php
    $today = date('Y-m-d');

    $transaksiKosong = \Illuminate\Support\Facades\DB::table('transaksi')
        ->whereBetween('tanggal', [$today.' 00:00:00', $today.' 23:59:59'])
        ->whereNull('deleted_at')
        ->doesntExist();

    $adaDraft = \Illuminate\Support\Facades\DB::table('penjualan')
        ->whereBetween('tanggal', [$today.' 00:00:00', $today.' 23:59:59'])
        ->where('status', 'Draft')
        ->whereNull('deleted_at')
        ->exists();

    $pulse = false;
    if ($transaksiKosong && !$adaDraft) {
        $statusText = 'Belum Ada Data';
        $statusStyle = 'bg-slate-900/90 text-slate-400 border-slate-700/50';
        $icon = 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'; // Clock
    } elseif ($transaksiKosong && $adaDraft) {
        $statusText = 'Ada Draft';
        $statusStyle = 'bg-gradient-to-r from-amber-600/20 to-yellow-500/20 text-amber-400 border-amber-500/30';
        $icon = 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99'; 
        $pulse = true;
    } elseif (!$transaksiKosong && !$adaDraft) {
        $statusText = 'Selesai';
        $statusStyle = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        $icon = 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'; // Check
    } else {
        $statusText = 'Parsial';
        $statusStyle = 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
        $icon = 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3Z'; 
    }
@endphp

<!-- Status Bar NATIVE Sidebar Inline -->
<div class="px-3 mt-3 mb-2" id="citroroso-daily-status">
    <div class="px-3 py-1.5 rounded-xl border flex items-center gap-2 transition-all duration-300 {{ $statusStyle }}">
        @if($pulse)
        <span class="relative flex h-2 w-2">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.8)]"></span>
        </span>
        @else
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-inherit">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
        </svg>
        @endif
        
        <span class="{{ str_contains($statusStyle, 'bg-clip-text') ? 'bg-gradient-to-r from-amber-300 to-yellow-500 bg-clip-text text-transparent transform' : '' }}">
            Status: {{ $statusText }}
        </span>
    </div>
</div>
