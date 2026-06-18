<div class="fi-wi-stats-overview-stats-grid-root" x-data="{ statFilter: '' }">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
            Aksi Cepat
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach($actions as $action)
                <a href="{{ $action['url'] }}" 
                   class="group flex flex-col items-center p-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-emerald-500 dark:hover:border-emerald-500 transition-all hover:shadow-lg hover:shadow-{{ $action['color'] }}-500/20 hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-lg bg-{{ $action['color'] }}-100 dark:bg-{{ $action['color'] }}-900/30 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                        <x-dynamic-component :component="$action['icon']" class="w-5 h-5 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white text-center">{{ $action['label'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 text-center mt-1">{{ $action['description'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
