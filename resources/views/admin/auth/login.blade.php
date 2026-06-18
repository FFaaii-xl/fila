<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon Icons -->
    <link rel="icon" type="image/x-icon" href="/image/favicon.ico">
    <link rel="apple-touch-icon" href="/image/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/image/favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="/image/favicon.ico">
    <title>Citroroso Heritage | Portal Akses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,900&display=swap" rel="stylesheet">
    <style>
        :root {
            --onyx: #0a0a0a;
            /* Default: Emerald (Admin) */
            --primary: #10b981;
            --primary-glow: rgba(16, 185, 129, 0.2);
            --primary-light: #34d399;
        }
        
        /* Role-based color schemes */
        [data-role="admin"] {
            --primary: #10b981;
            --primary-glow: rgba(16, 185, 129, 0.2);
            --primary-light: #34d399;
        }
        
        [data-role="pengurus"] {
            --primary: #a855f7;
            --primary-glow: rgba(168, 85, 247, 0.2);
            --primary-light: #c084fc;
        }
        
        [data-role="pedagang"] {
            --primary: #f59e0b;
            --primary-glow: rgba(245, 158, 11, 0.2);
            --primary-light: #fbbf24;
        }
        
        [data-role="produsen"] {
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.2);
            --primary-light: #60a5fa;
        }
        
        body { 
            background-color: var(--onyx); 
            font-family: 'Outfit', sans-serif;
            color: white;
            background-image: radial-gradient(circle at 50% -20%, #1e293b 0%, var(--onyx) 80%);
        }
        .font-playfair { font-family: 'Playfair Display', serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.015);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 20px;
        }
        .input-editorial {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }
        .input-editorial:focus {
            border-color: var(--primary);
            background: rgba(var(--primary-rgb, 16, 185, 129), 0.02);
            box-shadow: 0 0 15px var(--primary-glow);
        }
        .btn-editorial {
            background: var(--primary);
            color: var(--onyx);
            font-weight: 800;
            transition: all 0.3s ease;
        }
        .btn-editorial:hover {
            transform: translateY(-1px);
            filter: brightness(1.1);
            box-shadow: 0 5px 20px var(--primary-glow);
        }
        .role-selector {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }
        .role-selector:focus {
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary-glow);
            outline: none;
        }
        .glow-orb {
            transition: all 0.5s ease;
        }
        .role-badge {
            transition: all 0.3s ease;
        }
        .role-badge:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4 selection:bg-emerald-500 selection:text-black" data-role="admin">

    <div class="w-full max-w-sm">
        <!-- Editorial Header -->
        <div class="text-center mb-8">
            <div class="inline-block mb-4 relative">
                <div class="absolute inset-0 bg-emerald-500 blur-2xl opacity-10 rounded-full glow-orb" id="glowOrb"></div>
                <img src="/image/logo200.png" alt="Logo" class="h-14 relative z-10 filter drop-shadow-lg">
            </div>
            
            <h1 class="font-playfair text-4xl font-black italic tracking-tighter text-white mb-1.5 leading-none">
                Citroroso
            </h1>
            <div class="flex items-center justify-center gap-3 opacity-30">
                <div class="h-px w-6 bg-white"></div>
                <p class="text-[8px] font-black uppercase tracking-[0.3em]" id="portalLabel">Heritage Guardian Portal</p>
                <div class="h-px w-6 bg-white"></div>
            </div>
        </div>

        <!-- Auth Card -->
        <div class="glass-card p-8 relative overflow-hidden">
            <div class="absolute -top-4 -right-4 w-16 h-16 bg-emerald-500/5 blur-2xl rounded-full glow-orb" id="cardGlow"></div>
            
            <form action="{{ route('login.post') }}" method="POST" class="space-y-6">
                @csrf
                
                <!-- Username -->
                <div class="space-y-2.5">
                    <label class="block text-[9px] font-black uppercase tracking-[0.25em] ml-1" style="color: var(--primary-light); opacity: 0.7;">Kredensial Akses</label>
                    <input type="text" 
                           name="username" 
                           placeholder="Nama Pengguna" 
                           class="input-editorial w-full text-white text-sm rounded-xl px-5 py-3.5 outline-none placeholder:text-white/5 font-medium"
                           required 
                           autofocus>
                    @if($errors->has('username'))
                        <p class="text-red-400 text-[10px] font-bold mt-1.5 ml-1 italic opacity-80">! {{ $errors->first('username') }}</p>
                    @endif
                </div>

                <!-- Password -->
                <div class="space-y-2.5">
                    <label class="block text-[9px] font-black uppercase tracking-[0.25em] ml-1" style="color: var(--primary-light); opacity: 0.7;">Kata Sandi</label>
                    <input type="password" 
                           name="password" 
                           placeholder="••••••••" 
                           class="input-editorial w-full text-white text-sm rounded-xl px-5 py-3.5 outline-none placeholder:text-white/5 font-medium"
                           required>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" 
                            class="btn-editorial w-full py-3.5 rounded-xl text-[10px] uppercase tracking-[0.2em]">
                        <span id="submitLabel">Otorisasi Masuk</span>
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-[8px] font-bold text-white/10 uppercase tracking-widest leading-relaxed italic">
                    Sistem Manajemen Warisan Nusantara<br>
                    &copy; {{ date('Y') }} Citroroso Heritage
                </p>
            </div>
        </div>
    </div>

</body>
</html>
