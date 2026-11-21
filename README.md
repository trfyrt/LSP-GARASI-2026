# LSP-2026 — Sistem Penyewaan Garasi Kendaraan

Aplikasi web manajemen penyewaan garasi (sepeda/motor, mobil bensin, mobil listrik) untuk uji kompetensi Pemrogram.
PRD: [`docs/product_requirements_document_sistem_penyewaan_garasi.md`](docs/product_requirements_document_sistem_penyewaan_garasi.md)

## Stack
- PHP 8.3 + Laravel
- MySQL 8
- Diagram: PlantUML (`docs/diagram/`)

## Setup
```bash
composer install
cp .env.example .env        # sesuaikan DB_* bila perlu
php artisan key:generate
# buat database: CREATE DATABASE garasi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
php artisan migrate --seed
php artisan serve
```
Akun admin awal: `admin@garasi.test` / `admin12345` (ganti setelah login pertama).

## Struktur
| Path | Isi |
|---|---|
| `app/Enums` | Enum tipe/status (UserRole, RenterStatus, GarageType, VehicleType, InvoiceType, dst.) |
| `app/Models` | User, Renter, Garage, Vehicle, Rental, Invoice |
| `app/Services` | Logika bisnis (kalkulasi harga, generate ID garasi, validasi) — Checkpoint 2–4 |
| `database/migrations` | Skema tabel sesuai ERD V2 (`user`, `renter`, `garage`, `vehicle`, `rental`, `invoice`) |
| `docs/diagram` | Use Case Diagram, ERD V2 (PNG), dan `class.puml` |
| `docs/checklist_kesiapan_pengumpulan.md` | Checklist kesiapan pengumpulan |

## Skema Basis Data (ringkas)
- `user` — akun login (Admin/Penyewa). Admin hanya punya baris di tabel ini.
- `renter` — profil penyewa (NIK, telepon, status verifikasi), relasi 1:1 ke `user`.
- `garage` — garasi (kode `S###`/`C###`/`E###`, harga harian, harga isi listrik).
- `vehicle` — kendaraan milik penyewa.
- `rental` — kontrak sewa yang menghubungkan penyewa, garasi, dan kendaraan.
- `invoice` — tagihan per periode (tanggal, biaya, bukti bayar). Perpanjangan = invoice baru bertipe `extension` untuk garasi yang sama.

## Progres
- [x] Checkpoint 1 — Perancangan sistem & basis data
- [ ] Checkpoint 2 — Login, registrasi, verifikasi pendaftar, pembuatan garasi batch
- [ ] Checkpoint 3 — Sewa baru dan perpanjangan sewa
- [ ] Checkpoint 4 — Laporan, unit testing, dokumentasi
- [ ] Checkpoint 5 — Final review dan persiapan demo
