<div class="flex items-center justify-center min-h-[400px]">
    <div class="text-center">
        <div class="mb-4">
            <x-moonshine::icon icon="eye" size="10" class="mx-auto text-violet-400 animate-pulse" />
        </div>
        <h2 class="text-2xl font-bold text-white mb-2" style="font-family: 'Playfair Display', serif;">
            Mengalihkan ke Profil...
        </h2>
        <p class="text-white/60 mb-4">{{ $user->name ?? 'User' }}</p>
        <meta http-equiv="refresh" content="0;url={{ $redirectUrl }}">
        <script>window.location.href = '{{ $redirectUrl }}';</script>
    </div>
</div>