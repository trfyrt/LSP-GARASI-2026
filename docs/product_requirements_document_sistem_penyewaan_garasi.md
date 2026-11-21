# Product Requirements Document (PRD)

## Sistem Informasi Penyewaan Garasi Kendaraan

### 1. Ringkasan Proyek (Project Overview)

**Nama Proyek:** Aplikasi Penyewaan Garasi Kendaraan

**Platform:** Web atau Mobile dengan Basis Data Relasional

**Tujuan:** Membangun aplikasi manajemen penyewaan garasi kendaraan (sepeda/motor, mobil bensin, dan mobil listrik) yang mendukung proses pendaftaran penyewa, pemesanan garasi, perpanjangan sewa, manajemen inventaris garasi, serta pelaporan pendapatan untuk memenuhi standar uji kompetensi Pemrogram.

### 2. Aktor & Hak Akses (User Roles)

1. **Admin:** Pengelola sistem yang bertugas membuat data garasi, memverifikasi pendaftaran penyewa baru, memproses perpanjangan sewa, dan melihat laporan penyewaan.

2. **Penyewa:** Pelanggan yang mendaftar ke sistem, menyewa garasi sesuai jenis kendaraan, dan mengunggah bukti pembayaran.

### 3. Aturan Bisnis Utama (Core Business Rules)

* **Durasi Sewa:** Harian (maksimal 30 hari) atau Bulanan.

  * Sewa harian max bulan saat ini -1. Contohnya bulan November max 29 jika harian karena 30 hari -1. Jika sewa di bulan Februari max 27 karena 28 hari -1.

  * Sewa bulanan mengikuti jumlah hari dalam bulan saat disewa

* **Kalkulasi Harga Sewa:**

  * Harga sewa harian ditentukan oleh Admin per tipe garasi.

  * Harga sewa bulanan = $25 \times \text{Harga Sewa Harian}$.

* **Tipe Garasi & Kompatibilitas Kendaraan:**

  * **Garasi Sepeda (`S<3 digit>`):** Hanya untuk Sepeda atau Sepeda Motor.

  * **Garasi Mobil (`C<3 digit>`):** Untuk Mobil Bensin atau Mobil Listrik.

  * **Garasi Mobil Listrik (`E<3 digit>`):** Khusus untuk Mobil Listrik.

* **Aturan Pengisian Daya (Charging):**

  * Mobil listrik yang disewa dan ditempatkan di **Garasi Mobil Listrik** wajib mengisi daya hingga penuh.

  * Biaya isi listrik = $\text{Kapasitas Listrik Kendaraan (kWh)} \times \text{Harga Isi Listrik per kWh}$.

* **Batasan Sewa:** Satu penyewa dapat menyewa lebih dari satu garasi secara bersamaan.

  * 1 Garasi hanya bisa 1 kendaraan

### 4. Spesifikasi Fitur Fungsional (Functional Requirements)

#### A. Modul Autentikasi & Registrasi

* **Registrasi Penyewa:**

  * Input: NIK (Nomor KTP), Nama Lengkap, Nomor Telepon, Email (sebagai username), dan Password.

  * Status awal akun adalah `Pending` hingga diverifikasi oleh Admin.

* **Login:** Autentikasi berbasis Email/Username dan Password untuk Admin dan Penyewa (hanya penyewa berstatus `Approved` yang dapat menyewa).

#### B. Modul Admin

1. **Manajemen Garasi (Batch Creation):**

   * Admin dapat menambahkan garasi secara berkelompok berdasarkan tipe (Sepeda, Mobil, Mobil Listrik).

   * Input: Tipe Garasi, Jumlah Garasi (maksimal 999 per tipe), Harga Sewa Harian, dan Harga Isi Listrik per kWh (khusus tipe Mobil Listrik).

   * Sistem men-generate ID Garasi secara otomatis dan *incremental* per tipe (contoh: `S001`, `C010`, `E005`S untuk Sepeda, C untuk Mobil, E untuk Mobil listrik).

2. **Verifikasi Pendaftar Baru:**

   * Menampilkan daftar calon penyewa baru beserta data NIK/KTP.

   * Admin dapat melakukan aksi **Terima (Approve)** atau **Tolak (Reject)**.

3. **Perpanjang Status Garasi:**

   * Menampilkan daftar garasi yang sedang aktif disewa (`used`) pada hari berjalan.

   * Admin memilih garasi dan memperbarui **Tanggal Selesai Sewa**.

   * Sistem menghitung otomatis sisa biaya tambahan yang harus dibayarkan berdasarkan selisih durasi baru.

   * Admin mengunggah bukti pembayaran tambahan dan menyimpan perubahan.

4. **Laporan Penyewaan Garasi:**

   * Rekapitulasi dan filter laporan berdasarkan:

     * Per bulan dalam tahun tertentu.

     * Per hari dalam bulan tertentu.

     * Per Tipe Garasi.

     * Per Penyewa.

#### C. Modul Penyewa

1. **Sewa Baru Garasi:**

   * Input Detail Kendaraan: Tipe Kendaraan (Sepeda, Sepeda Motor, Mobil, Mobil Listrik), Nomor Plat (wajib kecuali Sepeda), Merek & Tipe, serta Kapasitas Listrik dalam kWh (khusus Mobil Listrik).

   * Input Detail Sewa: Tipe Sewa (Harian / Bulanan), Jumlah Hari.

     * Sewa harian max bulan saat ini -1. Contohnya bulan November max 29 jika harian karena 30 hari -1. Jika sewa di bulan Februari max 27 karena 28 hari -1.

     * Sewa bulanan mengikuti jumlah hari dalam bulan saat disewa

   * Tanggal Mulai Sewa otomatis terisi tanggal hari ini; Tanggal Selesai Sewa dihitung otomatis.

   * Pilihan Tipe Garasi difilter otomatis sesuai kompatibilitas kendaraan.

   * Menampilkan daftar garasi yang tersedia (`available`) pada tanggal hari ini berdasarkan tipe yang dipilih.

   * Sistem menghitung dan menampilkan **Total Biaya Sewa** (termasuk biaya listrik jika mobil listrik masuk ke garasi mobil listrik).

   * Penyewa mengunggah **Bukti Pelunasan Biaya** (gambar).

   * Setelah disimpan, status garasi berubah menjadi `used`.

### 5. Kebutuhan Non-Fungsional & Teknis

* **Arsitektur & Kode:** Menggunakan paradigma Pemrograman Berorientasi Objek (OOP) dan Pemrograman Terstruktur sesuai *coding guidelines* & *best practices*, lengkap dengan dokumentasi kode (komentar/docblock).

* **Basis Data:** Basis data relasional (misal: MySQL/PostgreSQL/SQLite) dengan relasi yang ternormalisasi.

* **Library:** Memanfaatkan *library* atau komponen *pre-existing* yang relevan (untuk UI, ORM/koneksi DB, upload file, atau testing).

* **Dokumentasi Desain:** Use-Case Diagram, Class Diagram, dan Entity-Relationship Diagram (ERD).

* **Pengujian:** Skenario dan eksekusi *Unit Testing* pada fungsi-fungsi inti (kalkulasi harga, generate ID garasi, validasi sewa).

### 6. Checkpoint Pengerjaan Proyek (40 Jam)

#### Checkpoint 1: Perancangan Sistem & Basis Data (Jam ke-1 s.d. ke-5)

* **Target:** Menyelesaikan analisis kebutuhan dan desain arsitektur (dilakukan pada 1 jam pertama di hadapan asesor serta dilanjutkan mandiri).

* **Deliverables:**

  * Use-Case Diagram, Class Diagram, dan ERD.

  * Inisialisasi repositori GitHub dan struktur proyek.

  * Pembuatan skema tabel pada basis data relasional.

#### Checkpoint 2: Pengembangan Core Backend & Modul Admin (Jam ke-6 s.d. ke-18)

* **Target:** Implementasi autentikasi dan seluruh fitur Admin (Laporan Progres 1: Sabtu, 21 Nov 2026 pukul 17:00 WIB).

* **Deliverables:**

  * Fitur Login & Registrasi Penyewa.

  * Fitur Verifikasi Pendaftar Baru oleh Admin.

  * Fitur Pembuatan Garasi (Batch & Auto-increment ID `S001`, `C001`, `E001`).

#### Checkpoint 3: Pengembangan Modul Penyewa & Perpanjangan Sewa (Jam ke-19 s.d. ke-30)

* **Target:** Menyelesaikan alur transaksi utama penyewaan dan perpanjangan (Laporan Progres 2: Minggu, 22 Nov 2026 pukul 08:00 WIB).

* **Deliverables:**

  * Fitur Sewa Baru Garasi (filter kendaraan-garasi, kalkulasi otomatis harga harian/bulanan & listrik, upload bukti bayar, update status `used`).

  * Fitur Perpanjang Status Garasi oleh Admin (update tanggal selesai, kalkulasi sisa biaya, upload bukti bayar).

#### Checkpoint 4: Pelaporan, Unit Testing & Dokumentasi (Jam ke-31 s.d. ke-38)

* **Target:** Penyelesaian fitur laporan, pengujian kode, dan penyusunan laporan akhir.

* **Deliverables:**

  * Fitur Laporan Penyewaan (4 filter utama).

  * Penulisan dan eksekusi *Unit Testing* untuk logika bisnis krusial.

  * Dokumentasi kode program dan penyusunan dokumen laporan (diagram, penjelasan library, screenshot fitur, link GitHub).

#### Checkpoint 5: Final Review & Persiapan Demo (Jam ke-39 s.d. ke-40)

* **Target:** Simulasi presentasi dan memastikan aplikasi berjalan tanpa *bug*.

* **Deliverables:**

  * Pengujian *end-to-end* seluruh skenario penggunaan.

  * Persiapan skenario demo aplikasi berdurasi maksimal 10 menit untuk asesor.

### 7. Aturan Penggunaan Asisten AI (Claude)

* **Dilarang push otomatis:** Claude **tidak boleh** menjalankan `git push` (termasuk `--force`, `--tags`, atau push ke branch/remote mana pun) atas inisiatif sendiri. Push hanya dilakukan oleh pengembang secara manual.

  * Commit lokal hanya dibuat bila pengembang memintanya secara eksplisit.

  * Claude tidak boleh membuat atau menggabungkan Pull Request, membuat tag/rilis, maupun mengubah remote repositori tanpa diminta.

  * Izin push pada satu permintaan tidak berlaku untuk permintaan berikutnya; setiap push memerlukan instruksi baru.

* **Alasan:** Repositori GitHub adalah bukti pengerjaan yang dinilai asesor, sehingga setiap perubahan yang dipublikasikan harus sudah ditinjau dan disetujui pengembang.
