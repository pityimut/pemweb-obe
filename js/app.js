// Memanggil fungsi dari utils.js
import { ringkasInventaris } from './utils.js';

// ==========================================
// DATA INVENTARIS WARUNG MAKAN HANISA
// ==========================================
const inventarisWarung = [
    { id: 1, nama: "Mie Ayam Biasa", kategori: "Makanan", harga: 12000, tersedia: true, lokasi: "Jl. Bangsal Tengah" },
    { id: 2, nama: "Mie Ayam Pentol", kategori: "Makanan", harga: 15000, tersedia: true, lokasi: "Jl. Bangsal Tengah" },
    { id: 3, nama: "Bakso", kategori: "Makanan", harga: 13000, tersedia: true, lokasi: "Jl. Bangsal Tengah" },
    { id: 4, nama: "Soto", kategori: "Makanan", harga: 14000, tersedia: false, lokasi: "Jl. Bangsal Tengah" },
    { id: 5, nama: "Es Teh", kategori: "Minuman", harga: 4000, tersedia: true, lokasi: "Jl. Bangsal Tengah" },
    { id: 6, nama: "Es Jeruk", kategori: "Minuman", harga: 5000, tersedia: true, lokasi: "Jl. Bangsal Tengah" }
];

console.log("--- DATA SELURUH INVENTARIS ---", inventarisWarung);

// ==========================================
// LATIHAN 1: Filter menu berdasarkan lokasi
// ==========================================
const menuLokasiKhusus = inventarisWarung.filter(item => item.lokasi === "Jl. Bangsal Tengah");
console.log("--- LATIHAN 1: Menu di Jl. Bangsal Tengah ---", menuLokasiKhusus);

// ==========================================
// LATIHAN 2: Fungsi mencari item berdasarkan ID
// ==========================================
function cariMenuBerdasarkanId(idMenu) {
    return inventarisWarung.find(item => item.id === idMenu);
}

const hasilCariId = cariMenuBerdasarkanId(2);
console.log("--- LATIHAN 2: Cari Menu Berdasarkan ID 2 ---", hasilCariId);

// ==========================================
// LATIHAN 3: Destructuring & Template Literals
// ==========================================
console.log("--- LATIHAN 3: Ringkasan Menu (Destructuring & Template Literals) ---");
inventarisWarung.forEach(item => {
    // Destructuring objek item
    const { nama, harga, kategori, lokasi } = item;
    
    // Menggunakan Template Literals
    console.log(`Menu ${nama} (${kategori}) berharga Rp ${harga} tersedia di ${lokasi}.`);
});

// Fungsi bawaan utils sebelumnya
const hasilRingkasan = ringkasInventaris(inventarisWarung);
console.log("--- Hasil RINGKASAN (dari utils.js) ---", hasilRingkasan);