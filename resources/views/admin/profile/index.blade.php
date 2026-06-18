<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    
    <div class="md:col-span-1 space-y-6">
        <div class="box p-6 text-center">
            <div class="relative inline-block">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0D8ABC&color=fff&size=128" 
                     alt="Avatar" 
                     class="w-32 h-32 rounded-full border-4 border-slate-100 dark:border-dark-200 object-cover mx-auto">
                <span class="absolute bottom-2 right-2 w-5 h-5 bg-green-500 border-4 border-white dark:border-dark-600 rounded-full"></span>
            </div>
            <h2 class="mt-4 font-bold text-xl">{{ $user->name }}</h2>
            <p class="text-sm opacity-60">{{ $user->email }}</p>
            <div class="mt-4 flex justify-center gap-2">
                <span class="px-3 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded-full text-xs font-bold uppercase">
                    {{ $user->owner_type ?? 'Administrator' }}
                </span>
            </div>
        </div>

        <div class="box p-4 space-y-3">
            <h3 class="text-xs font-black uppercase opacity-50 border-b pb-2 mb-2">Technical Context</h3>
            <div class="flex justify-between text-sm">
                <span class="opacity-60">Primary Device</span>
                <span class="font-medium">Poco F7</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="opacity-60">Ride</span>
                <span class="font-medium">NMAX New (NV8)</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="opacity-60">Base Location</span>
                <span class="font-medium">Surakarta</span>
            </div>
        </div>
    </div>

    <div class="md:col-span-2 space-y-6">
        
        <div class="box">
            <div class="box-header p-4 border-b dark:border-dark-200">
                <h3 class="font-bold text-md">Informasi Dasar</h3>
            </div>
            <div class="box-body p-4">
                <form action="#" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label text-xs font-bold uppercase opacity-60">Nama Lengkap</label>
                            <input type="text" name="name" value="{{ $user->name }}" class="form-input w-full mt-1" placeholder="Nama anda">
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold uppercase opacity-60">Alamat Email</label>
                            <input type="email" name="email" value="{{ $user->email }}" class="form-input w-full mt-1" placeholder="email@domain.com">
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary btn-sm px-6">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="box">
            <div class="box-header p-4 border-b dark:border-dark-200">
                <h3 class="font-bold text-md">Keamanan Akun</h3>
            </div>
            <div class="box-body p-4">
                <form action="#" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label text-xs font-bold uppercase opacity-60">Password Baru</label>
                            <input type="password" name="password" class="form-input w-full mt-1" placeholder=" Minimal 8 karakter">
                        </div>
                        <div>
                            <label class="form-label text-xs font-bold uppercase opacity-60">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" class="form-input w-full mt-1" placeholder="Ulangi password">
                        </div>
                    </div>
                    
                    <div class="bg-orange-50 dark:bg-orange-900/10 border-l-4 border-orange-400 p-3 mt-4">
                        <p class="text-xs text-orange-700 dark:text-orange-300 italic">
                            Kosongkan jika tidak ingin mengganti password.
                        </p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-secondary btn-sm px-6">Update Password</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<style>
    /* Tambahan agar form selaras dengan gaya Merchant Page */
    .form-input {
        height: 38px !important;
        font-size: 14px !important;
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
    }
    .dark .form-input {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }
    .btn-sm {
        font-size: 12px !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>