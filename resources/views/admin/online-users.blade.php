<x-moonshine::layout>
    <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-6 border border-gray-700/50 shadow-xl">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <span class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></span>
                <h1 class="text-2xl font-bold text-white">Online Users</h1>
                <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-sm font-medium">
                    {{ $onlineCount }} online
                </span>
            </div>
            <button onclick="refreshData()" class="flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-4 mb-6">
            <div class="flex gap-2">
                <a href="{{ route('admin.online-users') }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ !$filters['role'] ? 'bg-emerald-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    All
                </a>
                <a href="{{ route('admin.online-users', ['role' => 'admin']) }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filters['role'] === 'admin' ? 'bg-emerald-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    👑 Admin
                </a>
                <a href="{{ route('admin.online-users', ['role' => 'pengurus']) }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filters['role'] === 'pengurus' ? 'bg-emerald-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    👤 Pengurus
                </a>
                <a href="{{ route('admin.online-users', ['role' => 'pedagang']) }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filters['role'] === 'pedagang' ? 'bg-emerald-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    🛒 Pedagang
                </a>
                <a href="{{ route('admin.online-users', ['role' => 'produsen']) }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filters['role'] === 'produsen' ? 'bg-emerald-500 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">
                    🏭 Produsen
                </a>
            </div>
            <form method="GET" action="{{ route('admin.online-users') }}" class="flex gap-2">
                <input type="hidden" name="role" value="{{ $filters['role'] }}">
                <input type="text" 
                       name="search" 
                       value="{{ $filters['search'] }}"
                       placeholder="Search name or email..." 
                       class="px-4 py-2 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <button type="submit" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg transition-colors">
                    Search
                </button>
            </form>
        </div>

        <!-- Users List -->
        <div class="space-y-4">
            @forelse($onlineUsers as $user)
            <div class="p-4 bg-gray-800/50 rounded-xl border border-gray-700/50 hover:border-emerald-500/30 transition-colors">
                <div class="flex items-start gap-4">
                    <span class="text-3xl">{{ $user['role']['icon'] }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-white">{{ $user['name'] }}</h3>
                                <p class="text-sm text-gray-400">
                                    {{ $user['role']['label'] }}
                                    @if($user['email'])
                                        • {{ $user['email'] }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-sm">
                                    <span class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-pulse"></span>
                                    Online
                                </span>
                                <p class="text-xs text-gray-500 mt-1">{{ $user['last_activity_ago'] }}</p>
                            </div>
                        </div>
                        
                        <!-- Activity Log -->
                        @if(!empty($user['activities']))
                        <div class="mt-4 pl-4 border-l-2 border-gray-700">
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Recent Activity</h4>
                            <div class="space-y-2">
                                @foreach($user['activities'] as $activity)
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="text-gray-500">📝</span>
                                    <span class="text-gray-300 flex-1">{{ $activity->description }}</span>
                                    <span class="text-xs text-gray-500 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-12 text-gray-400">
                <span class="text-5xl">💤</span>
                <p class="mt-4 text-lg">No users online</p>
                <p class="text-sm text-gray-500">All users are currently offline</p>
            </div>
            @endforelse
        </div>

        <!-- Footer -->
        <div class="mt-6 pt-4 border-t border-gray-700/50 flex items-center justify-between text-sm text-gray-500">
            <span>Showing {{ $onlineUsers->count() }} users</span>
            <span>Last updated: {{ now()->format('H:i:s') }}</span>
        </div>
    </div>

    <script>
    function refreshData() {
        window.location.reload();
    }
    
    // Auto-refresh every 30 seconds
    setInterval(() => {
        fetch('/admin/api/online-users')
            .then(res => res.json())
            .then(data => {
                document.querySelector('.bg-emerald-500\\/20.text-emerald-400.rounded-full.text-sm.font-medium').textContent = data.count + ' online';
            })
            .catch(() => {});
    }, 30000);
    </script>
</x-moonshine::layout>