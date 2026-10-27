# Proyek Individu: Responsive Landing Page (Warung Makan Hanisa)

Repositori ini dikembangkan untuk memenuhi penugasan **Praktikum Pemrograman Web (OBE)**. Proyek ini berfokus pada pembuatan halaman *landing page* yang responsif, aksesibel, dan terstruktur dengan baik untuk UMKM lokal "Warung Makan Hanisa".

## 🛠️ Tech Stack & Fitur
* **HTML5 Semantic Elements**: Menggunakan struktur tag yang bermakna (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`).
* **CSS3 Flexbox & Grid**: Digunakan untuk tata letak navigasi yang fleksibel serta penataan *card* menu makanan/minuman yang simetris.
* **Custom Properties & Reset**: Menggunakan variabel CSS (`:root`) untuk pengelolaan warna dan spasi yang konsisten serta pembersihan margin bawaan browser.
* **Media Query & Mobile-First**: Pendekatan desain yang berfokus pada kenyamanan layar kecil hingga besar (diuji pada 320px, 768px, 1024px, dan desktop).
* **Accessibility (A11y)**: Dilengkapi dengan *skip link* dan *focus-visible outline* untuk navigasi keyboard yang optimal.

## 📱 Dokumentasi Pengujian Tampilan (Screenshots)
* **Mobile (320px)**: Berhasil diuji, tata letak menyusun ke bawah tanpa *overflow*.
* **Tablet (768px)**: Kolom grid beradaptasi menyesuaikan lebar layar.
* **Desktop / 1024px+**: Tampilan utama rata tengah dengan tata letak grid menu yang rapi menyamping.
* **Focus State**: Indikator aksesibilitas keyboard aktif dengan *outline* jelas pada elemen interaktif.

## 🚀 Cara Menjalankan Proyek (Lokal)
1. Pastikan **Laragon** aktif di direktori lokal Anda (Drive D) dengan Apache & MySQL berjalan.
2. Letakkan folder proyek di `D:\laragon\www\pemweb-obe\`.
3. Buka browser dan akses URL: `http://localhost/pemweb-obe/` (atau langsung login kasir di `http://localhost/pemweb-obe/login.php` dengan akun kasir: `kasir` / `kasir123`).

---

## 📝 Latihan Praktikum A & B: Form Aksesibel & Validasi Client-Side Kasir

Implementasi form modal pembayaran dan validasi client-side diterapkan langsung pada sistem kasir POS Warung Hanisa (`kasir/index.php`, `assets/js/app.js`, `assets/css/style.css`, `kasir/proses.php`).

### 1. Tujuan Form
Mengumpulkan data transaksi penjualan secara aman, jelas, dan aksesibel sebelum pesanan dikirim ke pemrosesan backend, memastikan data pemesan dan nominal uang benar serta menghindari kesalahan pencatatan transaksi kasir.

### 2. Rincian Field Form
1. **Tanggal Transaksi** (`#tanggal`): Tanggal pelaksanaan pesanan kasir (`required`, default hari ini).
2. **Nama Pemesan** (`#inputNamaPemesan`): Nama pelanggan atau pemesan makanan/minuman (`required`, min. 3 karakter).
3. **Kontak Pemesan** (`#inputKontakPemesan`): Nomor kontak / WhatsApp pemesan (opsional, format 9–15 digit angka).
4. **Ringkasan & Total Pembayaran** (`#modalTotalTagihan`, `#modalItemsSummary`, `#inputJumlahPesanan`): Data agregat nominal tagihan dan jumlah item pesanan yang diambil dari keranjang belanja kasir.
5. **Metode Pembayaran** (`#metodeCash`, `#metodeQris`): Pilihan metode pembayaran `Tunai (Cash)` atau `QRIS Digital` menggunakan `<fieldset>` dan `<legend>`.
6. **Nominal Uang Diterima** (`#inputNominalCash`): Field angka untuk pembayaran Tunai dilengkapi tombol cepat (+20rb, +50rb, 100rb, Uang Pas) serta kalkulasi kembalian/kekurangan otomatis realtime.
7. **Catatan Pesanan** (`#inputCatatanPesanan`): Keterangan khusus pesanan pelanggan opsional (misal: bungkus, kuah dipisah, pedas, dsb.).
8. **Checkbox Konfirmasi** (`#inputKonfirmasiPesanan`): Checkbox persetujuan bahwa data pesanan dan rincian pembayaran sudah benar sebelum diproses.

### 3. Aspek Aksesibilitas (WCAG & WAI-ARIA)
* **Label Terhubung**: Setiap input memiliki elemen `<label>` dengan atribut `for` yang identik dengan atribut `id` input.
* **aria-describedby**: Menghubungkan setiap kontrol form dengan elemen pesan errornya (`<small id="...-error" class="error-message" role="alert"></small>`).
* **Error Summary Terstruktur**: Elemen `#form-errors` di bagian paling atas form dengan atribut `role="alert"` dan `aria-live="polite"` yang merangkum jumlah dan daftar kesalahan input secara deskriptif.
* **Indikator Visual Kesalahan**: Field bermasalah diberi kelas `.is-invalid` dan atribut `aria-invalid="true"` dengan batas garis merah kontras serta highlight fokus.
* **Auto-Focus Keyboard**: Saat validasi gagal, fokus keyboard langsung diarahkan secara otomatis ke field tidak valid pertama.
* **Real-Time Error Clearing**: Saat pengguna mengetik atau mengubah input yang salah, pesan error dan status invalid langsung dibersihkan seketika.

### 4. Aturan Validasi (Client-Side)
1. **Keranjang Tidak Kosong**: Keranjang pesanan harus memiliki minimal 1 item produk (`jumlah >= 1`).
2. **Nama Pemesan Wajib**: Nama pemesan wajib diisi dan memiliki panjang minimal 3 karakter.
3. **Format Kontak Valid**: Jika nomor kontak diisi, harus berformat nomor telepon valid (9–15 digit angka).
4. **Tanggal Valid**: Tanggal transaksi wajib diisi dan format kalender valid.
5. **Metode Pembayaran Valid**: Metode wajib dipilih (`cash` atau `qris`).
6. **Nominal Tunai Cukup**: Jika memilih metode Tunai, nominal uang diterima wajib diisi angka valid dan tidak boleh kurang dari total tagihan. Jika memilih QRIS, input uang tunai tidak diwajibkan.
7. **Batas Panjang Catatan**: Catatan pesanan maksimal 255 karakter.
8. **Konfirmasi Wajib Dicentang**: Checkbox konfirmasi pesanan wajib dicentang sebelum form dapat disubmit.

### 5. Panduan Pengujian Manual di Localhost
Buka browser dan login ke `http://localhost/pemweb-obe/kasir/index.php`.

* **Skenario A — Validasi Gagal**:
  1. Klik tombol **Bayar Sekarang** di panel kanan saat keranjang kosong -> periksa validasi keranjang belanja kosong.
  2. Tambahkan 1 menu (misal Mie Ayam). Klik tombol **Bayar Sekarang**.
  3. Kosongkan Nama Pemesan, jangan centang konfirmasi, lalu klik **Bayar Sekarang** di modal.
  4. Periksa ringkasan kesalahan (*Error Summary*) di atas modal, pesan error di dekat Nama Pemesan, Nominal Uang, dan Checkbox Konfirmasi, serta keyboard fokus otomatis ke *Nama Pemesan*.
  5. Masukkan 2 huruf saja pada Nama Pemesan (misal "AB") -> submit -> verifikasi pesan error "Nama pemesan minimal 3 karakter.".
  6. Masukkan nominal uang yang kurang dari total tagihan (misal total Rp20.000, masukkan Rp10.000) -> verifikasi muncul pesan kekurangan uang.

* **Skenario B — Validasi Berhasil & Proses Transaksi**:
  1. Isi **Nama Pemesan**: `Fidiani` (atau nama pemesan valid).
  2. Isi **Kontak Pemesan**: `081234567890`.
  3. Klik tombol **Uang Pas** atau masukkan nominal tunai yang mencukupi (misal Rp50.000).
  4. Centang checkbox konfirmasi pesanan.
  5. Pastikan semua pesan error dan penanda merah menghilang secara realtime.
  6. Klik tombol **Bayar Sekarang**.
  7. Transaksi diproses satu kali tanpa duplikasi, dan modal **Transaksi Berhasil** tampil memuat nomor pesanan, nama pemesan, rincian pembayaran, serta kembalian.
  8. Uji alur **QRIS Digital**: Pada transaksi berikutnya, pilih metode QRIS, isi nama pemesan, centang konfirmasi, dan klik konfirmasi bayar tanpa perlu mengisi uang tunai.

  ## Form dan Validasi — Pertemuan 6

### Tujuan Form
Form pada Sistem Informasi Manajemen Penjualan dan Sistem Kasir Berbasis Web pada Warung Makan Hanisa digunakan untuk mendukung proses pemesanan dan pembayaran. Form terintegrasi dengan halaman kasir yang sudah ada sehingga pengguna dapat melakukan transaksi melalui satu sistem.

### Catatan Aksesibilitas
Penerapan aksesibilitas pada form meliputi:
- **Label dan input:** Menggunakan elemen `label` yang dihubungkan dengan input melalui atribut `for` dan `id`.
- **Input wajib:** Menggunakan atribut `required` pada data yang wajib diisi.
- **Pesan kesalahan:** Menggunakan elemen pesan kesalahan yang terhubung dengan input melalui atribut `aria-describedby`.
- **Validasi masukan:** Memeriksa data yang dimasukkan sebelum proses pembayaran dilanjutkan.
- **Umpan balik pengguna:** Menampilkan pesan kesalahan agar pengguna mengetahui data yang perlu diperbaiki.

### Pengujian Form
Pengujian dilakukan menggunakan dua skenario, yaitu data tidak valid dan data valid. Pengujian data tidak valid digunakan untuk memastikan sistem menampilkan pesan kesalahan, sedangkan pengujian data valid digunakan untuk memastikan form dapat melanjutkan proses sesuai aturan yang diterapkan.

---

## ⚡ Mini Dashboard Berbasis API — Pertemuan 7 (Praktikum & Tugas OBE)

Pada Pertemuan 7, sistem informasi Warung Makan Hanisa dikembangkan dengan menambahkan fitur **Mini Dashboard Real-Time Berbasis API** yang terintegrasi langsung pada `dashboard.php` dan didukung arsitektur pengambilan data secara asinkron (*Asynchronous JavaScript*).

Fitur ini memanfaatkan data riil dari sistem (jumlah menu, total transaksi, total penjualan, informasi stok, dan transaksi terbaru) serta mengimplementasikan standar modern:
* `fetch()` API
* `async/await`
* `try/catch` error handling
* Pemeriksaan integritas status HTTP melalui `response.ok`
* Siklus antarmuka lengkap: **Loading State**, **Error State** (dengan tombol *Coba Lagi*), **Success State**, dan **Empty State**
* Fitur **Pencarian Real-Time** dan **Filter Multi-Kategori / Filter Status Stok**
* Tampilan responsif berbasis tema warna khas Warung Hanisa (*Warm Cafe Palette*)

---

### 1. Dokumentasi Endpoint API

Fitur ini menggunakan endpoint backend PHP fungsional yang terhubung ke basis data MySQL `warung_hanisa`:

| Komponen | Rincian |
| :--- | :--- |
| **URL Endpoint Utama** | `http://localhost/pemweb-obe/api/dashboard.php` |
| **URL Endpoint Cadangan (JSON)** | `http://localhost/pemweb-obe/api/dashboard.json` |
| **Metode HTTP** | `GET` |
| **Format Header Respons** | `Content-Type: application/json; charset=UTF-8` |
| **Status Respons Berhasil** | `200 OK` |
| **Status Respons Gagal** | `500 Internal Server Error` |

#### Parameter Query (Opsional)
Endpoint mendukung parameter query dinamis untuk kebutuhan pencarian, pemfilteran di sisi server, maupun pengujian penanganan kesalahan:
1. `search`: String pencarian nama menu atau kategori (contoh: `?search=mie` atau `?search=bakso`).
2. `kategori`: ID atau nama kategori menu (contoh: `?kategori=1` atau `?kategori=Minuman`).
3. `status_stok`: Filter kondisi stok (`aman`, `menipis`, `habis`).
4. `simulate_error`: Bernilai `1` untuk mensimulasikan kegagalan server guna menguji *Error State* dan fungsi tombol *Coba Lagi* (Latihan 3).

#### Contoh Struktur Respons JSON (`200 OK`)
```json
{
  "status": "success",
  "message": "Data dashboard berhasil dimuat",
  "timestamp": "2026-10-10 21:26:28",
  "data": {
    "ringkasan": {
      "total_penjualan": 60000,
      "penjualan_hari_ini": 0,
      "total_transaksi": 2,
      "transaksi_hari_ini": 0,
      "total_produk": 8,
      "total_stok": 597,
      "produk_stok_menipis": 0,
      "produk_habis": 0,
      "total_kategori": 2
    },
    "kategori": [
      { "id_kategori": 1, "nama_kategori": "Makanan", "jumlah_produk": 4 },
      { "id_kategori": 2, "nama_kategori": "Minuman", "jumlah_produk": 4 }
    ],
    "produk": [
      {
        "id_produk": 1,
        "nama_produk": "Mie Ayam",
        "id_kategori": 1,
        "nama_kategori": "Makanan",
        "harga": 20000,
        "stok": 49,
        "status_stok": "aman",
        "opsi_suhu": "tidak_berlaku",
        "gambar": "mie-ayam-biasa.jpg"
      }
    ],
    "transaksi_terbaru": [
      {
        "id_pesanan": 2,
        "nomor_pesanan": "TRX-20261009-001",
        "tanggal": "2026-10-09 01:16:46",
        "total": 20000,
        "status": "selesai",
        "nama_kasir": "Kasir Hanisa",
        "metode_pembayaran": "cash"
      }
    ]
  }
}
```

#### Pemetaan Field ke Antarmuka Website
* `data.ringkasan.total_penjualan` & `penjualan_hari_ini` ➔ **Kartu Metrik 1**: Total Omzet Penjualan & Omzet Hari Ini (Format Rupiah).
* `data.ringkasan.total_transaksi` & `transaksi_hari_ini` ➔ **Kartu Metrik 2**: Total Transaksi Keseluruhan & Transaksi Hari Ini.
* `data.ringkasan.total_produk` & `total_stok` ➔ **Kartu Metrik 3**: Total Menu Aktif & Total Porsi Stok Tersedia.
* `data.ringkasan.produk_stok_menipis` & `produk_habis` ➔ **Kartu Metrik 4**: Peringatan Menu yang Memerlukan Restock.
* `data.produk[]` (`nama_produk`, `nama_kategori`, `harga`, `stok`, `status_stok`, `opsi_suhu`, `gambar`) ➔ **Tabel Monitoring Produk & Stok Real-Time** lengkap dengan thumbnail gambar, harga format Rupiah, dan indikator badge status (`✓ Aman`, `⚠️ Menipis`, `✕ Habis`).

---

### 2. Implementasi Asinkron & Status Tampilan (Latihan 2 & 3)

Logika JavaScript (`assets/js/app.js`) mengelola alur komunikasi asinkron sebagai berikut:

1. **Loading State (`#miniDashLoading`)**:
   Saat permintaan API dikirimkan, antarmuka menampilkan animasi spinner berputar dengan teks *"Memuat data dashboard..."* dan badge status *"⏳ Memuat data..."*. Kontainer data dan error disembunyikan.
2. **Success State (`#miniDashContent`)**:
   Jika request berhasil (`response.ok === true` dan `status === 'success'`), kontainer loading disembunyikan, badge status berubah menjadi hijau *"🟢 API Terhubung (200 OK)"*, data metrik diisi, dan tabel produk dirender.
3. **Fitur Pencarian & Filter Interaktif**:
   - **Pencarian Real-Time (`#miniDashSearchInput`)**: Mengetik kata kunci langsung memfilter baris produk tanpa memuat ulang halaman.
   - **Filter Kategori (`#miniDashCategoryFilter`)**: Memilih kategori (Semua, Makanan, Minuman) langsung memfilter menu yang ditampilkan.
   - **Filter Status Stok (`#miniDashStockFilter`)**: Memilih status (Semua, Aman >10, Menipis 1–10, Habis 0).
4. **Empty State (`#miniDashEmpty`)**:
   Jika pencarian atau kombinasi filter tidak menghasilkan produk, ditampilkan pesan informatif *"Tidak ada data menu yang sesuai dengan filter atau pencarian"*.
5. **Error State & Tombol Coba Lagi (`#miniDashError`, `#btnRetryDashboard`)**:
   Jika koneksi gagal, server mengembalikan status non-200, atau server sengaja disimulasikan error, blok `try/catch` menangkap kesalahan. Antarmuka menampilkan kotak peringatan merah dengan penjelasan pesan error dan tombol **"🔄 Coba Lagi"** yang mengeksekusi ulang fungsi `fetchDashboardData()`. Tersedia pula tombol **"↩️ Pulihkan Endpoint Normal"** untuk keluar dari mode pengujian kegagalan.

---

### 3. Cara Menjalankan & Menguji di Localhost

1. **Jalankan Laragon**:
   Pastikan modul **Apache** dan **MySQL** aktif pada Laragon di Drive `D:\laragon`.
2. **Akses Dashboard**:
   - Buka browser ke alamat `http://localhost/pemweb-obe/login.php`.
   - Masuk menggunakan akun Administrator:
     * **Username**: `admin`
     * **Password**: `admin123`
   - Setelah login, browser otomatis diarahkan ke `http://localhost/pemweb-obe/dashboard.php`.
3. **Uji Pengambilan Data Asinkron**:
   - Perhatikan bagian **⚡ Mini Dashboard Real-Time Warung Hanisa**.
   - Amati indikator badge status *"🟢 API Terhubung (200 OK)"* dan pembaruan 4 kartu serta tabel produk.
4. **Uji Pencarian & Filter**:
   - Ketik `mie` pada kolom pencarian: periksa bahwa hanya *Mie Ayam* dan *Mie Ayam Bakso* yang tampil.
   - Pilih kategori `Minuman`: periksa bahwa hanya menu minuman yang muncul.
   - Pilih filter stok `Stok Aman (> 10)`: periksa filter bekerja akurat.
5. **Uji Error State & Tombol Coba Lagi (Latihan 3)**:
   - Klik tombol **"⚠️ Uji Error State"** di kanan atas mini dashboard.
   - Permintaan akan dikirimkan ke endpoint dengan parameter `?simulate_error=1` (menghasilkan status HTTP 500).
   - Verifikasi antarmuka langsung menampilkan kotak merah *"Gagal Memuat Data Dashboard"* dan badge status menjadi *"🔴 Gagal Terhubung"*.
   - Klik tombol **"↩️ Pulihkan Endpoint Normal"** atau **"🔄 Coba Lagi"** setelah memulihkan: data berhasil diambil kembali dan tampilan normal dipulihkan seketika.
6. **Verifikasi Fitur Kasir & POS Tetap Berjalan**:
   - Klik tombol **"🛒 Buka Kasir POS"** di header dashboard.
   - Tambahkan menu ke keranjang dan lakukan simulasi checkout kasir untuk memastikan seluruh fungsi lama tetap berjalan 100% normal tanpa kendala.

---

### 4. Skenario Screenshot untuk Laporan Praktikum Pertemuan 7

| No | Skenario Screenshot | Keterangan & Tujuan |
| :---: | :--- | :--- |
| **1** | **Akses Endpoint JSON Langsung** | Buka URL `http://localhost/pemweb-obe/api/dashboard.php` pada tab browser untuk memperlihatkan struktur respons JSON asli berstatus 200 OK. |
| **2** | **Tampilan Mini Dashboard (Data State)** | Screenshot tampilan bagian *⚡ Mini Dashboard Real-Time Warung Hanisa* pada `dashboard.php` memperlihatkan 4 kartu metrik, status hijau `🟢 API Terhubung (200 OK)`, dan tabel monitoring stok. |
| **3** | **Pengujian Pencarian Real-Time** | Screenshot hasil pencarian saat kolom pencarian diisi kata kunci `mie`, memperlihatkan baris tabel terfilter dinamis. |
| **4** | **Pengujian Filter Kategori** | Screenshot saat dropdown kategori dipilih `Minuman`, memperlihatkan daftar menu minuman terfilter. |
| **5** | **Pengujian Error State & Tombol Coba Lagi** | Screenshot saat tombol *⚠️ Uji Error State* diklik, memperlihatkan pesan kesalahan merah dan tombol *🔄 Coba Lagi*. |
| **6** | **Pemulihan Sukses setelah Retry** | Screenshot saat tombol *↩️ Pulihkan Endpoint Normal* diklik, memperlihatkan data kembali berhasil dimuat normal. |
| **7** | **Integritas Halaman Kasir POS** | Screenshot halaman `http://localhost/pemweb-obe/kasir/index.php` untuk membuktikan fitur kasir POS tetap aktif dan tidak mengalami regresi. |

### Dokumentasi Mini Dashboard API — Pertemuan 7

Mini Dashboard pada sistem Warung Hanisa digunakan untuk menampilkan ringkasan penjualan, jumlah transaksi, informasi produk, dan kondisi stok secara terintegrasi dengan data sistem.

| Komponen | Keterangan |
|---|---|
| Endpoint utama | `api/dashboard.php` |
| Metode permintaan | GET |
| Format respons | JSON |
| Data cadangan | `api/dashboard.json` |
| Fungsi JavaScript | `syncDashboardWithAPI()` |
| Penanganan kegagalan | Menggunakan mekanisme fallback ke file JSON lokal apabila pengambilan data dari API gagal |

**Cara menjalankan:** Aktifkan Apache dan MySQL melalui Laragon, kemudian buka `http://localhost/pemweb-obe/dashboard.php` pada browser.

**Pengujian:** Periksa tampilan data dashboard, fitur pencarian dan filter, kondisi loading, serta penanganan error. Dokumentasikan hasil pengujian menggunakan screenshot halaman dan Console atau Network pada Developer Tools.

Pada pengerjaan OBE Pertemuan 7, saya mengembangkan fitur Mini Dashboard Berbasis API pada sistem kasir Warung Hanisa. Fitur ini digunakan untuk menampilkan ringkasan omzet penjualan, jumlah transaksi, informasi produk, dan kondisi stok menu. Data diambil menggunakan Fetch API dengan pendekatan asynchronous melalui async/await.

Saya mempelajari penggunaan fetch(), pemeriksaan status respons HTTP, pengolahan data JSON, serta penanganan kesalahan menggunakan try/catch. Saya juga memahami pentingnya menampilkan kondisi loading dan error agar pengguna mengetahui status pemuatan data. Selain itu, fitur pencarian dan filter membantu pengguna menemukan produk berdasarkan kriteria tertentu.

Melalui pengujian menggunakan browser dan tab Network pada Developer Tools, saya dapat memeriksa permintaan data serta status respons endpoint. Pengerjaan ini membantu saya memahami hubungan antara API, JavaScript, dan tampilan antarmuka pada aplikasi web.

---

## 🚀 Tugas OBE 9: Back-End PHP dan REST API (Warung Makan Hanisa)

### 1. Nama dan Tujuan Proyek
* **Nama Proyek:** Sistem Informasi Manajemen Penjualan, Kasir, dan REST API Warung Makan Hanisa
* **Tujuan Proyek:** Mengembangkan arsitektur backend berbasis PHP dan REST API yang terstruktur, aman, dan konsisten untuk mendukung sistem penjualan kasir UMKM Warung Makan Hanisa. API ini menyediakan endpoint pemeriksaan kesehatan server (`health.php`), manajemen data alat (`alats.php`), direktori endpoint (`index.php`), dan metrik dashboard (`dashboard.php`).

### 2. Struktur File Backend dan Endpoint
```
pemweb-obe/
├── api/
│   ├── index.php         # Endpoint direktori & daftar rute API (GET -> 200, Method lain -> 405)
│   ├── health.php        # Endpoint pemeriksaan kesehatan sistem (GET -> 200 {"status":"ok"}, Method lain -> 405)
│   ├── alats.php         # Endpoint sumber daya data alat (GET -> 200, POST -> 201/400/415/422, Method lain -> 405)
│   ├── alat.php          # Alias rute kompatibilitas singular ke alats.php
│   ├── dashboard.php     # Endpoint metrik dashboard kasir terintegrasi DB (GET -> 200/500, Method lain -> 405)
│   └── dashboard.json    # Data cadangan/mock lokal dashboard
├── config/
│   └── database.php      # Koneksi PDO MySQL & parser environment variabel (.env)
├── .env.example          # Template konfigurasi environment yang aman untuk dilacak Git
├── .gitignore            # Aturan pengabaian file sensitif (.env, vendor/, composer.phar)
├── composer.json         # Konfigurasi metadata proyek Composer
└── composer.lock         # File pengunci dependensi Composer
```

### 3. Persyaratan Versi PHP & Cara Memeriksa
* **Persyaratan Tugas:** PHP 8.4.27.
* **PHP CLI:** PHP 8.4.27 telah dipasang di `D:\laragon\bin\php\php-8.4.27`. Periksa versi CLI secara langsung di PowerShell:
  ```powershell
  & "D:\laragon\bin\php\php-8.4.27\php.exe" -v
  ```
  Perintah `php -v` hanya menunjukkan versi PHP yang ditemukan melalui `PATH`, yang mungkin berbeda dari versi pada path di atas.
* **PHP Apache:** Versi PHP CLI dan Apache dapat berbeda, sehingga keduanya perlu diperiksa secara terpisah. Untuk mengatur versi di Laragon 5, pilih **Menu > PHP > Version > php-8.4.27**, lalu restart Apache. Untuk memeriksa PHP yang benar-benar digunakan Apache, buat sementara file PHP di folder proyek yang berisi `<?php phpinfo();`, buka file tersebut melalui `http://localhost/pemweb-obe/`, lalu periksa nilai **PHP Version**. Hapus file pemeriksaan setelah selesai dan jangan membiarkan halaman `phpinfo()` dapat diakses publik. Pemilihan versi dari menu Laragon saja belum membuktikan versi aktif Apache.

#### Cara Menjalankan Aplikasi dengan Laragon 5

1. Pastikan Laragon 5 terpasang dan folder proyek berada di `D:\laragon\www\pemweb-obe`.
2. Buka Laragon, lalu pilih **Start All** atau jalankan **Apache** melalui kontrol Laragon. Pastikan Apache aktif sebelum membuka aplikasi.
3. Aktifkan **MySQL** jika fitur yang digunakan memerlukan database. Aplikasi utama memuat konfigurasi koneksi database, sehingga MySQL dan database proyek perlu tersedia untuk membuka alur aplikasi utama. Endpoint yang tidak menggunakan database, seperti health check, tidak memerlukan MySQL.
4. Buka `http://localhost/pemweb-obe/` di browser.
5. Jika aplikasi tidak dapat dibuka, periksa bahwa folder tersebut berisi file proyek seperti `index.php`, status Apache di Laragon, URL dan port yang digunakan, serta log Apache dari menu Laragon. Jika muncul pesan kegagalan koneksi database, periksa status MySQL, keberadaan database, dan nilai koneksi pada `.env`. Periksa juga versi PHP Apache melalui langkah di atas; versi PHP CLI yang benar tidak otomatis berarti Apache menggunakan versi yang sama.

### 4. Manajemen Dependensi & Penggunaan Composer
* Proyek menggunakan fungsi PHP dan ekstensi yang tersedia di lingkungan PHP, tanpa package pihak ketiga yang terdaftar saat ini. `composer.json` berisi metadata proyek dan `composer.lock` tidak mencantumkan package atau package development.
* Jika perintah `composer` belum tersedia di `PATH`, jalankan `composer.phar` melalui PHP CLI 8.4.27 dari root proyek:
  ```powershell
  & "D:\laragon\bin\php\php-8.4.27\php.exe" .\composer.phar --version
  & "D:\laragon\bin\php\php-8.4.27\php.exe" .\composer.phar validate
  & "D:\laragon\bin\php\php-8.4.27\php.exe" .\composer.phar install
  ```
  Perintah `validate` memeriksa metadata Composer; `install` memasang dependency yang tercantum pada manifest dan lock file. Karena saat ini tidak ada package terdaftar, tidak perlu menambahkan dependency hanya untuk menjalankan aplikasi.
* **Keamanan Repositori:** Direktori `vendor/`, file biner `composer.phar`, serta `composer-setup.php` telah dimasukkan ke dalam `.gitignore` sehingga tidak akan diikutsertakan ke dalam repository Git.

### 5. Konfigurasi Environment (.env dan .env.example)

File `.env` menyimpan konfigurasi lokal aplikasi, seperti pengaturan koneksi database. File `.env.example` adalah templat untuk membuat `.env`; templat ini boleh disertakan di repositori dan harus tetap bebas dari password maupun rahasia asli. Buat `.env` dari direktori root proyek menggunakan PowerShell:
```powershell
Copy-Item .env.example .env
```

Berikut isi variabel yang saat ini tersedia pada `.env.example`:
```ini
APP_NAME="Warung Makan Hanisa"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/pemweb-obe

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=warung_hanisa
DB_USER=root
DB_PASS=
```

Untuk konfigurasi koneksi database, `config/database.php` membaca `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASS`. Nilai fallback jika variabel terkait tidak tersedia adalah `localhost`, `warung_hanisa`, `root`, dan string kosong. Variabel `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `DB_CONNECTION`, dan `DB_PORT` ada pada templat, tetapi tidak dibaca oleh konfigurasi koneksi database tersebut. Sesuaikan nilai lokal yang relevan; jangan memasukkan kredensial asli ke `.env.example`.

Jangan unggah `.env` ke Git karena dapat berisi konfigurasi lokal atau rahasia. Berdasarkan `.gitignore`, `.env` dan pola `.env.*` diabaikan, sementara pengecualian `!.env.example` memungkinkan templat tetap disertakan di repositori. Periksa aturan pengabaian `.env` dengan:
```powershell
git check-ignore -v .env
```
Jika cocok dengan aturan, perintah menampilkan file `.gitignore` dan baris yang mengabaikan `.env`.

`config/database.php` memuat `.env` secara langsung tanpa dependency eksternal.

### 6. Dokumentasi Spesifikasi Endpoint REST API

Semua endpoint mengirim respons JSON. `api/index.php`, `api/health.php`, dan `api/alats.php` mengatur header `Content-Type: application/json; charset=utf-8`; `api/dashboard.php` mengatur `application/json; charset=UTF-8` (perbedaan kapitalisasi nilai charset tidak mengubah encoding).

| No | Endpoint | Metode HTTP | Request | Status yang ditangani kode | Fungsi |
| :---: | :--- | :---: | :--- | :--- | :--- |
| **1** | `/api/index.php` | `GET` | Tanpa body | `200 OK`; metode lain `405 Method Not Allowed` (`Allow: GET`) | Menampilkan status direktori dan daftar endpoint API. |
| **2** | `/api/health.php` | `GET` | Tanpa body | `200 OK`; metode lain `405 Method Not Allowed` (`Allow: GET`) | Memeriksa status API dan mengembalikan `{"status":"ok"}`. |
| **3** | `/api/alats.php` | `GET` | Tanpa body | `200 OK`; metode selain `GET`/`POST` `405 Method Not Allowed` (`Allow: GET, POST`) | Mengembalikan daftar data contoh alat. |
| **4** | `/api/alats.php` | `POST` | `Content-Type: application/json`; body JSON object | `201 Created`; kesalahan format/body `400 Bad Request`; tipe konten selain JSON `415 Unsupported Media Type`; validasi field gagal `422 Unprocessable Entity`; metode lain `405 Method Not Allowed` (`Allow: GET, POST`) | Memeriksa dan menerima data alat. Implementasi saat ini mengembalikan data yang diterima; endpoint ini belum menyimpannya ke database. |
| **5** | `/api/alat.php` | `GET`, `POST` | Sama dengan `/api/alats.php` | Meneruskan ke `/api/alats.php`, sehingga status dan validasinya sama | Alias kompatibilitas singular. |
| **6** | `/api/dashboard.php` | `GET` | Tanpa body; parameter query opsional | `200 OK`; metode lain `405 Method Not Allowed` (`Allow: GET`); kegagalan server/database atau `simulate_error=1` `500 Internal Server Error` | Mengambil data agregat dashboard dari database. |

`/api/index.php` mencantumkan endpoint indeks, health, alats, dan dashboard. `/api/alat.php` tersedia sebagai alias di source, tetapi tidak dicantumkan oleh indeks API.

#### Validasi `POST /api/alats.php`

* Header media type harus `application/json` (parameter seperti `charset=utf-8` diperbolehkan).
* Body harus JSON valid berbentuk object. Body kosong, sintaks JSON rusak, atau root selain object ditolak dengan `400`.
* `nama` wajib ada, bukan `null`, berupa string, tidak boleh kosong setelah trim, dan maksimal 100 karakter setelah trim.
* `jumlah` wajib ada, bukan `null`, berupa integer JSON (bukan string atau desimal), dan tidak boleh negatif.
* Data valid menghasilkan `201`; kegagalan validasi field menghasilkan `422` dan daftar alasan di properti `errors`.

---

### 7. Panduan Pengujian Thunder Client / Browser & Contoh Request/Response

#### Cara menjalankan pengujian

1. Pastikan Laragon dan Apache berjalan, lalu gunakan base URL `http://localhost/pemweb-obe`.
2. Di **Thunder Client**, buat request baru, pilih metode dan URL sesuai skenario di bawah. Untuk `POST`, pilih body JSON atau `Raw` dengan format JSON dan atur `Content-Type: application/json`.
3. Kirim request dan periksa status HTTP, body JSON, serta header `Content-Type` pada hasil respons.
4. Di browser, buka URL endpoint `GET` untuk melihat respons langsung. Untuk memeriksa request yang dibuat halaman web, buka Developer Tools (`F12`) → **Network**, muat ulang halaman, pilih request API, lalu periksa **Headers**, **Status Code**, dan **Response**. Untuk mengirim `POST` atau `DELETE`, gunakan Thunder Client; tab Network dipakai untuk mengamati request yang berasal dari browser.

#### A. Endpoint `api/health.php` (Latihan 1)
* **Skenario 1.1: Request GET (Normal)**
  * **Method:** `GET` | **URL:** `http://localhost/pemweb-obe/api/health.php`
  * **Status:** `200 OK` | **Content-Type:** `application/json; charset=utf-8`
  * **Response Body:**
    ```json
    {"status":"ok"}
    ```
* **Skenario 1.2: Method Selain GET (POST / DELETE)**
  * **Method:** `POST` | **URL:** `http://localhost/pemweb-obe/api/health.php`
  * **Status yang diharapkan dari kode:** `405 Method Not Allowed` | **Header:** `Allow: GET`. Hasil pengujian skenario ini tidak termasuk hasil uji yang tersedia.
  * **Response Body:**
    ```json
    {
        "status": "error",
        "message": "Metode HTTP tidak diizinkan. Hanya metode GET yang diperbolehkan."
    }
    ```

#### B. Endpoint `api/alats.php` (Latihan 2 & 3)
* **Skenario 2.1: Request GET (Daftar Alat)**
  * **Method:** `GET` | **URL:** `http://localhost/pemweb-obe/api/alats.php`
  * **Status:** `200 OK` | **Content-Type:** `application/json; charset=utf-8`
  * **Response Body:**
    ```json
    {
        "data": [
            { "id": 1, "nama": "Router", "jumlah": 4 },
            { "id": 2, "nama": "Multimeter", "jumlah": 6 }
        ]
    }
    ```

* **Skenario 2.2: POST Valid Standar (Nama ≤ 100 karakter)**
  * **Method:** `POST` | **URL:** `http://localhost/pemweb-obe/api/alats.php`
  * **Headers:** `Content-Type: application/json`
  * **Body (JSON):**
    ```json
    {
        "nama": "Access Point Ubiquiti",
        "jumlah": 3
    }
    ```
  * **Status:** `201 Created`
  * **Catatan:** Respons menyatakan data diterima. Implementasi belum melakukan penyimpanan permanen.
  * **Response Body:**
    ```json
    {
        "status": "success",
        "message": "Data diterima",
        "data": {
            "nama": "Access Point Ubiquiti",
            "jumlah": 3
        }
    }
    ```

* **Skenario 2.3: POST Batas Valid (Nama Tepat 100 Karakter)**
  * **Method:** `POST` | **URL:** `http://localhost/pemweb-obe/api/alats.php`
  * **Headers:** `Content-Type: application/json`
  * **Body (JSON):**
    ```json
    {
        "nama": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA",
        "jumlah": 5
    }
    ```
  * **Status yang diharapkan dari validasi kode:** `201 Created` jika field lain juga valid. Hasil pengujian untuk kasus batas tepat 100 karakter tidak termasuk hasil uji yang tersedia.

* **Skenario 2.4: POST Melebihi Batas (Nama > 100 Karakter / 101 Karakter)**
  * **Method:** `POST` | **URL:** `http://localhost/pemweb-obe/api/alats.php`
  * **Headers:** `Content-Type: application/json`
  * **Body (JSON):**
    ```json
    {
        "nama": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA",
        "jumlah": 5
    }
    ```
  * **Status:** `422 Unprocessable Entity`
  * **Response Body:**
    ```json
    {
        "status": "error",
        "message": "Validasi gagal",
        "errors": [
            "Field nama tidak boleh lebih dari 100 karakter (maksimal 100 karakter)."
        ]
    }
    ```

* **Skenario 2.5: POST Tanpa Header Content-Type JSON**
  * **Method:** `POST` | **URL:** `http://localhost/pemweb-obe/api/alats.php`
  * **Headers:** `Content-Type: text/plain` (atau tanpa header)
  * **Status yang diharapkan dari kode:** `415 Unsupported Media Type`. Hasil pengujian untuk skenario ini tidak termasuk hasil uji yang tersedia.
  * **Response Body:**
    ```json
    {
        "status": "error",
        "message": "Content-Type harus application/json."
    }
    ```

* **Skenario 2.6: POST Format JSON Rusak**
  * **Method:** `POST` | **URL:** `http://localhost/pemweb-obe/api/alats.php`
  * **Headers:** `Content-Type: application/json`
  * **Body:** `{nama: rusak`
  * **Status:** `400 Bad Request`
  * **Response Body:**
    ```json
    {
        "status": "error",
        "message": "Format JSON tidak valid atau rusak"
    }
    ```

* **Skenario 2.7: Request DELETE (Latihan 3 — Penolakan Metode Tidak Didukung)**
  * **Method:** `DELETE` | **URL:** `http://localhost/pemweb-obe/api/alats.php`
  * **Status:** `405 Method Not Allowed` | **Header:** `Allow: GET, POST`, `Content-Type: application/json; charset=utf-8`
  * **Response Body:**
    ```json
    {
        "status": "error",
        "message": "Metode HTTP tidak diizinkan. Hanya metode GET dan POST yang diperbolehkan."
    }
    ```

#### Hasil pengujian yang tersedia

Hasil berikut adalah pengujian yang telah tersedia; hasil tersebut tidak menyatakan skenario lain sudah dijalankan:

| Request | Hasil |
| :--- | :--- |
| `GET /api/health.php` | `200 OK` |
| `GET /api/alats.php` | `200 OK` |
| `POST /api/alats.php` dengan data valid | `201 Created` |
| `POST /api/alats.php` dengan JSON rusak | `400 Bad Request` |
| `POST /api/alats.php` dengan `nama` lebih dari 100 karakter | `422 Unprocessable Entity` |
| `DELETE /api/alats.php` | `405 Method Not Allowed` |
| Header respons endpoint API yang diuji | `Content-Type: application/json; charset=utf-8` |

Untuk pengujian data valid, gunakan contoh body pada Skenario 2.2. Untuk JSON rusak, gunakan body `{nama: rusak` pada Skenario 2.6. Untuk validasi panjang nama, kirim string `nama` dengan lebih dari 100 karakter dan `jumlah` integer nonnegatif. Bentuk lengkap respons error ditentukan oleh source code dan ditampilkan pada skenario terkait; jangan menyimpulkan hasil pengujian dari skenario yang belum dijalankan.

---

### 8. Rekomendasi Skenario Screenshot Laporan Tugas OBE 9

Tabel berikut mempertahankan dokumentasi screenshot sebagai rekomendasi pengumpulan bukti, bukan pernyataan bahwa semua skenario telah diuji. Status yang tersedia dirangkum pada tabel hasil pengujian di atas; ambil screenshot tambahan hanya setelah skenario terkait benar-benar dijalankan.

| No | Skenario Pengujian / Screenshot | Deskripsi & Caption yang Direkomendasikan |
| :---: | :--- | :--- |
| **1** | **GET `api/health.php` (Browser / Thunder Client)** | Bukti hasil uji yang tersedia: respons `{"status":"ok"}`, status HTTP `200 OK`, dan header `application/json; charset=utf-8`. |
| **2** | **POST `api/health.php` (Method selain GET)** | Jika diuji, screenshot respons kode `405 Method Not Allowed` dengan header `Allow: GET`; hasilnya belum termasuk daftar pengujian tersedia. |
| **3** | **GET `api/alats.php` (Daftar Alat)** | Bukti hasil uji yang tersedia: respons daftar data alat dalam format JSON dengan status `200 OK`. |
| **4** | **POST `api/alats.php` Valid (Nama ≤ 100 char)** | Bukti hasil uji yang tersedia: request data valid menghasilkan status `201 Created`. |
| **5** | **POST `api/alats.php` Batas (Nama Tepat 100 char)** | Jika diuji, dokumentasikan hasil batas panjang; kode menerima nama hingga 100 karakter, tetapi hasil uji kasus tepat 100 karakter belum tersedia. |
| **6** | **POST `api/alats.php` Gagal (Nama > 100 char)** | Bukti hasil uji yang tersedia: validasi nama lebih dari 100 karakter menghasilkan `422 Unprocessable Entity`. |
| **7** | **POST `api/alats.php` Salah Content-Type** | Jika diuji, kode mengembalikan `415 Unsupported Media Type` untuk Content-Type selain JSON; hasil uji ini belum tersedia. |
| **8** | **POST `api/alats.php` JSON Rusak** | Bukti hasil uji yang tersedia: body JSON rusak menghasilkan `400 Bad Request`. |
| **9** | **DELETE `api/alats.php` (Latihan 3)** | Bukti hasil uji yang tersedia: method `DELETE` menghasilkan `405 Method Not Allowed` dengan header `Allow: GET, POST`. |
| **10** | **Validasi Composer & Git Status** | Screenshot terminal saat menjalankan `composer validate` dan `git status`; dokumentasikan output aktual tanpa menyatakan lolos jika belum diverifikasi. |