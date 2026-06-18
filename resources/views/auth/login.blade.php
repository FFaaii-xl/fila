<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Citroroso</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/citroroso.css') }}" rel="stylesheet">
</head>
<body class="bg-gradient">
    
    <!-- Theme Toggle -->
    <button class="theme-toggle md" style="position: fixed; top: 1rem; right: 1rem;" onclick="toggleTheme()" title="Toggle theme">
        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        </svg>
        <svg class="moon-icon" style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
        </svg>
    </button>
    
    <div class="container sm mx-auto" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; padding: 1rem;">
        <!-- Logo & Branding -->
        <div class="text-center" style="margin-bottom: 2rem;">
            <div class="logo-box lg mx-auto" style="margin-bottom: 1.5rem;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <h1 class="brand-name lg">Citroroso</h1>
            <p style="color: var(--text-secondary); font-size: 0.875rem;">Manajemen Pasar Terpadu & Modern</p>
        </div>

        <!-- Login Card -->
        <div class="card">
            <h2 class="card-title">Masuk</h2>

            <!-- Session Status -->
            @if (session('status'))
                <div class="success-alert">
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
                    <div class="error-alert">
                        {{ $loginError }}
                    </div>
                @endif
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                
                <!-- Username -->
                <div class="form-group">
                    <label for="name" class="form-label">Username</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name') }}"
                            required 
                            autofocus
                            placeholder="Masukkan username"
                            class="form-input"
                        />
                    </div>
                    @error('name')
                        <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0110 0v4"></path>
                        </svg>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            required
                            placeholder="Masukkan password"
                            class="form-input"
                        />
                        <button type="button" onclick="togglePassword()" class="toggle-password">
                            <svg id="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p style="color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="checkbox-wrapper">
                    <input type="checkbox" name="remember" id="remember" class="checkbox" />
                    <label for="remember" class="checkbox-label">Ingat saya</label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-primary lg">
                    Masuk
                </button>
            </form>

            <!-- Back to Home -->
            <a href="{{ url('/') }}" class="back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"></path>
                </svg>
                Kembali ke Beranda
            </a>
        </div>

        <!-- Footer -->
        <div class="footer" style="margin-top: 2rem;">
            &copy; {{ date('Y') }} Citroroso
        </div>
    </div>

    <script src="{{ asset('js/theme.js') }}"></script>
</body>
</html>
