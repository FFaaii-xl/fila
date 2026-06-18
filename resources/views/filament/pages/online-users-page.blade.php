<x-filament-panels::page>
    <x-filament::section>
        <div class='flex items-center gap-4'>
            <span class='w-3 h-3 bg-emerald-400 rounded-full animate-pulse'></span>
            <h1 class='text-2xl font-bold text-gray-900 dark:text-white'>Online Users</h1>
            <span class='px-3 py-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-full text-sm font-medium'>
                {{ $onlineCount }} online
            </span>
        </div>
    </x-filament::section>

    @if(!empty($usersWithActivities))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($usersWithActivities as $user)
                <x-filament::section>
                    <div class='flex items-start gap-4'>
                        <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-full text-2xl">
                            {{ $user['role']['icon'] ?? '👤' }}
                        </div>
                        <div class='flex-1 min-w-0'>
                            <div class='flex items-center justify-between mb-1'>
                                <h3 class='text-lg font-semibold text-gray-900 dark:text-white truncate'>{{ $user['name'] }}</h3>
                                <span class='text-xs text-gray-500 whitespace-nowrap ml-2'>{{ $user['last_activity_ago'] }}</span>
                            </div>
                            <p class='text-sm text-gray-500 dark:text-gray-400 mb-4'>{{ $user['role']['label'] ?? 'Unknown' }}</p>
                            
                            @if(!empty($user['activities']))
                                <div class='space-y-3 pl-3 border-l-2 border-gray-200 dark:border-gray-700'>
                                    @foreach($user['activities'] as $activity)
                                        <div class='relative flex items-start'>
                                            <span class="absolute -left-[17px] top-1 w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600 ring-4 ring-white dark:ring-gray-900"></span>
                                            <div class='flex-1 min-w-0 flex justify-between gap-2 text-sm'>
                                                <span class='text-gray-600 dark:text-gray-300 truncate'>{{ $activity->description }}</span>
                                                <span class='text-xs text-gray-400 whitespace-nowrap'>{{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans(null, true, true) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-gray-400 italic">Tidak ada aktivitas terbaru.</p>
                            @endif
                        </div>
                    </div>
                </x-filament::section>
            @endforeach
        </div>
    @else
        <x-filament::section>
            <div class='text-center py-12'>
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 mb-4">
                    <x-heroicon-o-moon class="w-8 h-8 text-gray-400" />
                </div>
                <p class='text-lg font-medium text-gray-900 dark:text-gray-300'>No users online</p>
                <p class='mt-1 text-sm text-gray-500'>All users are currently offline</p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
