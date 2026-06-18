<div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-xl p-5 border border-gray-700/50 shadow-xl">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 bg-emerald-400 rounded-full animate-pulse"></span>
            <h3 class="text-lg font-semibold text-white">Online Users</h3>
        </div>
        <span id="online-count" class="text-sm font-medium text-emerald-400">{{ $onlineCount }}</span>
    </div>

    <div id="online-users-list" class="space-y-3 max-h-64 overflow-y-auto">
        @forelse($onlineUsers as $user)
        <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-800/50 hover:bg-gray-800 transition-colors">
            <span class="text-xl">{{ $user['role']['icon'] }}</span>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <span class="font-medium text-white truncate">{{ $user['name'] }}</span>
                    <span class="text-xs text-gray-400 whitespace-nowrap ml-2">{{ $user['last_activity_ago'] }}</span>
                </div>
                <div class="text-xs text-gray-400 mt-0.5">
                    {{ $user['role']['label'] }}
                    @if($user['email'])
                        • {{ $user['email'] }}
                    @endif
                </div>
                @if(!empty($user['activities']))
                <div class="mt-2 space-y-1">
                    @foreach($user['activities'] as $activity)
                    <div class="flex items-center gap-1.5 text-xs text-gray-300">
                        <span>📝</span>
                        <span class="truncate">{{ $activity->description }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-6 text-gray-400">
            <span class="text-2xl">💤</span>
            <p class="mt-2 text-sm">No users online</p>
        </div>
        @endforelse
    </div>

    <div class="mt-3 pt-3 border-t border-gray-700/50 flex items-center justify-between">
        <span class="text-xs text-gray-500">Last updated: <span id="last-updated">{{ now()->format('H:i:s') }}</span></span>
        <button onclick="refreshOnlineUsers()" class="text-xs text-emerald-400 hover:text-emerald-300 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
        </button>
    </div>
</div>

<script>
function refreshOnlineUsers() {
    fetch('/admin/api/online-users')
        .then(res => res.json())
        .then(data => {
            document.getElementById('online-count').textContent = data.count;
            document.getElementById('last-updated').textContent = new Date().toLocaleTimeString('id-ID');
            // Optionally update list
        })
        .catch(err => console.error('Failed to refresh:', err));
}

// Auto-refresh every 30 seconds
setInterval(refreshOnlineUsers, 30000);
</script>