<div x-data="{ loading: false }" 
     x-show="loading" 
     x-cloak
     @submit.window="loading = true" 
     @popstate.window="loading = false"
     @pageshow.window="loading = false"
     @click.window="if($event.target.closest('a') && !$event.target.closest('a').getAttribute('href')?.startsWith('#') && !$event.target.closest('a').target && $event.target.closest('a').getAttribute('href')) loading = true"
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm"
     style="display: none;">
    
    <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl shadow-2xl flex flex-col items-center space-y-4 border border-slate-200 dark:border-slate-700">
        {{-- Animated Spinner --}}
        <div class="relative h-16 w-16">
            <div class="absolute inset-0 rounded-full border-4 border-slate-200 dark:border-slate-700"></div>
            <div class="absolute inset-0 rounded-full border-4 border-blue-500 border-t-transparent animate-spin"></div>
        </div>
        
        <div class="text-center">
            <p class="text-sm font-bold text-slate-800 dark:text-slate-100 uppercase tracking-widest">Memproses Data</p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1">Sistem sedang menyiapkan laporan Anda...</p>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 0.8s linear infinite;
    }
</style>

{{-- Usage: Include this component in your main layout, and it will automatically show the loader on form submissions and link clicks. --}}
{{-- Example: <x-global-loader /> --}}