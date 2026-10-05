/**
 * Data awal Warung Hanisa
 * Berisi daftar menu awal dengan gambar lokal, kategori, harga modal, harga jual, dan stok awal.
 */

export const DEFAULT_STORE_INFO = {
    name: "Warung Hanisa",
    tagline: "Sistem Kasir & Manajemen Warung",
    address: "Jl. Pemuda No. 18, Warung Hanisa (Depan Kampus)",
    phone: "0812-3456-7890",
    receiptFooter: "Terima Kasih atas Kunjungan Anda!\nSemoga Berkah & Selamat Menikmati."
};

export const DEFAULT_PRODUCTS = [
    {
        id: "PRD-001",
        name: "Bakso Sapi Spesial",
        category: "Makanan",
        costPrice: 10000,
        price: 15000,
        stock: 25,
        unit: "Porsi",
        image: "images/bakso.jpg",
        description: "Bakso daging sapi asli dengan kuah kaldu gurih & seledri"
    },
    {
        id: "PRD-002",
        name: "Soto Ayam Komplit",
        category: "Makanan",
        costPrice: 9000,
        price: 14000,
        stock: 20,
        unit: "Porsi",
        image: "images/soto.jpg",
        description: "Soto ayam kuah kuning bening segar dengan suwiran ayam & koya"
    },
    {
        id: "PRD-003",
        name: "Mie Ayam Biasa",
        category: "Makanan",
        costPrice: 7000,
        price: 11000,
        stock: 30,
        unit: "Porsi",
        image: "images/mie-ayam-biasa.jpg",
        description: "Mie kenyal dengan potongan ayam kecap manis gurih & sawi segar"
    },
    {
        id: "PRD-004",
        name: "Mie Ayam Pentol",
        category: "Makanan",
        costPrice: 9000,
        price: 14000,
        stock: 18,
        unit: "Porsi",
        image: "images/mie-ayam-pentol.jpg",
        description: "Mie ayam gurih disajikan dengan 2 butir pentol bakso daging sapi"
    },
    {
        id: "PRD-005",
        name: "Es Teh Manis",
        category: "Minuman",
        costPrice: 1500,
        price: 4000,
        stock: 50,
        unit: "Gelas",
        image: "images/teh.jpg",
        description: "Teh melati wangi dengan es kristal dan gula asli"
    },
    {
        id: "PRD-006",
        name: "Es Jeruk Peras Segar",
        category: "Minuman",
        costPrice: 2500,
        price: 6000,
        stock: 35,
        unit: "Gelas",
        image: "images/jeruk.jpg",
        description: "Perasan jeruk segar asli kaya vitamin C dengan es batu"
    },
    {
        id: "PRD-007",
        name: "Sirup Susu Manis",
        category: "Minuman",
        costPrice: 3000,
        price: 7000,
        stock: 22,
        unit: "Gelas",
        image: "images/sirup-susu.jpg",
        description: "Kombinasi sirup merah legit dengan kental manis dan es serut"
    },
    {
        id: "PRD-008",
        name: "Air Es Kristal",
        category: "Minuman",
        costPrice: 500,
        price: 2000,
        stock: 40,
        unit: "Gelas",
        image: "images/air-es.jpg",
        description: "Air putih higienis dingin segar dengan es kristal"
    }
];

export const DEFAULT_CUSTOMERS = [
    {
        id: "CUST-001",
        name: "Pelanggan Umum",
        phone: "-",
        address: "Langsung di Tempat",
        totalOrders: 0,
        totalSpent: 0,
        notes: "Pelanggan umum tanpa nomor telepon"
    },
    {
        id: "CUST-002",
        name: "Pak Budi Santoso",
        phone: "081298765432",
        address: "Perum Graha Indah Blok C3",
        totalOrders: 3,
        totalSpent: 95000,
        notes: "Langganan mie ayam & es teh manis"
    },
    {
        id: "CUST-003",
        name: "Mbak Rina Rahmawati",
        phone: "085712348901",
        address: "Kos Melati No. 12",
        totalOrders: 2,
        totalSpent: 48000,
        notes: "Suka bakso pedas kuah banyak"
    }
];
