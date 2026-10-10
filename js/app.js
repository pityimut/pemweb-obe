/**
 * Sistem Kasir Warung Hanisa (POS & Manajemen)
 * 15 Fitur Utama:
 * 1. Login & Registrasi Akun (Kasir & Admin 1 Akun)
 * 2. Dasbor Ringkasan & Statistik
 * 3. Data Produk (CRUD Menu & Restock)
 * 4. Data Pelanggan (CRUD Pelanggan)
 * 5. Pilih Produk (POS Catalog)
 * 6. Masukkan Jumlah Produk (Qty Selector & Notes)
 * 7. Keranjang Belanja (Cart Management)
 * 8. Hitung Total Pembayaran (Subtotal, Diskon, Grand Total)
 * 9. Pilih Metode Pembayaran (Tunai, QRIS, Transfer, Kasbon)
 * 10. Proses Pembayaran (Kalkulator Uang Diterima & Kembalian)
 * 11. Konfirmasi Pembayaran (Validasi & Finalisasi)
 * 12. Notifikasi Pembayaran Berhasil & Cetak Struk Thermal
 * 13. Simpan Data Transaksi (Riwayat & Export CSV)
 * 14. Perbarui Produk Stok (Otomatis & Realtime)
 * 15. Keluar (Logout)
 */

import { DEFAULT_STORE_INFO, DEFAULT_PRODUCTS, DEFAULT_CUSTOMERS } from "./data.js";
import {
    formatRupiah,
    parseNumber,
    getTanggalHariIni,
    formatTanggal,
    formatTanggalPendek,
    formatWaktu,
    generateInvoiceNumber,
    generateId,
    showToast,
    playCashChime,
    exportToCSV
} from "./utils.js";

/* ==========================================================================
   APPLICATION STATE
   ========================================================================== */
const STATE = {
    currentUser: null,
    isLoggedIn: false,
    currentPage: "dasbor",
    products: [],
    customers: [],
    transactions: [],
    cart: [],
    selectedCustomer: null,
    discount: {
        type: "nominal", // "nominal" | "percent"
        value: 0
    },
    activeCategory: localStorage.getItem("hanisa_active_category") || "Semua",
    searchPOSQuery: "",
    selectedProductForQty: null,
    currentCheckout: null
};

/* ==========================================================================
   INITIALIZATION
   ========================================================================== */
document.addEventListener("DOMContentLoaded", () => {
    initClock();
    initStorage();
    initEventListeners();
    checkAuthSession();
});

/**
 * Inisialisasi Jam dan Tanggal Realtime
 */
function initClock() {
    const liveClockEl = document.getElementById("live-clock");
    const topbarDateEl = document.getElementById("topbar-date-string");

    function updateTime() {
        const now = new Date();
        if (liveClockEl) {
            liveClockEl.textContent = formatWaktu(now);
        }
        if (topbarDateEl) {
            topbarDateEl.textContent = formatTanggal(now);
        }
    }

    updateTime();
    setInterval(updateTime, 1000);
}

/**
 * Inisialisasi Data dari LocalStorage atau Gunakan Data Awal
 */
function initStorage() {
    // 1. Produk
    const savedProducts = localStorage.getItem("hanisa_products");
    if (savedProducts) {
        try {
            STATE.products = JSON.parse(savedProducts);
        } catch (e) {
            STATE.products = [...DEFAULT_PRODUCTS];
        }
    } else {
        STATE.products = [...DEFAULT_PRODUCTS];
        localStorage.setItem("hanisa_products", JSON.stringify(STATE.products));
    }

    // 2. Pelanggan
    const savedCustomers = localStorage.getItem("hanisa_customers");
    if (savedCustomers) {
        try {
            STATE.customers = JSON.parse(savedCustomers);
        } catch (e) {
            STATE.customers = [...DEFAULT_CUSTOMERS];
        }
    } else {
        STATE.customers = [...DEFAULT_CUSTOMERS];
        localStorage.setItem("hanisa_customers", JSON.stringify(STATE.customers));
    }

    // 3. Transaksi
    const savedTransactions = localStorage.getItem("hanisa_transactions");
    if (savedTransactions) {
        try {
            STATE.transactions = JSON.parse(savedTransactions);
        } catch (e) {
            STATE.transactions = [];
        }
    } else {
        STATE.transactions = [];
        localStorage.setItem("hanisa_transactions", JSON.stringify(STATE.transactions));
    }

    // Default Customer di Cart
    STATE.selectedCustomer = STATE.customers[0] || { id: "CUST-001", name: "Pelanggan Umum" };
}

/* ==========================================================================
   1. AUTHENTICATION (LOGIN & REGISTRASI AKUN KASIR & ADMIN)
   ========================================================================== */
function checkAuthSession() {
    const authSection = document.getElementById("auth-section");
    const appContainer = document.getElementById("app-container");
    const registerBox = document.getElementById("auth-register-box");
    const loginBox = document.getElementById("auth-login-box");
    const registeredAccountBadge = document.getElementById("registered-account-badge");

    const savedAccountStr = localStorage.getItem("hanisa_admin_account");
    const isLoggedIn = localStorage.getItem("hanisa_is_logged_in") === "true";

    let savedAccount = null;
    if (savedAccountStr) {
        try {
            savedAccount = JSON.parse(savedAccountStr);
        } catch (e) {
            savedAccount = null;
        }
    }

    // Kasus 1: Belum pernah daftar -> Tampilkan Registrasi Pertama Kali
    if (!savedAccount) {
        authSection.classList.remove("hidden");
        appContainer.classList.add("hidden");
        registerBox.classList.remove("hidden");
        loginBox.classList.add("hidden");
        return;
    }

    // Kasus 2: Sudah pernah daftar -> Tampilkan Login dengan info akun
    document.getElementById("rab-name").textContent = `${savedAccount.fullName} (Kasir & Admin)`;
    document.getElementById("rab-username").textContent = `Username: ${savedAccount.username}`;
    document.getElementById("login-username").value = savedAccount.username;

    // Kasus 3: Sesi login masih aktif
    if (isLoggedIn) {
        STATE.currentUser = savedAccount;
        STATE.isLoggedIn = true;
        showApp();
    } else {
        authSection.classList.remove("hidden");
        appContainer.classList.add("hidden");
        registerBox.classList.add("hidden");
        loginBox.classList.remove("hidden");
    }
}

function handleRegistration(e) {
    e.preventDefault();
    const fullName = document.getElementById("reg-fullname").value.trim();
    const storeName = document.getElementById("reg-storename").value.trim();
    const username = document.getElementById("reg-username").value.trim().toLowerCase();
    const password = document.getElementById("reg-password").value;
    const confirmPassword = document.getElementById("reg-confirm-password").value;

    if (!fullName || !username || !password) {
        showToast("Lengkapi semua field pendaftaran!", "error");
        return;
    }

    if (password !== confirmPassword) {
        showToast("Password dan Konfirmasi Password tidak cocok!", "error");
        return;
    }

    if (password.length < 4) {
        showToast("Password minimal 4 karakter!", "warning");
        return;
    }

    const newAccount = {
        fullName,
        storeName: storeName || DEFAULT_STORE_INFO.name,
        username,
        password,
        role: "Kasir & Admin",
        registeredAt: new Date().toISOString()
    };

    localStorage.setItem("hanisa_admin_account", JSON.stringify(newAccount));
    showToast("🎉 Pendaftaran berhasil! Silakan masuk dengan akun Anda.", "success");

    // Beralih ke form login
    checkAuthSession();
}

function handleLogin(e) {
    e.preventDefault();
    const usernameInput = document.getElementById("login-username").value.trim().toLowerCase();
    const passwordInput = document.getElementById("login-password").value;

    const savedAccountStr = localStorage.getItem("hanisa_admin_account");
    if (!savedAccountStr) {
        showToast("Belum ada akun terdaftar! Silakan mendaftar terlebih dahulu.", "error");
        checkAuthSession();
        return;
    }

    const savedAccount = JSON.parse(savedAccountStr);

    if (usernameInput === savedAccount.username && passwordInput === savedAccount.password) {
        STATE.currentUser = savedAccount;
        STATE.isLoggedIn = true;
        localStorage.setItem("hanisa_is_logged_in", "true");
        localStorage.setItem("hanisa_current_user", JSON.stringify(savedAccount));

        showToast(`Selamat datang, ${savedAccount.fullName}!`, "success");
        showApp();
    } else {
        showToast("Username atau Password salah!", "error");
    }
}

function showApp() {
    document.getElementById("auth-section").classList.add("hidden");
    document.getElementById("app-container").classList.remove("hidden");

    // Update Profile Kasir & Admin di UI
    const name = STATE.currentUser?.fullName || "Hanisa";
    const initial = name.charAt(0).toUpperCase();

    document.getElementById("sidebar-display-name").textContent = name;
    document.getElementById("sidebar-avatar-initial").textContent = initial;
    document.getElementById("topbar-username").textContent = name;
    document.getElementById("topbar-avatar-initial").textContent = initial;
    document.getElementById("dash-welcome-name").textContent = name;

    // Render Data Awal
    switchPage("dasbor");
}

/* ==========================================================================
   NAVIGATION SYSTEM
   ========================================================================== */
function switchPage(pageId) {
    STATE.currentPage = pageId;

    // Toggle navigation buttons
    document.querySelectorAll(".nav-btn[data-page]").forEach(btn => {
        if (btn.dataset.page === pageId) {
            btn.classList.add("active");
        } else {
            btn.classList.remove("active");
        }
    });

    // Toggle Page Views
    document.querySelectorAll(".app-page-view").forEach(view => {
        view.classList.add("hidden");
    });

    const activeView = document.getElementById(`page-${pageId}`);
    if (activeView) {
        activeView.classList.remove("hidden");
    }

    // Update Topbar Title
    const titles = {
        dasbor: "Dasbor Ringkasan",
        pos: "Kasir (Point of Sale)",
        produk: "Kelola Data Produk & Menu",
        pelanggan: "Kelola Data Pelanggan",
        transaksi: "Riwayat & Laporan Transaksi"
    };
    document.getElementById("current-page-title").textContent = titles[pageId] || "Sistem Kasir";

    // Re-render specific pages
    if (pageId === "dasbor") renderDashboard();
    if (pageId === "pos") renderPOS();
    if (pageId === "produk") renderProductsTable();
    if (pageId === "pelanggan") renderCustomersTable();
    if (pageId === "transaksi") renderTransactionsTable();

    // Close mobile sidebar if open
    document.getElementById("app-sidebar").classList.remove("sidebar-open");
}

/* ==========================================================================
   2. DASBOR (RINGKASAN, STATISTIK, ALERT STOK, SHORTCUTS)
   ========================================================================== */
function renderDashboard() {
    const today = getTanggalHariIni();

    // 1. Filter transaksi hari ini
    const todayTxs = STATE.transactions.filter(tx => {
        const txDate = tx.createdAt ? tx.createdAt.split("T")[0] : "";
        return txDate === today;
    });

    const todayRevenue = todayTxs.reduce((sum, tx) => sum + (tx.grandTotal || 0), 0);
    const todayTxCount = todayTxs.length;

    document.getElementById("dash-stat-revenue").textContent = formatRupiah(todayRevenue);
    document.getElementById("dash-stat-revenue-count").textContent = `${todayTxCount} Transaksi Hari Ini`;
    document.getElementById("dash-stat-tx-count").textContent = STATE.transactions.length;

    // 2. Hitung Produk & Stok
    const productCount = STATE.products.length;
    const totalStock = STATE.products.reduce((sum, p) => sum + (Number(p.stock) || 0), 0);
    document.getElementById("dash-stat-product-count").textContent = `${productCount} Menu`;
    document.getElementById("dash-stat-stock-count").textContent = `Total ${totalStock} Porsi Stok`;

    // 3. Pelanggan
    document.getElementById("dash-stat-customer-count").textContent = `${STATE.customers.length} Orang`;

    // 4. Alert Stok Menipis (< 5 pcs)
    const lowStockItems = STATE.products.filter(p => Number(p.stock) <= 5);
    const lowStockAlertBox = document.getElementById("low-stock-alert-box");
    const lowStockContainer = document.getElementById("low-stock-items-container");

    if (lowStockItems.length > 0) {
        lowStockAlertBox.classList.remove("hidden");
        lowStockContainer.innerHTML = lowStockItems.map(p => `
            <div class="low-stock-chip">
                <span class="lsc-name">${p.name}</span>
                <span class="lsc-badge ${Number(p.stock) === 0 ? 'badge-danger' : ''}">
                    ${Number(p.stock) === 0 ? 'HABIS' : 'Sisa ' + p.stock + ' ' + (p.unit || 'porsi')}
                </span>
                <button type="button" class="lsc-btn-restock" data-id="${p.id}">
                    + Restock
                </button>
            </div>
        `).join("");

        // Pasang event listener tombol restock pada alert
        lowStockContainer.querySelectorAll(".lsc-btn-restock").forEach(btn => {
            btn.addEventListener("click", () => {
                const prod = STATE.products.find(x => x.id === btn.dataset.id);
                if (prod) openQuickRestockModal(prod);
            });
        });
    } else {
        lowStockAlertBox.classList.add("hidden");
    }

    // 5. Tabel 5 Transaksi Terbaru
    const recentTxs = [...STATE.transactions].reverse().slice(0, 5);
    const tbody = document.getElementById("dash-recent-tx-tbody");
    if (recentTxs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    Belum ada transaksi hari ini. Silakan mulai transaksi di menu Kasir! 🛒
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = recentTxs.map(tx => `
            <tr>
                <td><strong>${tx.id}</strong></td>
                <td>${formatWaktu(tx.createdAt)}</td>
                <td>${tx.customerName || 'Umum'}</td>
                <td>
                    <span class="badge-payment pay-${tx.paymentMethod.toLowerCase()}">
                        ${tx.paymentMethod}
                    </span>
                </td>
                <td><strong class="text-pink">${formatRupiah(tx.grandTotal)}</strong></td>
                <td>
                    <button type="button" class="btn-tbl-action btn-tbl-edit btn-view-receipt" data-id="${tx.id}">
                        Lihat Struk
                    </button>
                </td>
            </tr>
        `).join("");

        tbody.querySelectorAll(".btn-view-receipt").forEach(btn => {
            btn.addEventListener("click", () => {
                const tx = STATE.transactions.find(x => x.id === btn.dataset.id);
                if (tx) showReceiptModal(tx);
            });
        });
    }

    // 6. Ringkasan Metode Pembayaran
    const methodStats = {
        Tunai: { total: 0, count: 0 },
        QRIS: { total: 0, count: 0 },
        Transfer: { total: 0, count: 0 },
        Kasbon: { total: 0, count: 0 }
    };

    STATE.transactions.forEach(tx => {
        const m = tx.paymentMethod;
        if (methodStats[m]) {
            methodStats[m].total += tx.grandTotal || 0;
            methodStats[m].count += 1;
        }
    });

    document.getElementById("pms-cash-total").textContent = formatRupiah(methodStats.Tunai.total);
    document.getElementById("pms-cash-count").textContent = `${methodStats.Tunai.count} tx`;

    document.getElementById("pms-qris-total").textContent = formatRupiah(methodStats.QRIS.total);
    document.getElementById("pms-qris-count").textContent = `${methodStats.QRIS.count} tx`;

    document.getElementById("pms-transfer-total").textContent = formatRupiah(methodStats.Transfer.total);
    document.getElementById("pms-transfer-count").textContent = `${methodStats.Transfer.count} tx`;

    document.getElementById("pms-kasbon-total").textContent = formatRupiah(methodStats.Kasbon.total);
    document.getElementById("pms-kasbon-count").textContent = `${methodStats.Kasbon.count} tx`;
}

/* ==========================================================================
   5. PILIH PRODUK (POS CATALOG GRID)
   ========================================================================== */
function renderPOS() {
    renderPOSProducts();
    renderCart();
    renderCustomerSelectDropdown();
}

function renderPOSProducts() {
    const container = document.getElementById("pos-products-container");
    if (!container) return;

    let filtered = [...STATE.products];

    // Filter Kategori
    if (STATE.activeCategory !== "Semua") {
        filtered = filtered.filter(p => p.category === STATE.activeCategory);
    }

    // Filter Search
    if (STATE.searchPOSQuery.trim()) {
        const q = STATE.searchPOSQuery.toLowerCase();
        filtered = filtered.filter(p =>
            p.name.toLowerCase().includes(q) ||
            p.id.toLowerCase().includes(q) ||
            (p.category && p.category.toLowerCase().includes(q))
        );
    }

    if (filtered.length === 0) {
        container.innerHTML = `
            <div class="cart-empty-state" style="grid-column: 1 / -1;">
                <span class="ces-icon">🔍</span>
                <strong>Menu tidak ditemukan</strong>
                <p>Coba gunakan kata kunci pencarian atau kategori lain.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = filtered.map(p => {
        const stock = Number(p.stock) || 0;
        const isOutOfStock = stock <= 0;
        let stockBadgeClass = "";
        let stockText = `Stok: ${stock} ${p.unit || 'porsi'}`;

        if (isOutOfStock) {
            stockBadgeClass = "stock-empty";
            stockText = "Stok Habis";
        } else if (stock <= 5) {
            stockBadgeClass = "stock-low";
            stockText = `Sisa ${stock} ${p.unit || 'porsi'}`;
        }

        return `
            <div class="product-card ${isOutOfStock ? 'out-of-stock' : ''}" data-id="${p.id}">
                <div class="product-image-box">
                    <img src="${p.image || 'images/bakso.jpg'}" alt="${p.name}" loading="lazy" onerror="this.src='images/bakso.jpg'">
                    <span class="product-badge-category">${p.category}</span>
                    <span class="product-badge-stock ${stockBadgeClass}">${stockText}</span>
                </div>
                <div class="product-details">
                    <div>
                        <h4 class="product-title">${p.name}</h4>
                        <p class="product-desc">${p.description || 'Menu lezat pilihan Warung Hanisa'}</p>
                    </div>
                    <div class="product-bottom-row">
                        <span class="product-price-tag">${formatRupiah(p.price)}</span>
                        <button type="button" class="btn-add-to-cart" title="${isOutOfStock ? 'Stok Habis' : 'Tambah Pesanan'}" ${isOutOfStock ? 'disabled' : ''}>
                            ${isOutOfStock ? '✕' : '+'}
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join("");

    // Klik kartu atau tombol tambah
    container.querySelectorAll(".product-card").forEach(card => {
        card.addEventListener("click", (e) => {
            const prodId = card.dataset.id;
            const product = STATE.products.find(p => p.id === prodId);
            if (!product) return;

            if (Number(product.stock) <= 0) {
                showToast(`Stok ${product.name} sedang habis!`, "warning");
                return;
            }

            // Buka Modal 6: Masukkan Jumlah & Catatan
            openProductQtyModal(product);
        });
    });
}

/* ==========================================================================
   6. MASUKKAN JUMLAH PRODUK (MODAL QTY SELECTOR & NOTES)
   ========================================================================== */
function openProductQtyModal(product) {
    STATE.selectedProductForQty = product;

    const modal = document.getElementById("modal-product-qty");
    const nameEl = document.getElementById("mpq-product-name");
    const imgEl = document.getElementById("mpq-image");
    const priceEl = document.getElementById("mpq-unit-price");
    const stockEl = document.getElementById("mpq-stock-info");
    const qtyInput = document.getElementById("mpq-qty-input");
    const subtotalEl = document.getElementById("mpq-subtotal-price");
    const notesInput = document.getElementById("mpq-item-notes");
    const warningEl = document.getElementById("mpq-stock-warning");

    nameEl.textContent = product.name;
    imgEl.src = product.image || "images/bakso.jpg";
    imgEl.onerror = () => { imgEl.src = "images/bakso.jpg"; };
    priceEl.textContent = formatRupiah(product.price);
    stockEl.textContent = `Tersedia: ${product.stock} ${product.unit || 'Porsi'}`;

    // Cek apakah item ini sudah ada di keranjang untuk memperhitungkan sisa stok
    const existingInCart = STATE.cart.find(item => item.product.id === product.id);
    const currentInCartQty = existingInCart ? existingInCart.qty : 0;
    const remainingStock = Math.max(0, product.stock - currentInCartQty);

    qtyInput.value = 1;
    qtyInput.max = remainingStock > 0 ? remainingStock : product.stock;
    notesInput.value = existingInCart ? existingInCart.note || "" : "";
    warningEl.classList.add("hidden");

    function updateModalSubtotal() {
        const q = parseInt(qtyInput.value, 10) || 1;
        const total = q * product.price;
        subtotalEl.textContent = formatRupiah(total);

        if (q > product.stock) {
            warningEl.classList.remove("hidden");
            warningEl.textContent = `Jumlah melebihi stok yang tersedia (${product.stock})!`;
        } else {
            warningEl.classList.add("hidden");
        }
    }

    updateModalSubtotal();

    // Event Listener Stepper di Modal
    const btnMinus = document.getElementById("mpq-btn-minus");
    const btnPlus = document.getElementById("mpq-btn-plus");

    btnMinus.onclick = () => {
        let val = parseInt(qtyInput.value, 10) || 1;
        if (val > 1) {
            qtyInput.value = val - 1;
            updateModalSubtotal();
        }
    };

    btnPlus.onclick = () => {
        let val = parseInt(qtyInput.value, 10) || 1;
        if (val < product.stock) {
            qtyInput.value = val + 1;
            updateModalSubtotal();
        } else {
            showToast(`Stok ${product.name} hanya tersisa ${product.stock}!`, "warning");
        }
    };

    qtyInput.oninput = () => {
        let val = parseInt(qtyInput.value, 10);
        if (isNaN(val) || val < 1) val = 1;
        if (val > product.stock) {
            val = product.stock;
            showToast(`Stok maksimal tersedia: ${product.stock}`, "warning");
        }
        qtyInput.value = val;
        updateModalSubtotal();
    };

    // Tombol Tambahkan ke Keranjang
    const btnAdd = document.getElementById("mpq-btn-add-to-cart");
    btnAdd.onclick = () => {
        const qty = parseInt(qtyInput.value, 10) || 1;
        const note = notesInput.value.trim();

        if (qty > product.stock) {
            showToast(`Stok tidak mencukupi! Sisa stok: ${product.stock}`, "error");
            return;
        }

        addToCart(product, qty, note);
        closeModal("modal-product-qty");
    };

    openModal("modal-product-qty");
}

/* ==========================================================================
   7. KERANJANG BELANJA (CART MANAGEMENT) & 8. HITUNG PEMBAYARAN
   ========================================================================== */
function addToCart(product, qty, note = "") {
    const existingIndex = STATE.cart.findIndex(item => item.product.id === product.id);

    if (existingIndex > -1) {
        const newTotalQty = STATE.cart[existingIndex].qty + qty;
        if (newTotalQty > product.stock) {
            showToast(`Stok tidak mencukupi! Sisa stok: ${product.stock}`, "warning");
            STATE.cart[existingIndex].qty = product.stock;
        } else {
            STATE.cart[existingIndex].qty = newTotalQty;
        }
        if (note) {
            STATE.cart[existingIndex].note = note;
        }
    } else {
        STATE.cart.push({
            product: { ...product },
            qty: qty,
            note: note,
            unitPrice: product.price
        });
    }

    showToast(`🛒 Ditambahkan: ${qty}x ${product.name}`, "success");
    renderCart();
}

function updateCartQty(productId, delta) {
    const item = STATE.cart.find(i => i.product.id === productId);
    if (!item) return;

    const originalProduct = STATE.products.find(p => p.id === productId);
    const maxStock = originalProduct ? originalProduct.stock : 999;

    const newQty = item.qty + delta;
    if (newQty <= 0) {
        removeFromCart(productId);
        return;
    }

    if (newQty > maxStock) {
        showToast(`Stok tidak mencukupi! Maksimal: ${maxStock}`, "warning");
        return;
    }

    item.qty = newQty;
    renderCart();
}

function removeFromCart(productId) {
    const item = STATE.cart.find(i => i.product.id === productId);
    STATE.cart = STATE.cart.filter(i => i.product.id !== productId);
    if (item) {
        showToast(`Dihapus dari keranjang: ${item.product.name}`, "info");
    }
    renderCart();
}

function clearCart() {
    if (STATE.cart.length === 0) return;
    if (confirm("Apakah Anda yakin ingin mengosongkan seluruh keranjang belanja?")) {
        STATE.cart = [];
        STATE.discount = { type: "nominal", value: 0 };
        renderCart();
        showToast("Keranjang berhasil dikosongkan.", "info");
    }
}

/**
 * 8. Hitung Total Pembayaran
 */
function calculateCartTotals() {
    const subtotal = STATE.cart.reduce((sum, item) => sum + (item.qty * item.product.price), 0);

    let discountAmount = 0;
    if (STATE.discount.type === "percent") {
        discountAmount = Math.round((subtotal * STATE.discount.value) / 100);
    } else {
        discountAmount = STATE.discount.value || 0;
    }

    if (discountAmount > subtotal) {
        discountAmount = subtotal;
    }

    const grandTotal = Math.max(0, subtotal - discountAmount);

    return {
        subtotal,
        discountAmount,
        grandTotal,
        totalItems: STATE.cart.reduce((sum, item) => sum + item.qty, 0)
    };
}

function renderCart() {
    const container = document.getElementById("cart-items-container");
    const countLabel = document.getElementById("cart-item-count-label");
    const subtotalVal = document.getElementById("cart-subtotal-val");
    const discountVal = document.getElementById("cart-discount-val");
    const grandTotalVal = document.getElementById("cart-grand-total");
    const btnCheckout = document.getElementById("btn-open-payment-modal");
    const btnCheckoutPrice = document.getElementById("btn-checkout-price");
    const navCartBadge = document.getElementById("nav-cart-indicator");

    const totals = calculateCartTotals();

    // Update Badges
    if (countLabel) countLabel.textContent = `${totals.totalItems} item dipilih`;
    if (navCartBadge) navCartBadge.textContent = totals.totalItems;

    // Update Totals
    if (subtotalVal) subtotalVal.textContent = formatRupiah(totals.subtotal);
    if (discountVal) discountVal.textContent = `- ${formatRupiah(totals.discountAmount)}`;
    if (grandTotalVal) grandTotalVal.textContent = formatRupiah(totals.grandTotal);
    if (btnCheckoutPrice) btnCheckoutPrice.textContent = formatRupiah(totals.grandTotal);

    // Aktifkan / Nonaktifkan Tombol Pembayaran
    if (btnCheckout) {
        btnCheckout.disabled = STATE.cart.length === 0;
    }

    // Render Items
    if (!container) return;

    if (STATE.cart.length === 0) {
        container.innerHTML = `
            <div class="cart-empty-state">
                <span class="ces-icon">🛒</span>
                <strong>Keranjang Masih Kosong</strong>
                <p>Pilih menu makanan atau minuman dari katalog di sebelah kiri untuk memulai pesanan.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = STATE.cart.map(item => `
        <div class="cart-item-row" data-id="${item.product.id}">
            <div class="cir-top">
                <div class="cir-info">
                    <strong>${item.product.name}</strong>
                    ${item.note ? `<span class="cir-note">📝 ${item.note}</span>` : ''}
                </div>
                <button type="button" class="btn-remove-item" title="Hapus item">&times;</button>
            </div>
            <div class="cir-bottom">
                <span class="cir-price">${formatRupiah(item.product.price)}</span>
                <div class="cir-actions">
                    <div class="qty-stepper-compact">
                        <button type="button" class="btn-step-sm btn-minus-cart">-</button>
                        <span class="qty-display-sm">${item.qty}</span>
                        <button type="button" class="btn-step-sm btn-plus-cart">+</button>
                    </div>
                    <strong class="cir-subtotal">${formatRupiah(item.qty * item.product.price)}</strong>
                </div>
            </div>
        </div>
    `).join("");

    // Event Listeners di Keranjang
    container.querySelectorAll(".cart-item-row").forEach(row => {
        const id = row.dataset.id;
        row.querySelector(".btn-minus-cart").onclick = () => updateCartQty(id, -1);
        row.querySelector(".btn-plus-cart").onclick = () => updateCartQty(id, 1);
        row.querySelector(".btn-remove-item").onclick = () => removeFromCart(id);
    });
}

function renderCustomerSelectDropdown() {
    const select = document.getElementById("cart-customer-select");
    if (!select) return;

    select.innerHTML = STATE.customers.map(c => `
        <option value="${c.id}" ${STATE.selectedCustomer?.id === c.id ? 'selected' : ''}>
            ${c.name} ${c.phone && c.phone !== '-' ? `(${c.phone})` : ''}
        </option>
    `).join("");

    select.onchange = () => {
        const found = STATE.customers.find(c => c.id === select.value);
        if (found) {
            STATE.selectedCustomer = found;
        }
    };
}

/* ==========================================================================
   9, 10, 11. PROSES & KONFIRMASI PEMBAYARAN
   ========================================================================== */
function openPaymentModal() {
    if (STATE.cart.length === 0) {
        showToast("Keranjang belanja masih kosong!", "warning");
        return;
    }

    const totals = calculateCartTotals();
    const customer = STATE.selectedCustomer || { name: "Pelanggan Umum" };

    // Set Total Display
    document.getElementById("pay-modal-total-display").textContent = formatRupiah(totals.grandTotal);
    document.getElementById("pay-modal-customer-name").textContent = customer.name;
    document.getElementById("qris-amount-display").textContent = formatRupiah(totals.grandTotal);

    // Render Mini Items List
    const itemsListContainer = document.getElementById("pay-modal-items-list");
    itemsListContainer.innerHTML = STATE.cart.map(item => `
        <div class="piml-row">
            <span>${item.qty}x ${item.product.name}</span>
            <strong>${formatRupiah(item.qty * item.product.price)}</strong>
        </div>
    `).join("") + (totals.discountAmount > 0 ? `
        <div class="piml-row text-pink">
            <span>Diskon</span>
            <strong>- ${formatRupiah(totals.discountAmount)}</strong>
        </div>
    ` : "");

    // Reset default payment method ke Tunai
    selectPaymentMethod("Tunai");

    // Quick Cash Uang Pas
    const cashInput = document.getElementById("input-cash-received");
    cashInput.value = totals.grandTotal;
    updateCashChange();

    openModal("modal-payment");
}

function selectPaymentMethod(method) {
    document.querySelectorAll(".pm-tile").forEach(tile => {
        if (tile.dataset.method === method) {
            tile.classList.add("active");
            tile.querySelector("input").checked = true;
        } else {
            tile.classList.remove("active");
        }
    });

    // Sembunyikan semua sub-section
    document.getElementById("method-section-tunai").classList.add("hidden");
    document.getElementById("method-section-qris").classList.add("hidden");
    document.getElementById("method-section-transfer").classList.add("hidden");
    document.getElementById("method-section-kasbon").classList.add("hidden");

    // Tampilkan section yang dipilih
    const section = document.getElementById(`method-section-${method.toLowerCase()}`);
    if (section) section.classList.remove("hidden");

    if (method === "Kasbon") {
        const custName = STATE.selectedCustomer ? STATE.selectedCustomer.name : "Pelanggan";
        document.getElementById("kasbon-target-customer").textContent =
            `Transaksi atas nama ${custName} akan dicatat ke buku piutang / kasbon Warung Hanisa.`;
    }
}
// ============================================================
// PRAKTIKUM A & B: CLIENT-SIDE ACCESSIBLE FORM & VALIDATION
// readFormData, validate, renderErrors
// ============================================================

function readFormData() {
    const selectedMethodInput = document.querySelector(
        'input[name="payment_method_radio"]:checked'
    );

    const cashInput = document.getElementById("input-cash-received");
    const nameInput = document.getElementById("inputNamaPemesan");
    const contactInput = document.getElementById("inputKontakPemesan");
    const dateInput = document.getElementById("inputTanggalTransaksi");
    const confirmInput = document.getElementById("inputKonfirmasiPesanan");
    const totals = calculateCartTotals();

    const customerName = STATE.selectedCustomer ? STATE.selectedCustomer.name : (nameInput ? nameInput.value.trim() : "");
    const customerPhone = STATE.selectedCustomer ? STATE.selectedCustomer.phone : (contactInput ? contactInput.value.trim() : "");

    return {
        cart: STATE.cart || [],
        paymentMethod: selectedMethodInput ? selectedMethodInput.value : "",
        cashReceived: Number(cashInput?.value) || 0,
        grandTotal: totals.grandTotal,
        customer: STATE.selectedCustomer,
        customerName: customerName,
        customerPhone: customerPhone,
        date: dateInput ? dateInput.value.trim() : new Date().toISOString().split("T")[0],
        confirmed: confirmInput ? confirmInput.checked : true
    };
}

function validate(data) {
    const errors = {};

    // 1. Keranjang tidak boleh kosong (minimal 1 item)
    if (!data.cart || data.cart.length === 0) {
        errors.cart = "Keranjang belanja masih kosong! Silakan pilih minimal 1 produk.";
    }

    // 2. Nama pemesan wajib diisi dan minimal 3 karakter
    if (!data.customerName || data.customerName.trim() === "") {
        errors.customer = "Nama pemesan wajib diisi.";
    } else if (data.customerName.trim().length < 3) {
        errors.customer = "Nama pemesan minimal 3 karakter.";
    }

    // 3. Validasi kontak pemesan jika diisi
    if (data.customerPhone && data.customerPhone.trim() !== "") {
        const cleanPhone = data.customerPhone.replace(/[\s\-\(\)\+]/g, "");
        if (!/^[0-9]{9,15}$/.test(cleanPhone)) {
            errors.contact = "Format nomor kontak tidak valid (contoh: 081234567890).";
        }
    }

    // 4. Tanggal transaksi harus valid
    if (!data.date || data.date === "" || isNaN(Date.parse(data.date))) {
        errors.date = "Tanggal transaksi harus valid.";
    }

    // 5. Metode pembayaran harus valid (Tunai atau QRIS)
    const validPaymentMethods = ["Tunai", "QRIS", "cash", "qris"];
    if (!validPaymentMethods.includes(data.paymentMethod)) {
        errors.paymentMethod = "Metode pembayaran harus dipilih (Tunai atau QRIS).";
    }

    // 6. Nominal tunai wajib diisi dan tidak boleh kurang dari total
    if (data.paymentMethod === "Tunai" || data.paymentMethod === "cash") {
        if (data.cashReceived <= 0) {
            errors.cashReceived = "Nominal uang diterima harus diisi untuk pembayaran Tunai.";
        } else if (data.cashReceived < data.grandTotal) {
            errors.cashReceived = `Uang yang diterima kurang ${formatRupiah(data.grandTotal - data.cashReceived)}.`;
        }
    }

    // 7. Checkbox konfirmasi harus dicentang jika elemen ada
    if (document.getElementById("inputKonfirmasiPesanan") && !data.confirmed) {
        errors.confirmed = "Harap centang konfirmasi bahwa data pesanan sudah benar.";
    }

    // 8. Jumlah setiap item produk harus minimal 1
    if (data.cart && data.cart.length > 0) {
        const invalidItem = data.cart.find(
            item => !Number.isInteger(Number(item.qty)) || Number(item.qty) < 1
        );
        if (invalidItem) {
            errors.qty = "Jumlah setiap produk harus minimal 1.";
        }
    }

    return errors;
}

function renderErrors(errors) {
    // Pesan error nominal pembayaran
    const cashError = document.getElementById("nominalCashError");
    if (cashError) {
        cashError.textContent = errors.cashReceived || "";
    }
    const cashInput = document.getElementById("input-cash-received");
    if (cashInput) {
        if (errors.cashReceived) {
            cashInput.classList.add("is-invalid");
            cashInput.setAttribute("aria-invalid", "true");
        } else {
            cashInput.classList.remove("is-invalid");
            cashInput.setAttribute("aria-invalid", "false");
        }
    }

    // Pesan error nama pemesan
    const nameError = document.getElementById("nama-error") || document.getElementById("customerError");
    if (nameError) {
        nameError.textContent = errors.customer || "";
    }
    const nameInput = document.getElementById("inputNamaPemesan");
    if (nameInput) {
        if (errors.customer) {
            nameInput.classList.add("is-invalid");
            nameInput.setAttribute("aria-invalid", "true");
        } else {
            nameInput.classList.remove("is-invalid");
            nameInput.setAttribute("aria-invalid", "false");
        }
    }

    // Tampilkan notifikasi toast jika tersedia
    if (typeof showToast === "function") {
        if (errors.cart) showToast(errors.cart, "warning");
        if (errors.customer) showToast(errors.customer, "warning");
        if (errors.paymentMethod) showToast(errors.paymentMethod, "warning");
        if (errors.cashReceived) showToast(errors.cashReceived, "error");
        if (errors.confirmed) showToast(errors.confirmed, "warning");
    }

    return Object.keys(errors).length === 0;
}

function updateCashChange() {
    const totals = calculateCartTotals();
    const cashInput = document.getElementById("input-cash-received");
    const changeDisplay = document.getElementById("change-amount-display");
    const changeAlert = document.getElementById("change-alert-message");
    const changeLabel = document.getElementById("change-status-label");

    const cashReceived = Number(cashInput.value) || 0;
    const change = cashReceived - totals.grandTotal;

    if (change < 0) {
        changeLabel.textContent = "Kurang:";
        changeDisplay.textContent = formatRupiah(Math.abs(change));
        changeDisplay.style.color = "var(--danger)";
        changeAlert.classList.remove("hidden");
        changeAlert.textContent = `⚠️ Uang yang diterima kurang ${formatRupiah(Math.abs(change))}!`;
    } else {
        changeLabel.textContent = "Kembalian:";
        changeDisplay.textContent = formatRupiah(change);
        changeDisplay.style.color = "var(--success)";
        changeAlert.classList.add("hidden");
    }
}

/**
 * 11. Konfirmasi Pembayaran
 */
function handleConfirmPayment() {
    const totals = calculateCartTotals();
    const selectedMethodInput = document.querySelector('input[name="payment_method_radio"]:checked');
    const paymentMethod = selectedMethodInput ? selectedMethodInput.value : "Tunai";

    let cashReceived = totals.grandTotal;
    let changeAmount = 0;

    // Validasi Tunai
    if (paymentMethod === "Tunai") {
        cashReceived = Number(document.getElementById("input-cash-received").value) || 0;
        if (cashReceived < totals.grandTotal) {
            showToast("Uang yang diterima kurang dari total pembayaran!", "error");
            return;
        }
        changeAmount = cashReceived - totals.grandTotal;
    }

    // Validasi Kasbon
    if (paymentMethod === "Kasbon") {
        if (!STATE.selectedCustomer || STATE.selectedCustomer.name === "Pelanggan Umum") {
            showToast("Kasbon / Hutang hanya bisa dicatat untuk pelanggan terdaftar!", "warning");
            return;
        }
    }

    // Validasi Stok Sebelum Eksekusi
    for (const item of STATE.cart) {
        const prod = STATE.products.find(p => p.id === item.product.id);
        if (!prod || prod.stock < item.qty) {
            showToast(`Gagal! Stok ${item.product.name} tidak mencukupi (sisa ${prod ? prod.stock : 0}).`, "error");
            return;
        }
    }

    // Buat Objek Transaksi (13. Simpan Data Transaksi)
    const invoiceId = generateInvoiceNumber();
    const transaction = {
        id: invoiceId,
        createdAt: new Date().toISOString(),
        cashierName: STATE.currentUser?.fullName || "Hanisa",
        customerId: STATE.selectedCustomer?.id || "CUST-001",
        customerName: STATE.selectedCustomer?.name || "Pelanggan Umum",
        items: STATE.cart.map(i => ({
            id: i.product.id,
            name: i.product.name,
            qty: i.qty,
            unitPrice: i.product.price,
            costPrice: i.product.costPrice || 0,
            subtotal: i.qty * i.product.price,
            note: i.note || ""
        })),
        subtotal: totals.subtotal,
        discount: totals.discountAmount,
        grandTotal: totals.grandTotal,
        paymentMethod: paymentMethod,
        cashReceived: cashReceived,
        changeAmount: changeAmount
    };

    // 14. Perbarui Produk Stok
    updateProductStocksOnPurchase(STATE.cart);

    // Simpan Transaksi ke LocalStorage (13. Simpan Data Transaksi)
    STATE.transactions.unshift(transaction);
    localStorage.setItem("hanisa_transactions", JSON.stringify(STATE.transactions));

    // Perbarui data pelanggan jika terdaftar
    if (STATE.selectedCustomer && STATE.selectedCustomer.id !== "CUST-001") {
        const cust = STATE.customers.find(c => c.id === STATE.selectedCustomer.id);
        if (cust) {
            cust.totalOrders = (cust.totalOrders || 0) + 1;
            cust.totalSpent = (cust.totalSpent || 0) + totals.grandTotal;
            localStorage.setItem("hanisa_customers", JSON.stringify(STATE.customers));
        }
    }

    // Tutup Modal Pembayaran
    closeModal("modal-payment");

    // Efek Suara Cash Register Ding & Notifikasi Berhasil
    playCashChime();

    // 12. Notifikasi Pembayaran Berhasil & Struk
    showReceiptModal(transaction);

    // Reset Keranjang
    STATE.cart = [];
    STATE.discount = { type: "nominal", value: 0 };
    renderCart();

    // Refresh Tampilan Dashboard dan POS
    renderPOSProducts();
    renderDashboard();
}

/* ==========================================================================
   14. PERBARUI PRODUK STOK (REALTIME & OTOMATIS)
   ========================================================================== */
function updateProductStocksOnPurchase(purchasedItems) {
    purchasedItems.forEach(item => {
        const prod = STATE.products.find(p => p.id === item.product.id);
        if (prod) {
            prod.stock = Math.max(0, Number(prod.stock) - Number(item.qty));
        }
    });

    localStorage.setItem("hanisa_products", JSON.stringify(STATE.products));
    showToast("📦 Stok produk telah otomatis diperbarui!", "info");
}

/* ==========================================================================
   12. NOTIFIKASI PEMBAYARAN BERHASIL & CETAK STRUK THERMAL
   ========================================================================== */
function showReceiptModal(tx) {
    STATE.currentCheckout = tx;

    document.getElementById("rec-invoice-id").textContent = tx.id;
    document.getElementById("rec-datetime").textContent = `${formatTanggalPendek(tx.createdAt)} ${formatWaktu(tx.createdAt)}`;
    document.getElementById("rec-cashier-name").textContent = tx.cashierName;
    document.getElementById("rec-customer-name").textContent = tx.customerName;

    // Items list
    const itemsContainer = document.getElementById("rec-items-list");
    itemsContainer.innerHTML = tx.items.map(item => `
        <div class="rec-item-row">
            <span class="rec-item-top">${item.name} ${item.note ? `(${item.note})` : ''}</span>
            <div class="rec-item-bottom">
                <span>${item.qty} x ${formatRupiah(item.unitPrice)}</span>
                <strong>${formatRupiah(item.subtotal)}</strong>
            </div>
        </div>
    `).join("");

    // Totals
    document.getElementById("rec-subtotal").textContent = formatRupiah(tx.subtotal);
    const discRow = document.getElementById("rec-discount-row");
    if (tx.discount > 0) {
        discRow.classList.remove("hidden");
        document.getElementById("rec-discount").textContent = `- ${formatRupiah(tx.discount)}`;
    } else {
        discRow.classList.add("hidden");
    }

    document.getElementById("rec-grand-total").textContent = formatRupiah(tx.grandTotal);
    document.getElementById("rec-method-label").textContent = `${tx.paymentMethod} (Diterima):`;
    document.getElementById("rec-cash-received").textContent = formatRupiah(tx.cashReceived);
    document.getElementById("rec-change").textContent = formatRupiah(tx.changeAmount);

    openModal("modal-success-receipt");
}

/**
 * Cetak Struk ke Printer Thermal / Browser Print
 */
function handlePrintReceipt() {
    window.print();
}

/**
 * Kirim Struk melalui WhatsApp
 */
function handleShareWhatsApp() {
    const tx = STATE.currentCheckout;
    if (!tx) return;

    let text = `*WARUNG HANISA - STRUK PEMBAYARAN*\n`;
    text += `No. Invoice: ${tx.id}\n`;
    text += `Tanggal: ${formatTanggalPendek(tx.createdAt)} ${formatWaktu(tx.createdAt)}\n`;
    text += `Kasir: ${tx.cashierName} | Pelanggan: ${tx.customerName}\n`;
    text += `----------------------------------------\n`;
    tx.items.forEach(item => {
        text += `${item.name} (${item.qty}x) = ${formatRupiah(item.subtotal)}\n`;
    });
    text += `----------------------------------------\n`;
    text += `Subtotal: ${formatRupiah(tx.subtotal)}\n`;
    if (tx.discount > 0) text += `Diskon: -${formatRupiah(tx.discount)}\n`;
    text += `*TOTAL BAYAR: ${formatRupiah(tx.grandTotal)}*\n`;
    text += `Metode: ${tx.paymentMethod}\n`;
    text += `Uang Diterima: ${formatRupiah(tx.cashReceived)}\n`;
    text += `Kembalian: ${formatRupiah(tx.changeAmount)}\n`;
    text += `----------------------------------------\n`;
    text += `Terima Kasih atas kunjungan Anda di Warung Hanisa! 🙏🍜`;

    // Cari nomor WA pelanggan jika ada
    let phone = "";
    if (tx.customerId) {
        const c = STATE.customers.find(x => x.id === tx.customerId);
        if (c && c.phone && c.phone !== "-") {
            phone = c.phone.replace(/[^0-9]/g, "");
            if (phone.startsWith("0")) phone = "62" + phone.slice(1);
        }
    }

    const waUrl = phone
        ? `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(text)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`;

    window.open(waUrl, "_blank");
}

/* ==========================================================================
   3. DATA PRODUK (CRUD MENU, HARGA, STOK, RESTOCK)
   ========================================================================== */
function renderProductsTable() {
    const tbody = document.getElementById("products-tbody");
    if (!tbody) return;

    const searchInput = document.getElementById("product-search-table").value.toLowerCase().trim();
    const categoryFilter = document.getElementById("product-filter-category").value;
    const stockFilter = document.getElementById("product-filter-stock").value;

    let filtered = [...STATE.products];

    if (categoryFilter !== "Semua") {
        filtered = filtered.filter(p => p.category === categoryFilter);
    }

    if (stockFilter === "Tersedia") {
        filtered = filtered.filter(p => Number(p.stock) > 5);
    } else if (stockFilter === "Menipis") {
        filtered = filtered.filter(p => Number(p.stock) > 0 && Number(p.stock) <= 5);
    } else if (stockFilter === "Habis") {
        filtered = filtered.filter(p => Number(p.stock) <= 0);
    }

    if (searchInput) {
        filtered = filtered.filter(p =>
            p.name.toLowerCase().includes(searchInput) ||
            p.id.toLowerCase().includes(searchInput) ||
            p.category.toLowerCase().includes(searchInput)
        );
    }

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="text-center py-4 text-muted">
                    Tidak ada data produk yang sesuai.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map(p => {
        const stock = Number(p.stock) || 0;
        const profit = (p.price || 0) - (p.costPrice || 0);

        let stockBadge = `<span class="badge-stock stock-safe">${stock} ${p.unit || 'porsi'}</span>`;
        if (stock <= 0) {
            stockBadge = `<span class="badge-stock stock-out">Habis (0)</span>`;
        } else if (stock <= 5) {
            stockBadge = `<span class="badge-stock stock-warn">Menipis (${stock})</span>`;
        }

        return `
            <tr>
                <td>
                    <img src="${p.image || 'images/bakso.jpg'}" alt="${p.name}" class="tbl-img" onerror="this.src='images/bakso.jpg'">
                </td>
                <td><code>${p.id}</code></td>
                <td><strong>${p.name}</strong></td>
                <td><span class="pill-badge pill-blue">${p.category}</span></td>
                <td>${formatRupiah(p.costPrice || 0)}</td>
                <td><strong class="text-pink">${formatRupiah(p.price)}</strong></td>
                <td><span class="text-success font-bold">+${formatRupiah(profit)}</span></td>
                <td>${stockBadge}</td>
                <td>
                    <button type="button" class="btn-quick-restock" data-id="${p.id}">
                        + Restock
                    </button>
                </td>
                <td>
                    <div class="table-actions">
                        <button type="button" class="btn-tbl-action btn-tbl-edit btn-edit-product" data-id="${p.id}">
                            ✏️ Edit
                        </button>
                        <button type="button" class="btn-tbl-action btn-tbl-delete btn-delete-product" data-id="${p.id}">
                            🗑️ Hapus
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join("");

    // Quick restock button
    tbody.querySelectorAll(".btn-quick-restock").forEach(btn => {
        btn.addEventListener("click", () => {
            const prod = STATE.products.find(x => x.id === btn.dataset.id);
            if (prod) openQuickRestockModal(prod);
        });
    });

    // Edit Product
    tbody.querySelectorAll(".btn-edit-product").forEach(btn => {
        btn.addEventListener("click", () => {
            const prod = STATE.products.find(x => x.id === btn.dataset.id);
            if (prod) openProductFormModal(prod);
        });
    });

    // Delete Product
    tbody.querySelectorAll(".btn-delete-product").forEach(btn => {
        btn.addEventListener("click", () => {
            const prod = STATE.products.find(x => x.id === btn.dataset.id);
            if (!prod) return;
            if (confirm(`Hapus produk "${prod.name}" dari sistem Warung Hanisa?`)) {
                STATE.products = STATE.products.filter(p => p.id !== prod.id);
                localStorage.setItem("hanisa_products", JSON.stringify(STATE.products));
                showToast(`Produk "${prod.name}" berhasil dihapus.`, "info");
                renderProductsTable();
                renderPOSProducts();
                renderDashboard();
            }
        });
    });
}

function openProductFormModal(product = null) {
    const modal = document.getElementById("modal-product-form");
    const titleEl = document.getElementById("product-modal-title");
    const idInput = document.getElementById("form-product-id");
    const nameInput = document.getElementById("form-product-name");
    const catSelect = document.getElementById("form-product-category");
    const unitInput = document.getElementById("form-product-unit");
    const costInput = document.getElementById("form-product-cost");
    const priceInput = document.getElementById("form-product-price");
    const stockInput = document.getElementById("form-product-stock");
    const imgSelect = document.getElementById("form-product-image-select");
    const customImgInput = document.getElementById("form-product-custom-image");
    const descInput = document.getElementById("form-product-desc");

    if (product) {
        titleEl.textContent = "Edit Produk Menu";
        idInput.value = product.id;
        nameInput.value = product.name;
        catSelect.value = product.category;
        unitInput.value = product.unit || "Porsi";
        costInput.value = product.costPrice || 0;
        priceInput.value = product.price;
        stockInput.value = product.stock;
        descInput.value = product.description || "";

        // Check image select
        let matched = false;
        for (let opt of imgSelect.options) {
            if (opt.value === product.image) {
                imgSelect.value = product.image;
                matched = true;
                break;
            }
        }
        if (!matched && product.image) {
            imgSelect.value = "custom";
            customImgInput.classList.remove("hidden");
            customImgInput.value = product.image;
        } else {
            customImgInput.classList.add("hidden");
        }
    } else {
        titleEl.textContent = "Tambah Produk Menu Baru";
        idInput.value = "";
        nameInput.value = "";
        catSelect.value = "Makanan";
        unitInput.value = "Porsi";
        costInput.value = "";
        priceInput.value = "";
        stockInput.value = "20";
        imgSelect.value = "images/bakso.jpg";
        customImgInput.classList.add("hidden");
        customImgInput.value = "";
        descInput.value = "";
    }

    openModal("modal-product-form");
}

function handleSaveProduct(e) {
    e.preventDefault();
    const id = document.getElementById("form-product-id").value;
    const name = document.getElementById("form-product-name").value.trim();
    const category = document.getElementById("form-product-category").value;
    const unit = document.getElementById("form-product-unit").value.trim() || "Porsi";
    const costPrice = Number(document.getElementById("form-product-cost").value) || 0;
    const price = Number(document.getElementById("form-product-price").value) || 0;
    const stock = Number(document.getElementById("form-product-stock").value) || 0;
    const imgSelect = document.getElementById("form-product-image-select").value;
    const customImg = document.getElementById("form-product-custom-image").value.trim();
    const desc = document.getElementById("form-product-desc").value.trim();

    let image = imgSelect === "custom" && customImg ? customImg : imgSelect;

    if (!name || price <= 0) {
        showToast("Nama produk dan harga jual wajib diisi dengan benar!", "error");
        return;
    }

    if (id) {
        // Edit
        const prod = STATE.products.find(p => p.id === id);
        if (prod) {
            prod.name = name;
            prod.category = category;
            prod.unit = unit;
            prod.costPrice = costPrice;
            prod.price = price;
            prod.stock = stock;
            prod.image = image;
            prod.description = desc;
            showToast(`Produk "${name}" berhasil diperbarui.`, "success");
        }
    } else {
        // Tambah Baru
        const newProduct = {
            id: generateId("PRD"),
            name,
            category,
            unit,
            costPrice,
            price,
            stock,
            image,
            description: desc
        };
        STATE.products.unshift(newProduct);
        showToast(`Produk baru "${name}" berhasil ditambahkan!`, "success");
    }

    localStorage.setItem("hanisa_products", JSON.stringify(STATE.products));
    closeModal("modal-product-form");
    renderProductsTable();
    renderPOSProducts();
    renderDashboard();
}

/**
 * Modal Restock Cepat
 */
let currentRestockProd = null;
function openQuickRestockModal(product) {
    currentRestockProd = product;
    document.getElementById("restock-product-name").textContent = product.name;
    document.getElementById("restock-current-stock").textContent = `${product.stock} ${product.unit || 'porsi'}`;
    const inputQty = document.getElementById("input-restock-qty");
    inputQty.value = 10;

    openModal("modal-quick-restock");
}

function handleSaveQuickRestock() {
    if (!currentRestockProd) return;
    const addQty = Number(document.getElementById("input-restock-qty").value) || 0;
    if (addQty <= 0) {
        showToast("Masukkan jumlah restock yang valid!", "warning");
        return;
    }

    const prod = STATE.products.find(p => p.id === currentRestockProd.id);
    if (prod) {
        prod.stock = (Number(prod.stock) || 0) + addQty;
        localStorage.setItem("hanisa_products", JSON.stringify(STATE.products));
        showToast(`Stok ${prod.name} berhasil ditambah +${addQty}!`, "success");
    }

    closeModal("modal-quick-restock");
    renderProductsTable();
    renderPOSProducts();
    renderDashboard();
}

/* ==========================================================================
   4. DATA PELANGGAN (CRUD PELANGGAN, WA, TOTAL TRANSAKSI)
   ========================================================================== */
function renderCustomersTable() {
    const tbody = document.getElementById("customers-tbody");
    if (!tbody) return;

    const searchInput = document.getElementById("customer-search-table").value.toLowerCase().trim();

    let filtered = [...STATE.customers];
    if (searchInput) {
        filtered = filtered.filter(c =>
            c.name.toLowerCase().includes(searchInput) ||
            (c.phone && c.phone.includes(searchInput)) ||
            (c.address && c.address.toLowerCase().includes(searchInput))
        );
    }

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    Tidak ada data pelanggan yang cocok.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map(c => `
        <tr>
            <td><code>${c.id}</code></td>
            <td><strong>${c.name}</strong></td>
            <td>
                ${c.phone && c.phone !== '-' ? `
                    <a href="https://wa.me/${c.phone.replace(/[^0-9]/g, '')}" target="_blank" class="btn-link" title="Kirim WhatsApp">
                        📱 ${c.phone}
                    </a>
                ` : '-'}
            </td>
            <td>${c.address || '-'}</td>
            <td><span class="pill-badge pill-blue">${c.totalOrders || 0}x Order</span></td>
            <td><strong class="text-pink">${formatRupiah(c.totalSpent || 0)}</strong></td>
            <td>
                <div class="table-actions">
                    <button type="button" class="btn-tbl-action btn-tbl-edit btn-edit-cust" data-id="${c.id}">
                        ✏️ Edit
                    </button>
                    ${c.id !== "CUST-001" ? `
                        <button type="button" class="btn-tbl-action btn-tbl-delete btn-del-cust" data-id="${c.id}">
                            🗑️ Hapus
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join("");

    tbody.querySelectorAll(".btn-edit-cust").forEach(btn => {
        btn.addEventListener("click", () => {
            const cust = STATE.customers.find(c => c.id === btn.dataset.id);
            if (cust) openCustomerFormModal(cust);
        });
    });

    tbody.querySelectorAll(".btn-del-cust").forEach(btn => {
        btn.addEventListener("click", () => {
            const cust = STATE.customers.find(c => c.id === btn.dataset.id);
            if (!cust) return;
            if (confirm(`Hapus pelanggan "${cust.name}"?`)) {
                STATE.customers = STATE.customers.filter(c => c.id !== cust.id);
                localStorage.setItem("hanisa_customers", JSON.stringify(STATE.customers));
                showToast(`Pelanggan "${cust.name}" dihapus.`, "info");
                renderCustomersTable();
                renderCustomerSelectDropdown();
                renderDashboard();
            }
        });
    });
}

function openCustomerFormModal(customer = null) {
    const titleEl = document.getElementById("customer-modal-title");
    const idInput = document.getElementById("form-customer-id");
    const nameInput = document.getElementById("form-customer-name");
    const phoneInput = document.getElementById("form-customer-phone");
    const addrInput = document.getElementById("form-customer-address");
    const notesInput = document.getElementById("form-customer-notes");

    if (customer) {
        titleEl.textContent = "Edit Data Pelanggan";
        idInput.value = customer.id;
        nameInput.value = customer.name;
        phoneInput.value = customer.phone || "";
        addrInput.value = customer.address || "";
        notesInput.value = customer.notes || "";
    } else {
        titleEl.textContent = "Tambah Pelanggan Baru";
        idInput.value = "";
        nameInput.value = "";
        phoneInput.value = "";
        addrInput.value = "";
        notesInput.value = "";
    }

    openModal("modal-customer-form");
}

function handleSaveCustomer(e) {
    e.preventDefault();
    const id = document.getElementById("form-customer-id").value;
    const name = document.getElementById("form-customer-name").value.trim();
    const phone = document.getElementById("form-customer-phone").value.trim();
    const address = document.getElementById("form-customer-address").value.trim();
    const notes = document.getElementById("form-customer-notes").value.trim();

    if (!name) {
        showToast("Nama pelanggan wajib diisi!", "error");
        return;
    }

    if (id) {
        const cust = STATE.customers.find(c => c.id === id);
        if (cust) {
            cust.name = name;
            cust.phone = phone || "-";
            cust.address = address || "-";
            cust.notes = notes;
            showToast(`Data pelanggan "${name}" diperbarui.`, "success");
        }
    } else {
        const newCust = {
            id: generateId("CUST"),
            name,
            phone: phone || "-",
            address: address || "-",
            notes,
            totalOrders: 0,
            totalSpent: 0
        };
        STATE.customers.push(newCust);
        STATE.selectedCustomer = newCust;
        showToast(`Pelanggan baru "${name}" berhasil ditambahkan!`, "success");
    }

    localStorage.setItem("hanisa_customers", JSON.stringify(STATE.customers));
    closeModal("modal-customer-form");
    renderCustomersTable();
    renderCustomerSelectDropdown();
    renderDashboard();
}

/* ==========================================================================
   13. SIMPAN DATA TRANSAKSI (RIWAYAT & LAPORAN PENJUALAN)
   ========================================================================== */
function renderTransactionsTable() {
    const tbody = document.getElementById("transactions-tbody");
    if (!tbody) return;

    const search = document.getElementById("tx-search-input").value.toLowerCase().trim();
    const filterPayment = document.getElementById("tx-filter-payment").value;
    const filterDate = document.getElementById("tx-filter-date").value;

    let filtered = [...STATE.transactions];

    if (filterPayment !== "Semua") {
        filtered = filtered.filter(tx => tx.paymentMethod === filterPayment);
    }

    if (filterDate) {
        filtered = filtered.filter(tx => tx.createdAt && tx.createdAt.startsWith(filterDate));
    }

    if (search) {
        filtered = filtered.filter(tx =>
            tx.id.toLowerCase().includes(search) ||
            (tx.customerName && tx.customerName.toLowerCase().includes(search))
        );
    }

    // Update Summary Bar
    const totalOmzet = filtered.reduce((sum, tx) => sum + (tx.grandTotal || 0), 0);
    const totalCount = filtered.length;
    const average = totalCount > 0 ? Math.round(totalOmzet / totalCount) : 0;

    document.getElementById("tx-sum-total-omzet").textContent = formatRupiah(totalOmzet);
    document.getElementById("tx-sum-total-count").textContent = `${totalCount} Transaksi`;
    document.getElementById("tx-sum-average").textContent = formatRupiah(average);

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    Tidak ada riwayat transaksi yang ditemukan.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map(tx => {
        const itemsSummary = tx.items.map(i => `${i.qty}x ${i.name}`).join(", ");
        return `
            <tr>
                <td><strong>${tx.id}</strong></td>
                <td>
                    <small>${formatTanggalPendek(tx.createdAt)}</small><br>
                    <strong>${formatWaktu(tx.createdAt)}</strong>
                </td>
                <td>${tx.customerName || 'Umum'}</td>
                <td><small>${itemsSummary}</small></td>
                <td>
                    <span class="badge-payment pay-${tx.paymentMethod.toLowerCase()}">
                        ${tx.paymentMethod}
                    </span>
                </td>
                <td><strong class="text-pink">${formatRupiah(tx.grandTotal)}</strong></td>
                <td><small>${tx.cashierName || 'Hanisa'}</small></td>
                <td>
                    <div class="table-actions">
                        <button type="button" class="btn-tbl-action btn-tbl-edit btn-tx-view" data-id="${tx.id}" title="Lihat Struk">
                            📄 Struk
                        </button>
                        <button type="button" class="btn-tbl-action btn-tbl-delete btn-tx-del" data-id="${tx.id}" title="Hapus Transaksi">
                            🗑️
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join("");

    tbody.querySelectorAll(".btn-tx-view").forEach(btn => {
        btn.addEventListener("click", () => {
            const tx = STATE.transactions.find(x => x.id === btn.dataset.id);
            if (tx) showReceiptModal(tx);
        });
    });

    tbody.querySelectorAll(".btn-tx-del").forEach(btn => {
        btn.addEventListener("click", () => {
            const tx = STATE.transactions.find(x => x.id === btn.dataset.id);
            if (!tx) return;
            if (confirm(`Hapus transaksi ${tx.id}? Data tidak dapat dikembalikan.`)) {
                STATE.transactions = STATE.transactions.filter(t => t.id !== tx.id);
                localStorage.setItem("hanisa_transactions", JSON.stringify(STATE.transactions));
                showToast(`Transaksi ${tx.id} berhasil dihapus.`, "info");
                renderTransactionsTable();
                renderDashboard();
            }
        });
    });
}

function handleExportCSV() {
    if (STATE.transactions.length === 0) {
        showToast("Belum ada data transaksi untuk diexport!", "warning");
        return;
    }

    const headers = [
        "No. Invoice",
        "Waktu Transaksi",
        "Kasir",
        "Pelanggan",
        "Metode Pembayaran",
        "Subtotal",
        "Diskon",
        "Total Pembayaran",
        "Rincian Menu"
    ];

    const rows = [headers];
    STATE.transactions.forEach(tx => {
        const itemsStr = tx.items.map(i => `${i.qty}x ${i.name} (@${i.unitPrice})`).join(" | ");
        rows.push([
            tx.id,
            tx.createdAt,
            tx.cashierName,
            tx.customerName,
            tx.paymentMethod,
            tx.subtotal,
            tx.discount,
            tx.grandTotal,
            itemsStr
        ]);
    });

    const filename = `Laporan_Transaksi_Warung_Hanisa_${getTanggalHariIni()}.csv`;
    exportToCSV(filename, rows);
    showToast("Laporan transaksi berhasil diunduh (CSV)!", "success");
}

/* ==========================================================================
   15. KELUAR (LOGOUT)
   ========================================================================== */
function handleLogout() {
    if (confirm("Apakah Anda yakin ingin keluar dari sistem kasir Warung Hanisa?")) {
        STATE.isLoggedIn = false;
        STATE.currentUser = null;
        localStorage.removeItem("hanisa_is_logged_in");
        localStorage.removeItem("hanisa_current_user");

        showToast("Anda telah keluar dari akun kasir.", "info");
        checkAuthSession();
    }
}

/* ==========================================================================
   MODAL HELPERS
   ========================================================================== */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove("hidden");
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add("hidden");
    }
}

/* ==========================================================================
   EVENT LISTENERS SETUP
   ========================================================================== */
function initEventListeners() {
    // 1. Auth Events
    const registerForm = document.getElementById("register-form");
    if (registerForm) registerForm.addEventListener("submit", handleRegistration);

    const loginForm = document.getElementById("login-form");
    if (loginForm) loginForm.addEventListener("submit", handleLogin);

    const btnSwitchToLogin = document.getElementById("switch-to-login");
    if (btnSwitchToLogin) {
        btnSwitchToLogin.addEventListener("click", () => {
            document.getElementById("auth-register-box").classList.add("hidden");
            document.getElementById("auth-login-box").classList.remove("hidden");
        });
    }

    const btnSwitchToReg = document.getElementById("switch-to-register");
    if (btnSwitchToReg) {
        btnSwitchToReg.addEventListener("click", () => {
            document.getElementById("auth-login-box").classList.add("hidden");
            document.getElementById("auth-register-box").classList.remove("hidden");
        });
    }

    const btnTogglePwd = document.getElementById("btn-toggle-pwd");
    if (btnTogglePwd) {
        btnTogglePwd.addEventListener("click", () => {
            const input = document.getElementById("login-password");
            if (input.type === "password") {
                input.type = "text";
                btnTogglePwd.textContent = "Sembunyikan";
            } else {
                input.type = "password";
                btnTogglePwd.textContent = "Lihat";
            }
        });
    }

    // 2. Navigation
    document.querySelectorAll(".nav-btn[data-page]").forEach(btn => {
        btn.addEventListener("click", () => switchPage(btn.dataset.page));
    });

    // Topbar POS Button
    const topbarPosBtn = document.getElementById("topbar-btn-pos");
    if (topbarPosBtn) topbarPosBtn.addEventListener("click", () => switchPage("pos"));

    // Dashboard Shortcuts
    const dashOpenPos = document.getElementById("dash-btn-open-pos");
    if (dashOpenPos) dashOpenPos.addEventListener("click", () => switchPage("pos"));

    const dashAddProd = document.getElementById("dash-btn-add-product");
    if (dashAddProd) {
        dashAddProd.addEventListener("click", () => {
            switchPage("produk");
            openProductFormModal();
        });
    }

    const dashManageStock = document.getElementById("dash-btn-manage-stock");
    if (dashManageStock) dashManageStock.addEventListener("click", () => switchPage("produk"));

    const dashViewAllTx = document.getElementById("dash-view-all-tx");
    if (dashViewAllTx) dashViewAllTx.addEventListener("click", () => switchPage("transaksi"));

    // Sidebar Mobile Toggle
    const sidebarToggleBtn = document.getElementById("sidebar-toggle-btn");
    const appSidebar = document.getElementById("app-sidebar");
    if (sidebarToggleBtn && appSidebar) {
        sidebarToggleBtn.addEventListener("click", () => {
            appSidebar.classList.toggle("sidebar-open");
        });
    }

    // 15. Logout
    document.getElementById("btn-sidebar-logout")?.addEventListener("click", handleLogout);
    document.getElementById("btn-topbar-logout")?.addEventListener("click", handleLogout);

    // Modal Close Buttons (data-close-modal)
    document.querySelectorAll("[data-close-modal]").forEach(btn => {
        btn.addEventListener("click", () => {
            closeModal(btn.dataset.closeModal);
        });
    });

    // POS Search & Filter
    const posSearchInput = document.getElementById("pos-search-input");
    const posClearSearch = document.getElementById("pos-clear-search");
    if (posSearchInput) {
        posSearchInput.addEventListener("input", () => {
            STATE.searchPOSQuery = posSearchInput.value;
            if (posClearSearch) {
                if (posSearchInput.value) {
                    posClearSearch.classList.remove("hidden");
                } else {
                    posClearSearch.classList.add("hidden");
                }
            }
            renderPOSProducts();
        });
    }

    if (posClearSearch) {
        posClearSearch.addEventListener("click", () => {
            posSearchInput.value = "";
            STATE.searchPOSQuery = "";
            posClearSearch.classList.add("hidden");
            renderPOSProducts();
        });
    }

    // POS Category Tabs
    // Pulihkan kategori terakhir
document.querySelectorAll(".cat-pill").forEach(pill => {
    if (pill.dataset.category === STATE.activeCategory) {
        pill.classList.add("active");
    } else {
        pill.classList.remove("active");
    }
});
    document.querySelectorAll(".cat-pill").forEach(pill => {
        pill.addEventListener("click", () => {
            document.querySelectorAll(".cat-pill").forEach(p => p.classList.remove("active"));
                pill.classList.add("active");

                 STATE.activeCategory = pill.dataset.category;

          // Simpan kategori terakhir
        localStorage.setItem("hanisa_active_category", STATE.activeCategory);

        renderPOSProducts();
    });
});

    // Clear Cart
    document.getElementById("btn-clear-cart")?.addEventListener("click", clearCart);

    // Quick Add Customer from Cart
    document.getElementById("btn-quick-add-customer")?.addEventListener("click", () => {
        openCustomerFormModal();
    });

    // Diskon Drawer
    const btnToggleDiscount = document.getElementById("btn-toggle-discount");
    const discountDrawer = document.getElementById("discount-input-drawer");
    if (btnToggleDiscount && discountDrawer) {
        btnToggleDiscount.addEventListener("click", () => {
            discountDrawer.classList.toggle("hidden");
        });
    }

    // Discount Quick Buttons
    document.querySelectorAll(".discount-quick-btns .btn-chip").forEach(chip => {
        chip.addEventListener("click", () => {
            const val = parseInt(chip.dataset.disc, 10);
            if (val === 5 || val === 10) {
                STATE.discount = { type: "percent", value: val };
            } else {
                STATE.discount = { type: "nominal", value: val };
            }
            discountDrawer.classList.add("hidden");
            renderCart();
            showToast(`Diskon diterapkan: ${val > 10 ? formatRupiah(val) : val + '%'}`, "info");
        });
    });

    const btnApplyDisc = document.getElementById("btn-apply-discount");
    if (btnApplyDisc) {
        btnApplyDisc.addEventListener("click", () => {
            const customVal = Number(document.getElementById("custom-discount-amount").value) || 0;
            STATE.discount = { type: "nominal", value: customVal };
            discountDrawer.classList.add("hidden");
            renderCart();
            showToast(`Diskon diterapkan: ${formatRupiah(customVal)}`, "info");
        });
    }

    // Checkout Modal
    document.getElementById("btn-open-payment-modal")?.addEventListener("click", openPaymentModal);

    // Payment Methods Radio Tiles
    document.querySelectorAll(".pm-tile").forEach(tile => {
        tile.addEventListener("click", () => {
            selectPaymentMethod(tile.dataset.method);
        });
    });

    // Cash Received Realtime Input
    const cashInput = document.getElementById("input-cash-received");
    if (cashInput) {
        cashInput.addEventListener("input", updateCashChange);
    }

    // Quick Cash Buttons
    document.getElementById("btn-cash-pas")?.addEventListener("click", () => {
        const totals = calculateCartTotals();
        cashInput.value = totals.grandTotal;
        updateCashChange();
    });

    document.querySelectorAll("#quick-cash-buttons .btn-cash-chip[data-amount]").forEach(btn => {
        btn.addEventListener("click", () => {
            const amt = Number(btn.dataset.amount) || 0;
            cashInput.value = amt;
            updateCashChange();
        });
    });

    // Confirm Payment
    document.getElementById("btn-confirm-payment")?.addEventListener("click", handleConfirmPayment);

    // ============================================================
// PRAKTIKUM 6 - NOMOR 5
// Validasi sebelum proses pembayaran
// ============================================================

const confirmPaymentButton = document.getElementById(
    "btn-confirm-payment"
);

if (confirmPaymentButton) {
    confirmPaymentButton.addEventListener(
        "click",
        (e) => {
            const formData = readFormData();
            const errors = validate(formData);
            const isValid = renderErrors(errors);

            if (!isValid) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        },
        true
    );
}

    // Receipt Actions
    document.getElementById("btn-print-receipt")?.addEventListener("click", handlePrintReceipt);
    document.getElementById("btn-share-whatsapp")?.addEventListener("click", handleShareWhatsApp);
    document.getElementById("btn-new-transaction")?.addEventListener("click", () => {
        closeModal("modal-success-receipt");
        switchPage("pos");
    });

    // Products Management Events
    document.getElementById("btn-open-add-product")?.addEventListener("click", () => openProductFormModal());
    document.getElementById("product-crud-form")?.addEventListener("submit", handleSaveProduct);
    document.getElementById("product-search-table")?.addEventListener("input", renderProductsTable);
    document.getElementById("product-filter-category")?.addEventListener("change", renderProductsTable);
    document.getElementById("product-filter-stock")?.addEventListener("change", renderProductsTable);

    // Image Select in Product Modal
    const imgSelect = document.getElementById("form-product-image-select");
    const customImgInput = document.getElementById("form-product-custom-image");
    if (imgSelect && customImgInput) {
        imgSelect.addEventListener("change", () => {
            if (imgSelect.value === "custom") {
                customImgInput.classList.remove("hidden");
                customImgInput.focus();
            } else {
                customImgInput.classList.add("hidden");
            }
        });
    }

    // Quick Restock Save
    document.getElementById("btn-save-quick-restock")?.addEventListener("click", handleSaveQuickRestock);
    document.querySelectorAll(".quick-add-chips .btn-chip").forEach(chip => {
        chip.addEventListener("click", () => {
            const add = Number(chip.dataset.add) || 0;
            const input = document.getElementById("input-restock-qty");
            input.value = (Number(input.value) || 0) + add;
        });
    });

    document.getElementById("btn-restock-minus")?.addEventListener("click", () => {
        const input = document.getElementById("input-restock-qty");
        let val = Number(input.value) || 1;
        if (val > 1) input.value = val - 1;
    });

    document.getElementById("btn-restock-plus")?.addEventListener("click", () => {
        const input = document.getElementById("input-restock-qty");
        let val = Number(input.value) || 0;
        input.value = val + 1;
    });

    // Customer Management Events
    document.getElementById("btn-open-add-customer")?.addEventListener("click", () => openCustomerFormModal());
    document.getElementById("customer-crud-form")?.addEventListener("submit", handleSaveCustomer);
    document.getElementById("customer-search-table")?.addEventListener("input", renderCustomersTable);

    // Transaction Management Events
    document.getElementById("tx-search-input")?.addEventListener("input", renderTransactionsTable);
    document.getElementById("tx-filter-payment")?.addEventListener("change", renderTransactionsTable);
    document.getElementById("tx-filter-date")?.addEventListener("change", renderTransactionsTable);
    document.getElementById("tx-reset-filter")?.addEventListener("click", () => {
        document.getElementById("tx-search-input").value = "";
        document.getElementById("tx-filter-payment").value = "Semua";
        document.getElementById("tx-filter-date").value = "";
        renderTransactionsTable();
    });
    document.getElementById("btn-export-tx-csv")?.addEventListener("click", handleExportCSV);
}