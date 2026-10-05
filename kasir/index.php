<?php
/**
 * Kasir POS Modern - Warung Makan Hanisa
 * Halaman Kasir Penjualan Utama
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$page_title = 'Kasir POS';
$page_subtitle = 'Pilih hidangan, atur suhu, dan proses pembayaran kasir';
$current_page = 'kasir';

// Ambil kategori aktif
$categories = $pdo->query("SELECT * FROM kategori WHERE status = 'aktif' ORDER BY id_kategori ASC")->fetchAll();

// Ambil semua produk aktif beserta kategorinya
$queryProduk = "
    SELECT p.*, k.nama_kategori 
    FROM produk p 
    JOIN kategori k ON p.id_kategori = k.id_kategori 
    WHERE p.status = 'aktif' AND k.status = 'aktif'
    ORDER BY p.id_kategori ASC, p.nama_produk ASC
";
$products = $pdo->query($queryProduk)->fetchAll();
$settings = get_app_settings($pdo);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="pos-layout">
    <!-- Bagian Kiri: Katalog & Pencarian Produk -->
    <div class="pos-products-area">
        <!-- Toolbar: Search & Filter Kategori -->
        <div class="pos-toolbar">
            <div class="pos-search-box">
                <span class="pos-search-icon">🔍</span>
                <input type="text" id="posSearchInput" class="pos-search-input" 
                       placeholder="Cari menu makanan atau minuman..." autocomplete="off">
            </div>

            <div class="pos-category-filters" id="posCategoryFilters">
                <button type="button" class="filter-pill active" data-category="all">
                    Semua Menu (<?= count($products) ?>)
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="filter-pill" data-category="<?= $cat['id_kategori'] ?>">
                        <?= htmlspecialchars($cat['nama_kategori']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Grid Kartu Produk POS -->
        <div class="pos-products-grid" id="posProductsGrid">
            <?php foreach ($products as $p): ?>
                <?php
                $imgSrc = !empty($p['gambar']) ? BASE_URL . 'assets/uploads/produk/' . htmlspecialchars($p['gambar']) : BASE_URL . 'assets/uploads/produk/mie-ayam-biasa.jpg';
                $isOutOfStock = ($p['stok'] <= 0);
                ?>
                <div class="product-pos-card" 
                     id="prod-card-<?= $p['id_produk'] ?>"
                     data-id="<?= $p['id_produk'] ?>"
                     data-nama="<?= htmlspecialchars($p['nama_produk']) ?>"
                     data-harga="<?= (float)$p['harga'] ?>"
                     data-stok="<?= (int)$p['stok'] ?>"
                     data-suhu="<?= $p['opsi_suhu'] ?>"
                     data-kategori="<?= $p['id_kategori'] ?>"
                     data-gambar="<?= htmlspecialchars($imgSrc) ?>">
                    
                    <div class="product-image-container">
                        <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>" loading="lazy"
                             onerror="this.src='<?= BASE_URL ?>assets/uploads/produk/mie-ayam-biasa.jpg'">
                        
                        <!-- Badge Stok -->
                        <?php if ($isOutOfStock): ?>
                            <span class="product-badge-stock out-of-stock" id="badge-stok-<?= $p['id_produk'] ?>">Habis</span>
                        <?php else: ?>
                            <span class="product-badge-stock" id="badge-stok-<?= $p['id_produk'] ?>">Stok: <?= $p['stok'] ?></span>
                        <?php endif; ?>

                        <!-- Badge Suhu Minuman -->
                        <?php if ($p['opsi_suhu'] === 'panas_dingin'): ?>
                            <span class="product-badge-temp">🔥 / ❄</span>
                        <?php elseif ($p['opsi_suhu'] === 'dingin'): ?>
                            <span class="product-badge-temp">❄ Dingin</span>
                        <?php endif; ?>
                    </div>

                    <div class="product-card-body">
                        <span class="product-card-category"><?= htmlspecialchars($p['nama_kategori']) ?></span>
                        <h4 class="product-card-title"><?= htmlspecialchars($p['nama_produk']) ?></h4>
                        
                        <div class="product-card-footer">
                            <span class="product-card-price"><?= format_rupiah($p['harga']) ?></span>
                            <button type="button" class="btn-add-cart" id="btn-add-<?= $p['id_produk'] ?>"
                                    title="Tambah ke pesanan"
                                    <?= $isOutOfStock ? 'disabled' : '' ?>
                                    onclick="onProductAddClick(<?= $p['id_produk'] ?>)">
                                +
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="noProductMatch" style="display: none; text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔍</div>
            <p>Menu yang Anda cari tidak ditemukan.</p>
        </div>
    </div>

    <!-- Bagian Kanan: Panel Keranjang Pesanan -->
    <div class="pos-cart-panel">
        <div class="cart-header">
            <h3>
                <span>🛒 Pesanan</span>
                <span class="cart-items-count" id="cartBadgeCount">0 item</span>
            </h3>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearCart" title="Kosongkan Keranjang" style="display: none;" onclick="clearCart()">
                Batal
            </button>
        </div>

        <div class="cart-items-list" id="cartItemsList">
            <!-- State Kosong Default -->
            <div class="cart-empty-state" id="cartEmptyState">
                <div class="icon">🍽️</div>
                <div style="font-weight: 700; color: var(--primary-dark); margin-bottom: 0.25rem;">Keranjang Masih Kosong</div>
                <div style="font-size: 0.8rem;">Pilih menu makanan atau minuman di sebelah kiri untuk memulai transaksi.</div>
            </div>
        </div>

        <div class="cart-summary" id="cartSummarySection">
            <div class="summary-row">
                <span>Total Item:</span>
                <span id="summaryTotalQty">0 porsi</span>
            </div>
            <div class="summary-row total-row">
                <span>Total Tagihan:</span>
                <span class="total-amount" id="summaryTotalAmount">Rp0</span>
            </div>

            <button type="button" class="btn btn-primary btn-checkout" id="btnOpenCheckout" disabled onclick="openCheckoutModal()">
                Lanjut ke Pembayaran ➔
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================
     MODAL 1: PILIHAN SUHU (PANAS / DINGIN)
     ========================================================== -->
<div class="modal-overlay" id="modalSuhu">
    <div class="modal-box" style="max-width: 440px;">
        <div class="modal-header">
            <h3 id="modalSuhuTitle">Pilih Suhu Minuman</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalSuhu')">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <p id="modalSuhuSubtitle" style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
                Silakan tentukan penyajian minuman ini:
            </p>

            <div class="temp-options-grid">
                <button type="button" class="temp-card-btn" onclick="confirmSuhuChoice('panas')">
                    <span class="temp-emoji">🔥</span>
                    <span class="temp-text">PANAS / HANGAT</span>
                </button>
                <button type="button" class="temp-card-btn" onclick="confirmSuhuChoice('dingin')">
                    <span class="temp-emoji">❄️</span>
                    <span class="temp-text">DINGIN / ES</span>
                </button>
            </div>
        </div>
        <div class="modal-footer" style="justify-content: center;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalSuhu')">Batal</button>
        </div>
    </div>
</div>

<!-- ==========================================================
     MODAL 2: PEMBAYARAN (CASH / QRIS)
     ========================================================== -->
<div class="modal-overlay" id="modalPembayaran">
    <div class="modal-box" style="max-width: 520px;">
        <div class="modal-header">
            <h3>Proses Pembayaran</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalPembayaran')">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Total Banner -->
            <div style="background: linear-gradient(135deg, var(--primary-dark), #5D4037); color: #fff; padding: 1.25rem; border-radius: var(--radius-md); text-align: center; margin-bottom: 1.5rem;">
                <div style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--accent-light);">Total Pembayaran</div>
                <div style="font-size: 2rem; font-weight: 800; font-family: 'Outfit', sans-serif; color: #fff;" id="modalTotalTagihan">
                    Rp0
                </div>
            </div>

            <!-- Tab Metode Pembayaran -->
            <div class="payment-methods-grid">
                <div class="payment-method-card active" id="tabCash" onclick="switchPaymentMethod('cash')">
                    <span style="font-size: 1.3rem;">💵</span>
                    <span>Tunai (Cash)</span>
                </div>
                <div class="payment-method-card" id="tabQris" onclick="switchPaymentMethod('qris')">
                    <span style="font-size: 1.3rem;">📱</span>
                    <span>QRIS Digital</span>
                </div>
            </div>

            <!-- FORM CASH -->
            <div id="sectionCash">
                <div class="form-group">
                    <label class="form-label" for="inputNominalCash">Nominal Uang Diterima (Rp)</label>
                    <input type="number" id="inputNominalCash" class="form-control" style="font-size: 1.2rem; font-weight: 700; font-family: 'Outfit';"
                           placeholder="0" min="0" step="500" oninput="calculateKembalian()">
                    
                    <!-- Quick Cash Buttons -->
                    <div class="quick-cash-grid">
                        <button type="button" class="quick-cash-btn" onclick="setExactCash()">Uang Pas</button>
                        <button type="button" class="quick-cash-btn" onclick="addCash(20000)">+20 rb</button>
                        <button type="button" class="quick-cash-btn" onclick="addCash(50000)">+50 rb</button>
                        <button type="button" class="quick-cash-btn" onclick="setCashAmount(100000)">100 rb</button>
                    </div>
                </div>

                <!-- Indikator Kembalian / Status Uang Kurang -->
                <div id="cashFeedbackBox" style="margin-bottom: 1.25rem; padding: 0.85rem 1rem; border-radius: var(--radius-md); background: #FAF6F0; border: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 600; font-size: 0.9rem;" id="cashFeedbackLabel">Kembalian:</span>
                        <span style="font-size: 1.3rem; font-weight: 800; font-family: 'Outfit';" id="cashKembalianAmount">Rp0</span>
                    </div>
                    <div id="cashUnderpaidNotice" style="display: none; color: var(--status-danger); font-size: 0.82rem; font-weight: 700; margin-top: 0.35rem;">
                        ⚠️ Nominal pembayaran kurang!
                    </div>
                </div>
            </div>

            <!-- SECTION QRIS -->
            <div id="sectionQris" style="display: none;">
                <div class="qris-display-card">
                    <div style="font-size: 0.85rem; font-weight: 700; color: var(--primary-dark); margin-bottom: 0.5rem;">
                        WARUNG MAKAN HANISA - QRIS
                    </div>
                    <img src="<?= BASE_URL ?>assets/images/qris.png" alt="QRIS Warung Hanisa" id="qrisImageModal"
                         style="max-width: 230px;">
                    <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.4;">
                        Scan QR Code untuk melakukan pembayaran dengan aplikasi e-Wallet atau Mobile Banking apa pun.
                    </div>
                </div>
            </div>

            <!-- Catatan Pesanan Opsional -->
            <div class="form-group" style="margin-top: 0.5rem;">
                <label class="form-label" for="inputCatatanPesanan">Catatan Pesanan (Opsional)</label>
                <input type="text" id="inputCatatanPesanan" class="form-control" placeholder="Misal: Dibungkus, kuah dipisah, dsb.">
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalPembayaran')">Batal</button>
            <button type="button" class="btn btn-primary btn-lg" id="btnSubmitPayment" onclick="submitTransaction()">
                Bayar Sekarang
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================
     MODAL 3: TRANSAKSI BERHASIL (SUCCESS INVOICE)
     ========================================================== -->
<div class="modal-overlay" id="modalSukses">
    <div class="modal-box" style="max-width: 480px; text-align: center;">
        <div class="modal-body" style="padding: 2.25rem 1.75rem;">
            <div style="width: 70px; height: 70px; border-radius: var(--radius-full); background: var(--status-success-bg); color: var(--status-success); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 1.25rem; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25);">
                ✓
            </div>
            
            <h3 style="font-size: 1.4rem; color: var(--primary-dark); margin-bottom: 0.35rem;">
                Transaksi Berhasil!
            </h3>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.5rem;">
                Pesanan telah disimpan ke database & stok telah diperbarui.
            </p>

            <div style="background: #FAF6F0; border-radius: var(--radius-md); padding: 1.15rem; text-align: left; font-size: 0.88rem; border: 1px solid var(--border-color); margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                    <span style="color: var(--text-muted);">Nomor Pesanan:</span>
                    <strong id="resNomorPesanan">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                    <span style="color: var(--text-muted);">Waktu:</span>
                    <span id="resTanggal">-</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                    <span style="color: var(--text-muted);">Metode Pembayaran:</span>
                    <strong id="resMetode" class="badge badge-info">-</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem; border-top: 1px dashed var(--border-color); padding-top: 0.5rem;">
                    <span style="color: var(--text-muted); font-weight: 700;">Total Tagihan:</span>
                    <strong style="color: var(--accent-orange); font-size: 1.05rem;" id="resTotal">Rp0</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;" id="resRowDiterima">
                    <span style="color: var(--text-muted);">Uang Diterima:</span>
                    <span id="resDiterima">Rp0</span>
                </div>
                <div style="display: flex; justify-content: space-between;" id="resRowKembalian">
                    <span style="color: var(--text-muted); font-weight: 700;">Kembalian:</span>
                    <strong style="color: var(--status-success); font-size: 1.05rem;" id="resKembalian">Rp0</strong>
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: center;">
                <a href="#" id="btnLihatDetail" class="btn btn-secondary" target="_blank">
                    📄 Cetak / Detail Transaksi
                </a>
                <button type="button" class="btn btn-primary" onclick="closeModal('modalSukses'); clearCart();">
                    ➕ Transaksi Baru
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================
     JAVASCRIPT LOGIKA KASIR & TRANSAKSI
     ========================================================== -->
<script>
// Data Produk dari PHP
const productsDatabase = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

// State Keranjang: Array item pesanan
// Format item: { id_produk, nama_produk, harga, stok, suhu, jumlah, gambar }
let cart = [];
let pendingProductId = null;
let currentPaymentMethod = 'cash';

// 1. Event Listener: Search & Filter Kategori
document.getElementById('posSearchInput').addEventListener('input', filterProducts);

document.querySelectorAll('#posCategoryFilters .filter-pill').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#posCategoryFilters .filter-pill').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        filterProducts();
    });
});

function filterProducts() {
    const query = document.getElementById('posSearchInput').value.toLowerCase().trim();
    const activePill = document.querySelector('#posCategoryFilters .filter-pill.active');
    const selectedCategory = activePill ? activePill.getAttribute('data-category') : 'all';

    let matchCount = 0;
    document.querySelectorAll('.product-pos-card').forEach(card => {
        const name = card.getAttribute('data-nama').toLowerCase();
        const catId = card.getAttribute('data-kategori');

        const matchQuery = (query === '' || name.includes(query));
        const matchCategory = (selectedCategory === 'all' || selectedCategory === catId);

        if (matchQuery && matchCategory) {
            card.style.display = 'flex';
            matchCount++;
        } else {
            card.style.display = 'none';
        }
    });

    document.getElementById('noProductMatch').style.display = (matchCount === 0) ? 'block' : 'none';
}

// 2. Klik Tambah Produk ke Keranjang
function onProductAddClick(productId) {
    const product = productsDatabase.find(p => p.id_produk == productId);
    if (!product) return;

    if (product.stok <= 0) {
        alert('Stok ' + product.nama_produk + ' sudah habis!');
        return;
    }

    // Aturan Opsi Suhu sesuai Section 14
    if (product.opsi_suhu === 'tidak_berlaku') {
        // Makanan: langsung masuk keranjang
        addItemToCart(product, 'tidak_berlaku');
    } else if (product.opsi_suhu === 'dingin') {
        // Khusus dingin (Air Es): langsung masuk sebagai Dingin
        addItemToCart(product, 'dingin');
    } else if (product.opsi_suhu === 'panas_dingin') {
        // Minuman fleksibel: buka modal pilihan suhu
        pendingProductId = productId;
        document.getElementById('modalSuhuTitle').innerText = 'Pilih Suhu: ' + product.nama_produk;
        openModal('modalSuhu');
    }
}

// Konfirmasi dari Modal Suhu
function confirmSuhuChoice(suhu) {
    if (pendingProductId) {
        const product = productsDatabase.find(p => p.id_produk == pendingProductId);
        if (product) {
            addItemToCart(product, suhu);
        }
    }
    closeModal('modalSuhu');
    pendingProductId = null;
}

// 3. Tambah Item ke Keranjang (Identifikasi unik: id_produk + suhu)
function addItemToCart(product, suhu) {
    const cartKey = product.id_produk + '_' + suhu;
    const existingItem = cart.find(item => item.id_produk == product.id_produk && item.suhu === suhu);

    // Cek total qty item ini di keranjang dibanding stok tersedia
    const totalQtyInCart = cart
        .filter(item => item.id_produk == product.id_produk)
        .reduce((sum, item) => sum + item.jumlah, 0);

    if (totalQtyInCart + 1 > product.stok) {
        alert('Maaf, pesanan melebihi stok yang tersedia (' + product.stok + ' porsi).');
        return;
    }

    if (existingItem) {
        existingItem.jumlah += 1;
    } else {
        cart.push({
            id_produk: parseInt(product.id_produk),
            nama_produk: product.nama_produk,
            harga: parseFloat(product.harga),
            stok: parseInt(product.stok),
            suhu: suhu,
            jumlah: 1,
            gambar: product.gambar
        });
    }

    renderCart();
}

// 4. Update Qty di Keranjang
function updateCartQty(index, delta) {
    if (index < 0 || index >= cart.length) return;
    const item = cart[index];
    const product = productsDatabase.find(p => p.id_produk == item.id_produk);

    if (delta > 0) {
        const totalQtyInCart = cart
            .filter(i => i.id_produk == item.id_produk)
            .reduce((sum, i) => sum + i.jumlah, 0);

        if (product && totalQtyInCart + delta > product.stok) {
            alert('Stok maksimal ' + product.nama_produk + ' adalah ' + product.stok + ' porsi.');
            return;
        }
        item.jumlah += delta;
    } else {
        item.jumlah += delta;
        if (item.jumlah <= 0) {
            cart.splice(index, 1);
        }
    }

    renderCart();
}

// 5. Hapus Item dari Keranjang
function removeCartItem(index) {
    if (index >= 0 && index < cart.length) {
        cart.splice(index, 1);
        renderCart();
    }
}

// 6. Kosongkan Keranjang
function clearCart() {
    cart = [];
    renderCart();
}

// 7. Render Keranjang UI
function renderCart() {
    const list = document.getElementById('cartItemsList');
    const badgeCount = document.getElementById('cartBadgeCount');
    const clearBtn = document.getElementById('btnClearCart');
    const totalQtyEl = document.getElementById('summaryTotalQty');
    const totalAmountEl = document.getElementById('summaryTotalAmount');
    const checkoutBtn = document.getElementById('btnOpenCheckout');

    if (cart.length === 0) {
        list.innerHTML = `
            <div class="cart-empty-state" id="cartEmptyState">
                <div class="icon">🍽️</div>
                <div style="font-weight: 700; color: var(--primary-dark); margin-bottom: 0.25rem;">Keranjang Masih Kosong</div>
                <div style="font-size: 0.8rem;">Pilih menu makanan atau minuman di sebelah kiri untuk memulai transaksi.</div>
            </div>
        `;
        badgeCount.innerText = '0 item';
        clearBtn.style.display = 'none';
        totalQtyEl.innerText = '0 porsi';
        totalAmountEl.innerText = formatRupiah(0);
        checkoutBtn.disabled = true;
        return;
    }

    clearBtn.style.display = 'inline-flex';

    let html = '';
    let totalQty = 0;
    let grandTotal = 0;

    cart.forEach((item, idx) => {
        const subtotal = item.harga * item.jumlah;
        totalQty += item.jumlah;
        grandTotal += subtotal;

        let tempBadgeHtml = '';
        if (item.suhu === 'panas') {
            tempBadgeHtml = '<span class="cart-item-temp temp-panas">🔥 Panas</span>';
        } else if (item.suhu === 'dingin') {
            tempBadgeHtml = '<span class="cart-item-temp temp-dingin">❄ Dingin</span>';
        }

        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${escapeHtml(item.nama_produk)}</div>
                    ${tempBadgeHtml}
                    <div class="cart-item-price">${formatRupiah(item.harga)} × ${item.jumlah} = <span class="cart-item-subtotal">${formatRupiah(subtotal)}</span></div>
                </div>
                <div class="cart-item-actions">
                    <button type="button" class="qty-btn" onclick="updateCartQty(${idx}, -1)">-</button>
                    <span class="cart-item-qty">${item.jumlah}</span>
                    <button type="button" class="qty-btn" onclick="updateCartQty(${idx}, 1)">+</button>
                    <button type="button" class="btn-remove-item" title="Hapus menu" onclick="removeCartItem(${idx})">🗑️</button>
                </div>
            </div>
        `;
    });

    list.innerHTML = html;
    badgeCount.innerText = totalQty + ' item';
    totalQtyEl.innerText = totalQty + ' porsi';
    totalAmountEl.innerText = formatRupiah(grandTotal);
    checkoutBtn.disabled = false;
}

// 8. Pembayaran & Checkout Flow
function openCheckoutModal() {
    if (cart.length === 0) return;
    
    const grandTotal = cart.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
    document.getElementById('modalTotalTagihan').innerText = formatRupiah(grandTotal);
    
    // Default to Cash
    switchPaymentMethod('cash');
    document.getElementById('inputNominalCash').value = '';
    calculateKembalian();

    openModal('modalPembayaran');
}

function switchPaymentMethod(method) {
    currentPaymentMethod = method;
    const tabCash = document.getElementById('tabCash');
    const tabQris = document.getElementById('tabQris');
    const sectionCash = document.getElementById('sectionCash');
    const sectionQris = document.getElementById('sectionQris');
    const submitBtn = document.getElementById('btnSubmitPayment');

    if (method === 'cash') {
        tabCash.classList.add('active');
        tabQris.classList.remove('active');
        sectionCash.style.display = 'block';
        sectionQris.style.display = 'none';
        submitBtn.innerText = 'Bayar Sekarang (Cash)';
        calculateKembalian();
    } else {
        tabQris.classList.add('active');
        tabCash.classList.remove('active');
        sectionCash.style.display = 'none';
        sectionQris.style.display = 'block';
        submitBtn.innerText = 'Saya Sudah Membayar (Konfirmasi QRIS)';
        submitBtn.disabled = false; // QRIS is confirmed on click
    }
}

// Helper nominal Cash
function setExactCash() {
    const grandTotal = cart.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
    document.getElementById('inputNominalCash').value = grandTotal;
    calculateKembalian();
}

function setCashAmount(val) {
    document.getElementById('inputNominalCash').value = val;
    calculateKembalian();
}

function addCash(amount) {
    const current = parseFloat(document.getElementById('inputNominalCash').value || 0);
    document.getElementById('inputNominalCash').value = current + amount;
    calculateKembalian();
}

function calculateKembalian() {
    if (currentPaymentMethod !== 'cash') return;

    const grandTotal = cart.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
    const nominal = parseFloat(document.getElementById('inputNominalCash').value || 0);
    const feedbackBox = document.getElementById('cashFeedbackBox');
    const label = document.getElementById('cashFeedbackLabel');
    const amountEl = document.getElementById('cashKembalianAmount');
    const underpaidEl = document.getElementById('cashUnderpaidNotice');
    const submitBtn = document.getElementById('btnSubmitPayment');

    if (nominal < grandTotal) {
        const kurang = grandTotal - nominal;
        feedbackBox.style.background = '#FEF2F2';
        feedbackBox.style.borderColor = 'var(--status-danger)';
        label.innerText = 'Kekurangan Uang:';
        label.style.color = 'var(--status-danger)';
        amountEl.innerText = formatRupiah(kurang);
        amountEl.style.color = 'var(--status-danger)';
        underpaidEl.innerText = '⚠️ Nominal pembayaran kurang ' + formatRupiah(kurang);
        underpaidEl.style.display = 'block';
        submitBtn.disabled = true;
    } else {
        const kembalian = nominal - grandTotal;
        feedbackBox.style.background = '#ECFDF5';
        feedbackBox.style.borderColor = 'var(--status-success)';
        label.innerText = 'Kembalian:';
        label.style.color = 'var(--status-success)';
        amountEl.innerText = formatRupiah(kembalian);
        amountEl.style.color = 'var(--status-success)';
        underpaidEl.style.display = 'none';
        submitBtn.disabled = false;
    }
}

// 9. Submit Transaksi ke Backend via AJAX
async function submitTransaction() {
    if (cart.length === 0) return;

    const grandTotal = cart.reduce((sum, item) => sum + (item.harga * item.jumlah), 0);
    let nominalDiterima = 0;

    if (currentPaymentMethod === 'cash') {
        nominalDiterima = parseFloat(document.getElementById('inputNominalCash').value || 0);
        if (nominalDiterima < grandTotal) {
            alert('Nominal uang tunai kurang dari total tagihan.');
            return;
        }
    } else {
        nominalDiterima = grandTotal;
    }

    const catatan = document.getElementById('inputCatatanPesanan').value.trim();
    const submitBtn = document.getElementById('btnSubmitPayment');
    submitBtn.disabled = true;
    submitBtn.innerText = 'Memproses Transaksi...';

    const payload = {
        metode_pembayaran: currentPaymentMethod,
        nominal_diterima: nominalDiterima,
        catatan: catatan,
        items: cart.map(item => ({
            id_produk: item.id_produk,
            jumlah: item.jumlah,
            suhu: item.suhu
        }))
    };

    try {
        const response = await fetch('<?= BASE_URL ?>kasir/proses.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if (response.ok && result.status === 'success') {
            closeModal('modalPembayaran');

            // Isi data modal sukses
            const d = result.data;
            document.getElementById('resNomorPesanan').innerText = d.nomor_pesanan;
            document.getElementById('resTanggal').innerText = d.tanggal;
            document.getElementById('resMetode').innerText = d.metode_pembayaran;
            document.getElementById('resTotal').innerText = d.total_formatted;
            
            if (d.metode_pembayaran === 'CASH') {
                document.getElementById('resRowDiterima').style.display = 'flex';
                document.getElementById('resRowKembalian').style.display = 'flex';
                document.getElementById('resDiterima').innerText = d.diterima_formatted;
                document.getElementById('resKembalian').innerText = d.kembalian_formatted;
            } else {
                document.getElementById('resRowDiterima').style.display = 'none';
                document.getElementById('resRowKembalian').style.display = 'none';
            }

            document.getElementById('btnLihatDetail').href = '<?= BASE_URL ?>transaksi/detail.php?id=' + d.id_pesanan;

            // Kurangi stok di memory & UI secara realtime
            cart.forEach(cItem => {
                const prod = productsDatabase.find(p => p.id_produk == cItem.id_produk);
                if (prod) {
                    prod.stok -= cItem.jumlah;
                    const badge = document.getElementById('badge-stok-' + prod.id_produk);
                    const addBtn = document.getElementById('btn-add-' + prod.id_produk);
                    if (badge) {
                        if (prod.stok <= 0) {
                            badge.innerText = 'Habis';
                            badge.classList.add('out-of-stock');
                            if (addBtn) addBtn.disabled = true;
                        } else {
                            badge.innerText = 'Stok: ' + prod.stok;
                        }
                    }
                }
            });

            openModal('modalSukses');
        } else {
            alert('Gagal memproses transaksi: ' + (result.message || 'Terjadi kesalahan'));
            submitBtn.disabled = false;
            submitBtn.innerText = (currentPaymentMethod === 'cash') ? 'Bayar Sekarang (Cash)' : 'Saya Sudah Membayar (Konfirmasi QRIS)';
        }
    } catch (err) {
        alert('Koneksi server terganggu: ' + err.message);
        submitBtn.disabled = false;
        submitBtn.innerText = (currentPaymentMethod === 'cash') ? 'Bayar Sekarang (Cash)' : 'Saya Sudah Membayar (Konfirmasi QRIS)';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
