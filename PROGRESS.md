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
| `PenjualanResource` | Transaksi | CRUD Penjualan |
| `DraftPenjualanResource` | Transaksi | CRUD Draft Penjualan |

### 4. Custom Pages (app/Filament/Pages/)
| Page | Fungsi |
|------|--------|
| `MerchantSalesPage` | Laporan penjualan pedagang (PROUP, KAS, Tabungan) |
| `ProducerSalesPage` | Laporan penjualan produsen |
| `MutasiHarianPage` | Mutasi transaksi harian |
| `CatatanSetoranPage` | Catatan setoran bulanan |
| `Dashboard.php` | Dashboard utama (role-based) |
| `Login.php` | Custom login (username + password) |

### 5. Multi-Panel Setup (app/Providers/Filament/)
| Panel | Path | Color | Fungsi |
|-------|------|-------|--------|
| `AdminPanelProvider` | `/admin` | Emerald | Admin/Pengurus full access |
| `PedagangPanelProvider` | `/pedagang` | Emerald | Panel Pedagang |
| `ProdusenPanelProvider` | `/produsen` | Blue | Panel Produsen |

### 6. Services & Traits
| File | Fungsi |
|------|--------|
| `SettingsService.php` | Konfigurasi via JSON (KAS tiered) |
| `MerchantFinancialRules.php` | PROUP calculation, KAS tiered |
| `MerchantFinancialTraits.php` | Uppercase display attributes |
| `helpers.php` | alignUang(), formatTanggal() |

### 7. Views (resources/views/filament/pages/)
- `merchant-sales.blade.php` - Laporan penjualan pedagang
- `producer-sales.blade.php` - Laporan penjualan produsen
- `mutasi-harian.blade.php` - Mutasi harian
- `catatan-setoran.blade.php` - Catatan setoran

### 8. Authentication
- **Table**: `users2`
- **Login Field**: `username` (bukan email)
- **Password**: Hash Bcrypt

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

## 📋 Fitur dari Moons (Selesai)
- [x] DraftPenjualanResource + Model
- [x] MerchantSalesPage (PROUP + KAS + Tabungan)
- [x] ProducerSalesPage
- [x] MutasiHarianPage
- [x] CatatanSetoranPage
- [x] SettingsService
- [x] MerchantFinancialRules Trait

## 📋 TODO List

### High Priority
- [ ] User Registration
- [ ] Password Reset functionality
- [ ] Role-based access control (RBAC)
- [ ] Dashboard widgets yang lebih lengkap

### Medium Priority
- [ ] TransaksiResource (CRUD Transaksi)
- [ ] AccountResource (Manajemen Kas)
- [ ] SaldoResource (Riwayat Saldo)
- [ ] UploadPenjualanPage

### Low Priority
- [ ] Export/Import Excel
- [ ] Print Nota
- [ ] Laporan Harian/Mingguan/Bulanan
- [ ] Notifikasi Email/SMS
- [ ] AI Integration (dari moons project)

## 🔗 Reference Project
- **Project Lama**: `D:\laragon\www\moons` (MoonShine)
- **Project Baru**: `D:\laragon\www\fila` (Filament)

## 📊 Database Schema
Menggunakan database existing - **ZERO MIGRATION** policy.

```
users2 (id, name, username, password, owner_type, owner_id)
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
- **Admin Theme**: Dark glass morphism
- **Typography**: Sans-serif (Tailwind default)

## 📞 Support
Build with ❤️ using Filament v5
