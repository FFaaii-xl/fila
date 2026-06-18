@php
    $settings = app(\App\Services\SettingsService::class);
    $deadlineTime = $settings->get('submission_deadline_time', '14:00');
    $deadlineActive = $settings->get('submission_deadline_active', false);
    $serverTime = now()->toDateTimeString();
    $user = \Illuminate\Support\Facades\Auth::user();
    
    // Default: tidak ada exempt untuk Pedagang (kolom is_exempt tidak ada di tabel pedagang)
    $isExempt = false;
    
    // Get ops status for DRAFTING badge
    $ops = \Illuminate\Support\Facades\Cache::get("ops_status_" . date('Y-m-d'));
    $opsText = $ops['text'] ?? 'NO DRAFT';
    $opsColor = $ops['color'] ?? 'gray';
    $opsIcon = $ops['icon'] ?? 'clock';
    
    // Map status to badge class
    $opsBadgeClass = 'bg-white/[0.04] border-white/[0.08]';
    $opsIconColor = '#ffffff';
    if ($opsColor === 'warning') {
        $opsBadgeClass = 'bg-amber-950/90 border-amber-500/40';
        $opsIconColor = '#f59e0b';
    } elseif ($opsColor === 'primary') {
        $opsBadgeClass = 'bg-blue-950/90 border-blue-500/40';
        $opsIconColor = '#3b82f6';
    } elseif ($opsColor === 'success-opacity') {
        $opsBadgeClass = 'bg-emerald-950/90 border-emerald-500/40';
        $opsIconColor = '#10b981';
    }
@endphp

<div x-data="globalTimelineIntel({
        deadline: '{{ $deadlineTime }}',
        active: {{ $deadlineActive ? 'true' : 'false' }},
        serverTime: '{{ $serverTime }}',
        isExempt: {{ $isExempt ? 'true' : 'false' }},
        position: 'bottom-left'
    })"
    class="floating-countdown-badge transition-all duration-300"
    :class="{
        'fixed bottom-4 left-4 z-50': position === 'bottom-left',
        'fixed top-0 left-1/2 -translate-x-1/2 z-50': position === 'top-center'
    }"
    x-show="true">
    
    <!-- Status Badge (DRAFTING/PROSES/NOTA CETAK) with position selector -->
    <div @click="cyclePosition()" class="flex items-center rounded-l-full border {{ $opsBadgeClass }} px-2 py-1 gap-1 cursor-pointer hover:bg-white/10 transition-colors" style="height: 24px;" title="Klik untuk ubah posisi">
        <x-moonshine::icon :icon="$opsIcon" size="3" style="color: {{ $opsIconColor }} !important;" />
        <span class="font-bold uppercase" style="font-family: 'Outfit', sans-serif; line-height: 1; color: #ffffff !important; text-transform: uppercase !important;">{{ $opsText }}</span>
        <span class="text-[7px] text-white/30 ml-1" x-text="position === 'bottom-left' ? '▼' : '▲'"></span>
    </div>
    
    <!-- Time Out Badge -->
    <div class="px-2.5 py-1 rounded-r-full flex items-center border transition-all duration-500 shadow-lg" style="height: 24px; gap: 6px; background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08);"
         :class="urgency === 'high' ? 'bg-rose-950/90 border-rose-500/40 shadow-rose-500/10' : (urgency === 'mid' ? 'bg-amber-950/90 border-amber-500/40 shadow-amber-500/10' : 'bg-white/[0.04] border-white/[0.08] shadow-black/20')">
        
        <div class="relative flex items-center justify-center">
            <div class="w-1.5 h-1.5 rounded-full transition-colors duration-500" 
                 :class="urgency === 'high' ? 'bg-rose-500 animate-pulse' : (urgency === 'mid' ? 'bg-amber-500' : 'bg-emerald-500')"></div>
            <div x-show="urgency === 'high'" class="absolute inset-0 w-full h-full rounded-full bg-rose-500 animate-ping opacity-40"></div>
        </div>
        
        <div class="flex items-center" style="font-family: 'Outfit', sans-serif; gap: 6px;">
            <span class="font-bold uppercase" style="font-family: 'Outfit', sans-serif; line-height: 1; color: #ffffff !important; text-transform: uppercase !important;">TIME OUT</span>
            <span class="font-bold uppercase" style="font-family: 'Outfit', sans-serif; line-height: 1; text-transform: uppercase !important;"
                :class="urgency === 'high' ? 'text-rose-400 drop-shadow-[0_0_6px_rgba(244,63,94,0.4)]' : (urgency === 'mid' ? 'text-amber-400 drop-shadow-[0_0_6px_rgba(251,191,36,0.3)]' : 'text-emerald-400 drop-shadow-[0_0_6px_rgba(16,185,129,0.3)]')"
                x-text="timeLeft"></span>
            
            <template x-if="isExempt">
                <span class="text-[8px] italic text-white/40" style="line-height: 1; text-transform: lowercase !important;">(non aktif)</span>
            </template>
        </div>
    </div>
</div>

@once
<script>
    document.addEventListener("alpine:init", () => {
        if (Alpine.data("globalTimelineIntel")) return;

        Alpine.data("globalTimelineIntel", (config) => ({
            deadline: config.deadline,
            active: config.active,
            isExempt: config.isExempt,
            position: config.position || 'bottom-left',
            currentTime: "00:00:00",
            timeLeft: "00:00:00",
            urgency: "low",

            init() {
                // Restore saved position from localStorage
                const saved = localStorage.getItem('floatingStatusPosition');
                if (saved && (saved === 'bottom-left' || saved === 'top-center')) {
                    this.position = saved;
                }
                this.update();
                setInterval(() => this.update(), 1000);
            },

            cyclePosition() {
                // Cycle between bottom-left and top-center only
                const positions = ['bottom-left', 'top-center'];
                const currentIndex = positions.indexOf(this.position);
                const nextIndex = (currentIndex + 1) % positions.length;
                this.position = positions[nextIndex];
                
                // Save to localStorage
                localStorage.setItem('floatingStatusPosition', this.position);
            },

            update() {
                const now = new Date();
                this.currentTime = now.toTimeString().split(" ")[0];

                const [h, m] = this.deadline.split(":");
                const target = new Date();
                target.setHours(parseInt(h), parseInt(m), 0, 0);

                let diff = target - now;

                if (diff < 0) {
                    this.timeLeft = "EXPIRED";
                    this.urgency = "high";
                    return;
                }

                const hh = Math.floor(diff / 3600000);
                const mm = Math.floor((diff % 3600000) / 60000);
                const ss = Math.floor((diff % 60000) / 1000);

                this.timeLeft = [hh, mm, ss].map(v => v.toString().padStart(2, "0")).join(":");

                if (!this.active || this.isExempt) {
                    this.urgency = "low";
                    return;
                }

                if (diff < 600000) this.urgency = "high"; // 10 mins
                else if (diff < 1800000) this.urgency = "mid"; // 30 mins
                else this.urgency = "low";
            }
        }));
    });
</script>
@endonce
