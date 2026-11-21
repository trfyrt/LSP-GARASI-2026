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
| `app/Enums` | Enum tipe/status (UserRole, GarageType, VehicleType, dst.) |
| `app/Models` | User, Garage, Vehicle, Rental, RentalExtension |
| `app/Services` | Logika bisnis (kalkulasi harga, generate ID garasi, validasi) — Checkpoint 2–4 |
| `database/migrations` | Skema tabel |
| `docs/diagram` | `usecase.puml`, `class.puml`, `erd.puml` |

## Progres
- [x] Checkpoint 1 — Perancangan sistem & basis data
