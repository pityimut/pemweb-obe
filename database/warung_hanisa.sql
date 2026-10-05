-- ==========================================================
-- Database SQL: warung_hanisa
-- Aplikasi: Sistem Informasi Manajemen Penjualan & Kasir
-- UMKM: Warung Makan Hanisa
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `warung_hanisa` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `warung_hanisa`;

-- ----------------------------------------------------------
-- 1. TABEL USERS
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `pemasukan`;
DROP TABLE IF EXISTS `pembayaran`;
DROP TABLE IF EXISTS `detail_pesanan`;
DROP TABLE IF EXISTS `pesanan`;
DROP TABLE IF EXISTS `pengeluaran`;
DROP TABLE IF EXISTS `kategori_pengeluaran`;
DROP TABLE IF EXISTS `produk`;
DROP TABLE IF EXISTS `kategori`;
DROP TABLE IF EXISTS `pengaturan`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id_user` INT PRIMARY KEY AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','kasir') NOT NULL,
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. TABEL KATEGORI
-- ----------------------------------------------------------
CREATE TABLE `kategori` (
  `id_kategori` INT PRIMARY KEY AUTO_INCREMENT,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `deskripsi` TEXT NULL,
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  INDEX `idx_kategori_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. TABEL PRODUK
-- ----------------------------------------------------------
CREATE TABLE `produk` (
  `id_produk` INT PRIMARY KEY AUTO_INCREMENT,
  `id_kategori` INT NOT NULL,
  `nama_produk` VARCHAR(100) NOT NULL,
  `harga` DECIMAL(12,2) NOT NULL,
  `gambar` VARCHAR(255) NULL,
  `deskripsi` TEXT NULL,
  `stok` INT NOT NULL DEFAULT 0,
  `opsi_suhu` ENUM('tidak_berlaku','panas_dingin','dingin') NOT NULL DEFAULT 'tidak_berlaku',
  `status` ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  INDEX `idx_produk_kategori` (`id_kategori`),
  INDEX `idx_produk_status` (`status`),
  CONSTRAINT `fk_produk_kategori` 
    FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. TABEL PESANAN
-- ----------------------------------------------------------
CREATE TABLE `pesanan` (
  `id_pesanan` INT PRIMARY KEY AUTO_INCREMENT,
  `id_user` INT NOT NULL,
  `nomor_pesanan` VARCHAR(50) NOT NULL UNIQUE,
  `tanggal` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total` DECIMAL(12,2) NOT NULL,
  `status` ENUM('diproses','selesai','batal') NOT NULL DEFAULT 'diproses',
  `catatan` TEXT NULL,
  INDEX `idx_pesanan_user` (`id_user`),
  INDEX `idx_pesanan_tanggal` (`tanggal`),
  INDEX `idx_pesanan_status` (`status`),
  CONSTRAINT `fk_pesanan_user` 
    FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. TABEL DETAIL PESANAN
-- ----------------------------------------------------------
CREATE TABLE `detail_pesanan` (
  `id_detail` INT PRIMARY KEY AUTO_INCREMENT,
  `id_pesanan` INT NOT NULL,
  `id_produk` INT NOT NULL,
  `jumlah` INT NOT NULL,
  `harga` DECIMAL(12,2) NOT NULL,
  `suhu` ENUM('panas','dingin','tidak_berlaku') NOT NULL DEFAULT 'tidak_berlaku',
  `subtotal` DECIMAL(12,2) NOT NULL,
  INDEX `idx_detail_pesanan` (`id_pesanan`),
  INDEX `idx_detail_produk` (`id_produk`),
  CONSTRAINT `fk_detail_pesanan` 
    FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_detail_produk` 
    FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. TABEL PEMBAYARAN
-- ----------------------------------------------------------
CREATE TABLE `pembayaran` (
  `id_pembayaran` INT PRIMARY KEY AUTO_INCREMENT,
  `id_pesanan` INT NOT NULL UNIQUE,
  `tanggal_pembayaran` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metode_pembayaran` ENUM('cash','qris') NOT NULL,
  `nominal_diterima` DECIMAL(12,2) NOT NULL,
  `kembalian` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status_pembayaran` ENUM('berhasil','pending','gagal') NOT NULL DEFAULT 'pending',
  `bukti_qris` VARCHAR(255) NULL,
  INDEX `idx_pembayaran_pesanan` (`id_pesanan`),
  INDEX `idx_pembayaran_status` (`status_pembayaran`),
  CONSTRAINT `fk_pembayaran_pesanan` 
    FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. TABEL PEMASUKAN
-- ----------------------------------------------------------
CREATE TABLE `pemasukan` (
  `id_pemasukan` INT PRIMARY KEY AUTO_INCREMENT,
  `id_pesanan` INT NOT NULL UNIQUE,
  `tanggal` DATE NOT NULL,
  `jumlah` DECIMAL(12,2) NOT NULL,
  `keterangan` VARCHAR(255) NULL,
  INDEX `idx_pemasukan_tanggal` (`tanggal`),
  CONSTRAINT `fk_pemasukan_pesanan` 
    FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 8. TABEL KATEGORI PENGELUARAN
-- ----------------------------------------------------------
CREATE TABLE `kategori_pengeluaran` (
  `id_kategori_pengeluaran` INT PRIMARY KEY AUTO_INCREMENT,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `deskripsi` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 9. TABEL PENGELUARAN
-- ----------------------------------------------------------
CREATE TABLE `pengeluaran` (
  `id_pengeluaran` INT PRIMARY KEY AUTO_INCREMENT,
  `id_user` INT NOT NULL,
  `id_kategori_pengeluaran` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `jumlah` DECIMAL(12,2) NOT NULL,
  `keterangan` TEXT NULL,
  INDEX `idx_pengeluaran_user` (`id_user`),
  INDEX `idx_pengeluaran_kategori` (`id_kategori_pengeluaran`),
  INDEX `idx_pengeluaran_tanggal` (`tanggal`),
  CONSTRAINT `fk_pengeluaran_user` 
    FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_pengeluaran_kategori` 
    FOREIGN KEY (`id_kategori_pengeluaran`) REFERENCES `kategori_pengeluaran` (`id_kategori_pengeluaran`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 10. TABEL PENGATURAN (Informasi dasar warung & QRIS)
-- ----------------------------------------------------------
CREATE TABLE `pengaturan` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `nama_warung` VARCHAR(100) NOT NULL,
  `slogan` VARCHAR(255) NULL,
  `alamat` TEXT NULL,
  `telepon` VARCHAR(30) NULL,
  `logo` VARCHAR(255) NULL,
  `qris_image` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- DATA AWAL (SEEDER)
-- ==========================================================

-- Users: Admin (admin / admin123), Kasir (kasir / kasir123)
-- Password dienkripsi dengan password_hash() BCRYPT
INSERT INTO `users` (`id_user`, `nama`, `username`, `password`, `role`, `status`) VALUES
(1, 'Administrator Hanisa', 'admin', '$2y$10$90lxEaAnF7Jzzs5u.FQxWe..n7cIGJGzO4pChY9eOvz9csOngwIj6', 'admin', 'aktif'),
(2, 'Kasir Hanisa', 'kasir', '$2y$10$VmO/JyFSwLCPLp4om/FfR.Nn1jIjlrUDdJilVS0tRABCN.Ril/3dq', 'kasir', 'aktif');

-- Kategori
INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`, `status`) VALUES
(1, 'Makanan', 'Menu hidangan makanan khas Warung Makan Hanisa', 'aktif'),
(2, 'Minuman', 'Aneka minuman segar dingin dan hangat', 'aktif');

-- Produk (Data resmi menu Warung Makan Hanisa)
INSERT INTO `produk` (`id_produk`, `id_kategori`, `nama_produk`, `harga`, `gambar`, `deskripsi`, `stok`, `opsi_suhu`, `status`) VALUES
(1, 1, 'Mie Ayam', 20000.00, 'mie-ayam-biasa.jpg', 'Mie ayam gurih dengan potongan daging ayam bumbu rempah pilihan', 50, 'tidak_berlaku', 'aktif'),
(2, 1, 'Mie Ayam Bakso', 23000.00, 'mie-ayam-pentol.jpg', 'Mie ayam lezat disajikan lengkap dengan bakso daging sapi kenyal', 50, 'tidak_berlaku', 'aktif'),
(3, 1, 'Bakso', 20000.00, 'bakso.jpg', 'Bakso sapi asli dengan kuah kaldu sapi kaya rasa dan sayuran segar', 50, 'tidak_berlaku', 'aktif'),
(4, 1, 'Soto', 22000.00, 'soto.jpg', 'Soto ayam kuah bening sedap dengan suwiran ayam dan taburan koya', 50, 'tidak_berlaku', 'aktif'),
(5, 2, 'Es Teh', 5000.00, 'teh.jpg', 'Teh aroma melati pilihan, nikmat disajikan dingin maupun hangat', 100, 'panas_dingin', 'aktif'),
(6, 2, 'Es Jeruk', 6000.00, 'jeruk.jpg', 'Perasan jeruk asli segar kaya vitamin C, mantap dingin maupun hangat', 100, 'panas_dingin', 'aktif'),
(7, 2, 'Es Sirup', 5000.00, 'sirup-susu.jpg', 'Minuman sirup manis aroma buah segar, nikmat dingin maupun hangat', 100, 'panas_dingin', 'aktif'),
(8, 2, 'Air Es', 2000.00, 'air-es.jpg', 'Air mineral segar dengan es batu dingin pelepas dahaga', 100, 'dingin', 'aktif');

-- Kategori Pengeluaran
INSERT INTO `kategori_pengeluaran` (`id_kategori_pengeluaran`, `nama_kategori`, `deskripsi`) VALUES
(1, 'Bahan Baku', 'Pembelian mie, daging ayam, bakso, sayuran, dan rempah bumbu'),
(2, 'Gas', 'Penggantian tabung gas elpiji dapur'),
(3, 'Listrik', 'Pembayaran tagihan listrik warung'),
(4, 'Air', 'Tagihan air PAM dan air mineral galon'),
(5, 'Operasional', 'Plastik, cup minuman, sedotan, tisu, dan perlengkapan kasir'),
(6, 'Lainnya', 'Biaya tak terduga lainnya');

-- Pengaturan Awal
INSERT INTO `pengaturan` (`id`, `nama_warung`, `slogan`, `alamat`, `telepon`, `logo`, `qris_image`) VALUES
(1, 'Warung Makan Hanisa', 'Sistem Informasi Manajemen Penjualan dan Sistem Kasir', 'Jl. Raya Kampus No. 12, Sleman, Yogyakarta', '0812-3456-7890', 'logo.png', 'qris.png');
