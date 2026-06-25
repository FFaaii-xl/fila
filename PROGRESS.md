# Citroroso v3 - Project Progress

## 📅 Tanggal Update
**18 Juni 2026**

## 🎯 Overview
Citroroso v3 adalah project manajemen pasar dengan Laravel 13 + Filament 5.6.7. Project ini menggunakan database existing `u674851158_tenongan_prod` tanpa migration/schema change.

## ✅ Yang Sudah Dibuat

### 1. Tech Stack
- **Laravel** 13.x
- **Filament** 5.6.7
- **PHP** 8.3+
- **Database** MySQL (existing)

### 2. Models (app/Models/)
| Model | Table | Fungsi |
|-------|-------|--------|
| `Pedagang.php` | `pedagang` | Data pedagang pasar |
| `Produsen.php` | `produsen` | Data produsen/supplier |
| `Produk.php` | `produk` | Master produk |
| `Penjualan.php` | `penjualan` | Transaksi penjualan |
| `Saldo.php` | `saldo` | Saldo pedagang/produsen |
| `Transaksi.php` | `transaksi` | Riwayat transaksi |
| `Account.php` | `account` | Akun kas |
| `User.php` | `users2` | User login (AUTH) |
| `DraftPenjualan.php` | `draft_penjualan` | Draft penjualan |

### 3. Filament Resources (app/Filament/Resources/)
| Resource | Navigation Group | Fungsi |
|----------|------------------|--------|
| `PedagangResource` | Data Master | CRUD Pedagang |
| `ProdusenResource` | Data Master | CRUD Produsen |
| `ProdukResource` | Data Master | CRUD Produk |
| `PenjualanResource` | Operasional | CRUD Penjualan |
| `DetailKasResource` | Operasional | Kas Harian |
| `PembulatanResource` | Sistem | Pengaturan Pembulatan |
| `AccountResource` | Sistem | Manajemen Akun |
| `UserResource` | Sistem | Manajemen User |

### 4. Custom Pages (app/Filament/Pages/)
| Page | Fungsi |
|------|--------|
| `MerchantSalesPage` | Laporan penjualan pedagang (PROUP, KAS, Tabungan) |
| `ProducerSalesPage` | Laporan penjualan produsen |
| `MutasiHarianPage` | Mutasi transaksi harian |
| `CatatanSetoranPage` | Catatan setoran bulanan |
| `Dashboard.php` | Dashboard utama (role-based) |
| `Login.php` | Custom login |

### 5. Multi-Panel Setup (app/Providers/Filament/)
| Panel | Path | Color | Fungsi |
|-------|------|-------|--------|
| `AdminPanelProvider` | `/admin` | Emerald | Admin/Pengurus full access |
| `PedagangPanelProvider` | `/pedagang` | Emerald | Panel Pedagang |
| `ProdusenPanelProvider` | `/produsen` | Blue | Panel Produsen |

### 6. Services (app/Services/)
| Service | Fungsi |
|---------|--------|
| `SettingsService.php` | Konfigurasi via JSON (KAS tiered) |
| `UserAutoCreationService.php` | Auto-create user untuk Pedagang/Produsen |

### 7. Traits (app/Traits/)
| Trait | Fungsi |
|-------|--------|
| `MerchantFinancialRules.php` | PROUP, KAS, Tabungan calculations |
| `UppercaseAttributes.php` | Uppercase display attributes |

### 8. Observers (app/Observers/)
| Observer | Fungsi |
|----------|--------|
| `OwnerObserver.php` | General owner operations |
| `PedagangObserver.php` | Auto-create user saat Pedagang dibuat |
| `ProdusenObserver.php` | Auto-create user saat Produsen dibuat |
| `PenjualanObserver.php` | Transaction operations |

### 9. User Auto-Creation System
- Saat Pedagang/Produsen dibuat → auto create user di `users2`
- Username: nama di-slug (contoh: `slamet_wais_khair`)
- Email: `{username}@citro.fun`
- Password: random 12 chars
- Admin mendapat notification dengan password sementara

### 10. User Management (UserResource)
- CRUD user untuk Admin
- Owner dropdown: `Nama (Pedagang)` / `Nama (Produsen)`
- Reset Password action
- Filter by Owner Type

### 12. Custom Login Page (Citroroso Theme)
- **View**: `resources/views/filament/auth/login.blade.php`
- **Features**: Emerald gradient, dark mode, glass morphism, password toggle

### 11. Mobile-First UI (Filament v5)
- **Plugin**: `hammadzafar05/mobile-bottom-nav`
- **Pedagang Panel**: Bottom Nav (Beranda, Penjualan, Mutasi, Akun)
- **Produsen Panel**: Bottom Nav (Beranda, Produk, Riwayat, Akun)
- **Design**: ADAPTIVE - compact & informative (HP + Desktop)
- **Reference**: `.clinerules` for full UI/UX guidelines

## 🔗 Reference Project
- **Project Lama**: `D:\laragon\www\moons` (MoonShine v4)
- **Project Baru**: `D:\laragon\www\fila` (Filament v5)

## 📌 Current Phase
**Admin Dashboard** - Fitting to match MoonShine v2 dashboard functionality at `D:\laragon\www\moons`

## 🚀 Cara Running

```bash
# 1. Masuk ke directory project
cd d:/laragon/www/fila

# 2. Dump autoload
composer dump-autoload

# 3. Jalankan server
php artisan serve

# 4. Buka browser
http://127.0.0.1:8000/admin
```

## 📋 TODO List - Selesai

### Authentication & User Management
- [x] User Registration (Auto-create via Observer)
- [x] Password Reset functionality
- [x] UserResource (CRUD)

### Phase 1-6 Complete
- [x] Master Data Resources
- [x] Operational Resources
- [x] Core Dashboards
- [x] Reports & Tools

## 📊 Database Schema
Menggunakan database existing - **ZERO MIGRATION** policy.

```
users2 (id, name, username, email, password, owner_type, owner_id)
pedagang (id, nama, gender, tabungan_rate, tabungan)
produsen (id, nama, gender, bundle_ke, tabungan_rate, tabungan)
produk (id, nama, harga_beli, harga_jual, stok, produsen_id)
penjualan (id, pedagang_id, produsen_id, produk_id, tanggal, titip, laku, sisa_jual, retur, modal, jual, status)
draft_penjualan (id, pedagang_id, produk_id, tanggal, titip, laku, sisa_jual, retur, modal, jual)
saldo (id, owner_type, owner_id, jumlah)
transaksi (id, owner_type, owner_id, tanggal, jumlah, kemarin, pembulatan, kas, keterangan)
account (id, nama, jenis, saldo)
```

## 🎨 Design Theme
- **Primary Color**: Emerald (#10b981)
- **Typography**: Sans-serif (Tailwind default)

## 📞 Support
Build with ❤️ using Filament v5
