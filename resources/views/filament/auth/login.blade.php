<div class="min-h-screen flex items-center justify-center px-4 py-12 bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
    <div class="w-full max-w-md">
        <!-- Logo & Branding -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 mb-6">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/25">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-3xl font-extrabold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent tracking-tight mb-2 dark:from-emerald-400 dark:to-teal-400">Citroroso</h1>
            <p class="text-slate-600 dark:text-slate-400 text-sm">Sistem Privat — Hanya untuk member terdaftar</p>
        </div>

        <!-- Login Card -->
        <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-3xl blur opacity-20"></div>
            <div class="relative bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-200/50 dark:border-slate-700/50 p-8 transition-all hover:shadow-lg">
                
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white text-center mb-6">Masuk</h2>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="mb-4 p-4 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 text-sm border border-emerald-200 dark:border-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Error Alert -->
                @if ($errors->any())
                    @php
                        $loginError = null;
                        foreach ($errors->all() as $error) {
                            if (Str::contains($error, ['credentials', 'login', 'masuk'])) {
                                $loginError = $error;
                                break;
                            }
                        }
                    @endphp
                    @if($loginError)
                        <div class="mb-4 p-4 rounded-xl bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 text-sm border border-red-200 dark:border-red-800">
                            {{ $loginError }}
                        </div>
                    @endif
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf
                    
                    <!-- Username -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Username
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                name="name" 
                                id="name" 
                                value="{{ old('name') }}"
                                required 
                                autofocus
                                placeholder="Masukkan username"
                                class="w-full pl-12 pr-4 py-3.5 rounded-xl bg-slate-100 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-all"
                            />
                        </div>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                required
                                placeholder="Masukkan password"
                                class="w-full pl-12 pr-12 py-3.5 rounded-xl bg-slate-100 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-transparent transition-all"
                            />
                            <button 
                                type="button" 
                                onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center"
                            >
                                <svg id="eye-icon" class="w-5 h-5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="remember" 
                            id="remember"
                            class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-emerald-600 focus:ring-emerald-500 dark:focus:ring-emerald-400 bg-slate-100 dark:bg-slate-700"
                        />
                        <label for="remember" class="ml-2 text-sm text-slate-600 dark:text-slate-400">
                            Ingat saya
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]"
                    >
                        Masuk
                    </button>
                </form>

                <!-- Back to Home -->
                <div class="mt-6 text-center">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-slate-500 dark:text-slate-400 text-sm">
                &copy; {{ date('Y') }} Citroroso. Sistem privat.
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            }
        }
    </script>
</div>
