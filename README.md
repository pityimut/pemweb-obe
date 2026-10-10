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