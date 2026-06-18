<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Citroroso - Sistem Manajemen pasar</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="{{ asset('css/citroroso.css') }}" rel="stylesheet">
    </head>
    <body>
        
        <!-- Background Decorations -->
        <div style="position: fixed; inset: 0; z-index: -1; overflow: hidden; pointer-events: none;">
            <div class="hero-gradient"></div>
            <div class="bg-blob emerald" style="top: 5rem; left: 2.5rem; width: 18rem; height: 18rem;"></div>
            <div class="bg-blob teal" style="top: 10rem; right: 5rem; width: 24rem; height: 24rem;"></div>
            <div class="bg-blob cyan" style="bottom: 5rem; left: 33%; width: 20rem; height: 20rem;"></div>
        </div>

        <!-- Navbar -->
        <nav class="glass" style="position: fixed; top: 0; left: 0; right: 0; z-index: 50; padding: 0 1rem;">
            <div class="container lg mx-auto" style="display: flex; justify-content: space-between; align-items: center; height: 5rem;">
                <div class="logo-wrapper" style="display: flex; align-items: center; gap: 0.75rem;">
                    <div class="logo-box md">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"></path>
                        </svg>
                    </div>
                    <span class="brand-name md">Citroroso</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <button class="theme-toggle md" onclick="toggleTheme()" title="Toggle theme">
                        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                        <svg class="moon-icon" style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
                        </svg>
                    </button>
                    @auth
                        <a href="{{ url('/admin') }}" class="btn-primary md">Dashboard</a>
                    @else
                        <a href="{{ url('/login') }}" class="btn-primary md">Masuk</a>
                    @endauth
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main style="position: relative; padding-top: 8rem; padding-bottom: 5rem; min-height: 100vh; display: flex; flex-direction: column; justify-content: center;">
            <div class="container lg mx-auto text-center">
                <h1 class="hero-title">
                    <span class="line-1">Manajemen Pasar</span>
                    <span class="line-2">Terpadu & Modern</span>
                </h1>
                
                <p class="hero-description">
                    Platform pengelolaan pedagang, produsen, dan transaksi pasar dengan sistem terintegrasi.
                </p>
            </div>

            <div class="container lg mx-auto" style="margin-top: 6rem;">
                <div class="grid grid-cols-1 md:grid-cols-3">
                    <div class="feature-card">
                        <div class="feature-icon emerald">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"></path>
                            </svg>
                        </div>
                        <h3 class="feature-title">Pedagang & Produsen</h3>
                        <p class="feature-description">Manajemen data lengkap dengan role-based access</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon blue">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"></path>
                            </svg>
                        </div>
                        <h3 class="feature-title">Transaksi Harian</h3>
                        <p class="feature-description">Pencatatan real-time dengan update otomatis</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon amber">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H15V10.5z"></path>
                            </svg>
                        </div>
                        <h3 class="feature-title">Saldo & Tabungan</h3>
                        <p class="feature-description">Auto-hitung dengan sistem terintegrasi</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="footer-centered">
            <div class="container lg mx-auto text-center">
                <div class="footer-logo">
                    <div class="logo-box sm">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"></path>
                        </svg>
                    </div>
                    <span class="footer-brand">Citroroso</span>
                </div>
                <p class="footer-text">&copy; {{ date('Y') }} Citroroso</p>
            </div>
        </footer>

        <script src="{{ asset('js/theme.js') }}"></script>
    </body>
</html>
