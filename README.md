# LSP-2026 - Garage Management System

Aplikasi web untuk mengelola penyewaan garasi sepeda/motor, mobil bensin, dan mobil listrik.

- **Admin:** membuat garasi batch, memverifikasi pendaftar, memperpanjang sewa, melihat laporan.
- **Penyewa:** mendaftar, menyewa garasi sesuai kendaraan, dan mengunggah bukti pembayaran.

Stack: PHP 8.3, Laravel, MySQL 8, Blade + Tailwind CSS.

## Setup
```bash
composer install
cp .env.example .env        # sesuaikan DB_* bila perlu
php artisan key:generate
# buat database: CREATE DATABASE garasi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
php artisan serve
```
Aplikasi berjalan di http://127.0.0.1:8000.
Akun admin: `admin@garage.test` / `12345678`.

## Pengujian
```bash
php artisan test
```

Versi zoom ini memungkinkan untuk book in advance sesuai dengan pembahasan pada zoom 21 November 2026
