// Memanggil fungsi ringkasInventaris dari file utils.js (untuk poin export/import)
import { ringkasInventaris } from './utils.js';

// ==========================================
// 1. MEMBUAT ARRAY OBJEK DATA INVENTARIS
// ==========================================
const inventarisWarung = [
    { id: 1, nama: "Mie Ayam Biasa", kategori: "Makanan", harga: 12000, tersedia: true },
    { id: 2, nama: "Mie Ayam Pentol", kategori: "Makanan", harga: 15000, tersedia: true },
    { id: 3, nama: "Bakso", kategori: "Makanan", harga: 13000, tersedia: true },
    { id: 4, nama: "Soto", kategori: "Makanan", harga: 14000, tersedia: false },
    { id: 5, nama: "Es Teh", kategori: "Minuman", harga: 4000, tersedia: true },
    { id: 6, nama: "Es Jeruk", kategori: "Minuman", harga: 5000, tersedia: true }
];

console.log("--- DATA SELURUH INVENTARIS ---");
console.log(inventarisWarung);

// ==========================================
// 2. MENGGUNAKAN METODE ARRAY (FILTER, MAP, FIND, REDUCE)
// ==========================================

// A. FILTER: Menyaring menu yang statusnya tersedia (true) saja
const menuTersedia = inventarisWarung.filter(item => item.tersedia === true);
console.log("--- Hasil FILTER (Menu Tersedia) ---", menuTersedia);

// B. MAP: Mengubah semua nama menu menjadi huruf kapital (Uppercase)
const namaMenuUppercase = inventarisWarung.map(item => item.nama.toUpperCase());
console.log("--- Hasil MAP (Nama Menu Uppercase) ---", namaMenuUppercase);

// C. FIND: Mencari satu menu spesifik berdasarkan namanya
const cariMenu = inventarisWarung.find(item => item.nama === "Mie Ayam Pentol");
console.log("--- Hasil FIND (Pencarian Menu) ---", cariMenu);

// D. REDUCE: Menghitung total keseluruhan harga dari semua menu
const totalHargaSemua = inventarisWarung.reduce((total, item) => total + item.harga, 0);
console.log("--- Hasil REDUCE (Total Harga Semua Menu) ---", totalHargaSemua);

// ==========================================
// 3. MENGGUNAKAN FUNGSI DARI utils.js
// ==========================================
const hasilRingkasan = ringkasInventaris(inventarisWarung);
console.log("--- Hasil RINGKASAN (dari utils.js) ---", hasilRingkasan);