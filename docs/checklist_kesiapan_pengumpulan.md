# Checklist Kesiapan Pengumpulan

Proyek: Sistem Informasi Penyewaan Garasi Kendaraan (LSP-2026)
Acuan: [PRD](product_requirements_document_sistem_penyewaan_garasi.md)

Centang `[x]` setelah item diverifikasi. Jangan mengumpulkan sebelum semua item **Wajib** tercentang.

---

## 1. Repositori GitHub

- [ ] Repositori dapat diakses asesor (public, atau asesor sudah di-invite)
- [ ] Semua perubahan sudah di-commit dan di-push ke `main` (`git status` bersih)
- [ ] Riwayat commit rapi dan mencerminkan checkpoint 1–5
- [x] `.env` **tidak** ter-commit; `.env.example` lengkap dan terbaru
- [x] Tidak ada file sampah / kredensial / folder `vendor/` dan `node_modules/` di repo
- [x] `README.md` berisi deskripsi, stack, langkah setup, akun demo, dan progres terbaru
- [ ] Tag/rilis final dibuat (opsional, mis. `v1.0.0`)

## 2. Setup & Menjalankan Aplikasi (uji di mesin bersih)

- [ ] `composer install` berhasil tanpa error
- [ ] `cp .env.example .env` + `php artisan key:generate` berhasil
- [ ] `php artisan migrate --seed` berhasil dari database kosong _(sudah lolos di SQLite; MySQL belum diuji)_
- [ ] `php artisan storage:link` sudah tercantum di README dan berjalan (untuk bukti bayar)
- [ ] `npm install && npm run build` berhasil (jika aset front-end dipakai)
- [ ] `php artisan serve` menampilkan aplikasi tanpa error
- [ ] Akun admin seeder berfungsi (`admin@garasi.test`) dan password sudah didokumentasikan
- [ ] Tidak ada error/warning di `storage/logs/laravel.log` setelah alur utama dijalankan

## 3. Fitur Fungsional (sesuai PRD)

### A. Autentikasi & Registrasi
- [ ] Registrasi penyewa: NIK, nama, telepon, email, password
- [ ] Status awal akun `Pending`
- [ ] Login Admin dan Penyewa berhasil
- [ ] Penyewa berstatus `Pending`/`Rejected` tidak dapat menyewa
- [ ] Validasi input (email unik, NIK format valid, password minimal) berjalan
- [ ] Logout dan proteksi halaman per role (Admin vs Penyewa)

### B. Modul Admin
- [ ] Buat garasi batch per tipe (Sepeda / Mobil / Mobil Listrik), maksimal 999 per tipe
- [ ] ID garasi otomatis & incremental per tipe (`S001`, `C001`, `E001`), lanjut dari nomor terakhir
- [ ] Harga sewa harian per tipe; harga isi listrik per kWh khusus tipe Mobil Listrik
- [ ] Verifikasi pendaftar: daftar + data NIK, aksi Terima / Tolak
- [ ] Perpanjang sewa: daftar garasi `used` hari ini, ubah tanggal selesai
- [ ] Perpanjang sewa: sisa biaya tambahan terhitung otomatis
- [ ] Perpanjang sewa: upload bukti pembayaran tambahan
- [ ] Laporan: filter per bulan (dalam tahun tertentu)
- [ ] Laporan: filter per hari (dalam bulan tertentu)
- [ ] Laporan: filter per tipe garasi
- [ ] Laporan: filter per penyewa

### C. Modul Penyewa
- [ ] Form sewa baru: tipe kendaraan, plat nomor, merek & tipe, kapasitas kWh (mobil listrik)
- [ ] Plat nomor wajib kecuali Sepeda
- [ ] Tipe garasi terfilter sesuai kompatibilitas kendaraan
- [ ] Daftar garasi `available` hari ini sesuai tipe terpilih
- [ ] Tanggal mulai otomatis hari ini; tanggal selesai terhitung otomatis
- [ ] Total biaya sewa tampil (termasuk biaya listrik bila relevan)
- [ ] Upload bukti pelunasan (gambar) dengan validasi tipe & ukuran
- [ ] Setelah disimpan, status garasi menjadi `used`
- [ ] Penyewa dapat menyewa lebih dari satu garasi sekaligus

## 4. Aturan Bisnis (verifikasi manual dengan angka nyata)

- [ ] Sewa harian maksimal = jumlah hari bulan berjalan − 1 (Nov = 29, Feb = 27 / 28 untuk kabisat sesuai kalkulasi)
- [ ] Sewa bulanan mengikuti jumlah hari bulan saat disewa
- [ ] Harga bulanan = 25 × harga harian
- [ ] Garasi Sepeda (`S`) hanya untuk Sepeda / Sepeda Motor
- [ ] Garasi Mobil (`C`) untuk Mobil Bensin / Mobil Listrik
- [ ] Garasi Mobil Listrik (`E`) khusus Mobil Listrik
- [ ] Biaya listrik = kWh kendaraan × harga per kWh (hanya mobil listrik di garasi `E`)
- [ ] 1 garasi hanya 1 kendaraan; garasi `used` tidak bisa disewa ulang
- [ ] Garasi kembali `available` setelah masa sewa berakhir (sesuai desain)

## 5. Kualitas Kode

- [ ] Menerapkan OOP (Model, Enum, Service) dan struktur terpisah (controller tipis, logika di `app/Services`)
- [x] Mengikuti coding guideline (PSR-12 / Laravel Pint) — `./vendor/bin/pint --test` lolos
- [ ] Docblock/komentar pada kelas dan method penting
- [x] Tidak ada kode mati, `dd()`, `dump()`, atau `TODO` tersisa
- [ ] Validasi memakai Form Request / rules, bukan di view
- [x] Relasi Eloquent & foreign key sesuai ERD

## 6. Unit Testing

- [ ] Test kalkulasi harga (harian, bulanan, listrik)
- [ ] Test generate ID garasi (urutan, padding 3 digit, per tipe, batas 999)
- [ ] Test validasi sewa (maks hari, kompatibilitas kendaraan–garasi, plat wajib)
- [ ] `php artisan test` hijau seluruhnya
- [x] Contoh test bawaan (`ExampleTest`) dibersihkan atau tetap lolos
- [ ] Skenario pengujian terdokumentasi (tabel input → hasil diharapkan) di laporan

## 7. Basis Data

- [x] Skema ternormalisasi dan sesuai ERD V2 (`docs/diagram/Entitty Relational DIagram V2.png`)
- [ ] Migrasi dapat dijalankan ulang (`php artisan migrate:fresh --seed`) _(sudah lolos di SQLite; MySQL belum diuji)_
- [x] Index dan constraint (unique, foreign key) terpasang
- [ ] Dump SQL / ekspor skema disertakan bila diminta asesor (opsional)

## 8. Dokumentasi Desain

- [x] Use-Case Diagram (`docs/diagram/Use Case Diagram.png`) sesuai fitur akhir
- [ ] Class Diagram (`docs/diagram/class.puml`) sesuai kode akhir
- [ ] ERD (`docs/diagram/Entitty Relational DIagram V2.png`) sesuai skema akhir _(perbaikan 1:1 `user`–`renter` dan garis rental–invoice belum dicek)_
- [x] Diagram diekspor ke PNG/PDF untuk laporan

## 9. Laporan Akhir (Dokumen)

- [ ] Halaman judul: nama, proyek, tanggal
- [ ] Use-Case, Class Diagram, dan ERD tercantum
- [ ] Penjelasan library/komponen yang dipakai (Laravel, ORM Eloquent, library upload, PHPUnit, dst.) beserta alasannya
- [ ] Screenshot seluruh fitur (registrasi, login, verifikasi, buat garasi, sewa baru, perpanjang, laporan 4 filter)
- [ ] Skenario & hasil unit testing (termasuk screenshot `php artisan test`)
- [ ] Link repositori GitHub tercantum dan dapat dibuka
- [ ] Format dokumen final (PDF) dan nama file sesuai ketentuan asesor

## 10. Persiapan Demo (maks. 10 menit)

- [ ] Skrip demo tertulis dengan urutan: registrasi → verifikasi admin → buat garasi → sewa baru → perpanjang → laporan
- [ ] Data demo siap (`migrate:fresh --seed` + akun admin & penyewa contoh)
- [ ] Alur end-to-end sudah dilatih minimal 2× dengan waktu < 10 menit
- [ ] Skenario edge case disiapkan (mobil listrik di garasi `E`, sewa harian batas maksimal, garasi penuh)
- [ ] Backup: rekaman layar / screenshot bila aplikasi bermasalah saat demo
- [ ] Koneksi, XAMPP/MySQL, dan browser sudah dites di perangkat demo
- [ ] Siap menjawab pertanyaan tentang kode, desain, dan keputusan teknis

## 11. Tenggat & Pengiriman

| Milestone | Tenggat | Status |
|---|---|---|
| Laporan Progres 1 (Checkpoint 2: login, registrasi, verifikasi, batch garasi) | Sabtu, 21 Nov 2026 17:00 WIB | [ ] |
| Laporan Progres 2 (Checkpoint 3: sewa baru, perpanjangan) | Minggu, 22 Nov 2026 08:00 WIB | [ ] |
| Checkpoint 4: laporan penyewaan, unit test, dokumentasi | Jam ke-31 s.d. 38 | [ ] |
| Checkpoint 5: final review & demo | Jam ke-39 s.d. 40 | [ ] |

- [ ] Commit terakhir dilakukan sebelum tenggat
- [ ] Link repo + laporan dikirim ke asesor sesuai kanal yang ditentukan
- [ ] Konfirmasi penerimaan dari asesor diterima

---

## Ringkasan Kesiapan

| Area | Status |
|---|---|
| Repositori & setup | ☐ |
| Fitur fungsional | ☐ |
| Aturan bisnis | ☐ |
| Kualitas kode & test | ☐ |
| Dokumentasi & laporan | ☐ |
| Demo | ☐ |

**Keputusan akhir:** ☐ Siap dikumpulkan  ☐ Belum siap
