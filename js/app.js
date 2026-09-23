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
    const { nama, harga, kategori, lokasi } = item;
    console.log(`Menu ${nama} (${kategori}) berharga Rp ${harga} tersedia di ${lokasi}.`);
});

const hasilRingkasan = ringkasInventaris(inventarisWarung);
console.log("--- Hasil RINGKASAN (dari utils.js) ---", hasilRingkasan);

// ==========================================
// RENDER MENU MENGGUNAKAN DOM MANIPULATION
// ==========================================
const menuContainer = document.getElementById('menu-container');

function renderItems(dataArray) {
    if (!menuContainer) return;
    
    menuContainer.innerHTML = '';
    menuContainer.style.display = 'grid';
    menuContainer.style.gridTemplateColumns = 'repeat(auto-fit, minmax(200px, 1fr))';
    menuContainer.style.gap = '1.5rem';
    menuContainer.style.marginBottom = '3rem';

    dataArray.forEach(item => {
        const card = document.createElement('article');
        card.style.backgroundColor = 'var(--surface)';
        card.style.padding = '1rem';
        card.style.border = '1px solid #ddd';
        card.style.borderRadius = 'var(--radius)';
        card.style.textAlign = 'center';
        card.style.display = 'flex';
        card.style.flexDirection = 'column';

        const title = document.createElement('h3');
        title.textContent = item.nama;

        const desc = document.createElement('p');
        desc.textContent = `${item.kategori} - Rp ${item.harga}`;

        card.append(title, desc);
        menuContainer.append(card);
    });
}

// Jalankan render awal
renderItems(inventarisWarung);

// ==========================================
// EVENT LISTENER UNTUK TOMBOL INTERAKTIF
// ==========================================
const filterButtons = document.querySelectorAll('.filter-btn');

filterButtons.forEach(button => {
    button.addEventListener('click', (e) => {
        const kategoriFilter = e.currentTarget.getAttribute('data-filter');
        
        if (kategoriFilter === 'all') {
            renderItems(inventarisWarung);
        } else {
            const filteredData = inventarisWarung.filter(item => item.kategori === kategoriFilter);
            renderItems(filteredData);
        }
    });
});

const prefButton = document.getElementById('pref-btn');

if (prefButton) {
    prefButton.addEventListener('click', () => {
        let currentView = localStorage.getItem('cardView') || 'grid';
        
        if (currentView === 'grid') {
            currentView = 'list';
            if (menuContainer) menuContainer.style.gridTemplateColumns = '1fr';
        } else {
            currentView = 'grid';
            if (menuContainer) menuContainer.style.gridTemplateColumns = 'repeat(auto-fit, minmax(200px, 1fr))';
        }

        localStorage.setItem('cardView', currentView);
    });
}