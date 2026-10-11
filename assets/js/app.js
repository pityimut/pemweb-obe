/**
 * Warung Makan Hanisa - Core JavaScript Utilities & UI Interactions
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.getElementById('appSidebar');

    if (sidebarToggle && appSidebar) {
        sidebarToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            appSidebar.classList.toggle('show');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 768 && appSidebar.classList.contains('show')) {
                if (!appSidebar.contains(e.target) && e.target !== sidebarToggle) {
                    appSidebar.classList.remove('show');
                }
            }
        });
    }

    // 2. Auto-close modals with close button or backdrop click
    document.querySelectorAll('.modal-close-btn, [data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = this.closest('.modal-overlay');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });

    // 3. Auto dismiss flash alerts after 6 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(() => {
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 500);
            });
        }, 6000);
    }
});

// Modal Helpers
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Rupiah Formatter
function formatRupiah(number) {
    return 'Rp' + new Intl.NumberFormat('id-ID').format(Math.round(number));
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}

/* ==========================================================================
   PRAKTIKUM A & B: CLIENT-SIDE ACCESSIBLE FORM & VALIDATION
   Warung Makan Hanisa POS System
   Mahasiswa : Fidiani (NPM: 2440304022)
   ========================================================================== */

/**
 * Pemetaan elemen form, id input, dan id pesan error untuk aksesibilitas (aria-describedby)
 */
const FIELD_MAP = [
    { key: 'tanggal', inputId: 'tanggal', errorId: 'tanggal-error' },
    { key: 'nama', inputId: 'inputNamaPemesan', errorId: 'nama-error' },
    { key: 'kontak', inputId: 'inputKontakPemesan', errorId: 'kontak-error' },
    { key: 'menu', inputId: 'inputJumlahPesanan', errorId: 'jumlah-error' },
    { key: 'jumlah', inputId: 'inputJumlahPesanan', errorId: 'jumlah-error' },
    { key: 'metode', inputId: 'metodeCash', errorId: 'metode-error' },
    { key: 'nominal', inputId: 'inputNominalCash', errorId: 'nominal-error' },
    { key: 'catatan', inputId: 'inputCatatanPesanan', errorId: 'catatan-error' },
    { key: 'konfirmasi', inputId: 'inputKonfirmasiPesanan', errorId: 'konfirmasi-error' }
];

/**
 * A. readFormData(form)
 * Membaca dan menormalisasi seluruh data dari form transaksi kasir.
 * Mengikuti prinsip:
 * - string -> trim()
 * - angka  -> Number()
 * - opsi   -> ambil value
 * - tanggal-> ambil value
 * - checkbox -> boolean
 * @param {HTMLFormElement} form
 * @returns {Object} Data transaksi yang ternormalisasi
 */
function readFormData(form) {
    if (!form) return {};

    // 1. Tanggal Transaksi
    const tanggalInput = form.querySelector('#tanggal');
    const tanggal = tanggalInput ? tanggalInput.value.trim() : '';

    // 2. Nama Pemesan
    const namaInput = form.querySelector('#inputNamaPemesan');
    const namaPemesan = namaInput ? namaInput.value.trim() : '';

    // 3. Kontak Pemesan (Opsional)
    const kontakInput = form.querySelector('#inputKontakPemesan');
    const kontakPemesan = kontakInput ? kontakInput.value.trim() : '';

    // 4. Jumlah Pesanan & Total Menu dari Keranjang
    const jumlahInput = form.querySelector('#inputJumlahPesanan');
    const jumlahRaw = jumlahInput ? jumlahInput.value.trim() : '0';
    const jumlah = (jumlahRaw !== '' && !isNaN(Number(jumlahRaw))) ? Number(jumlahRaw) : 0;

    let menuCount = 0;
    let totalTransaksi = 0;
    if (typeof cart !== 'undefined' && Array.isArray(cart)) {
        menuCount = cart.length;
        totalTransaksi = cart.reduce((sum, item) => sum + (Number(item.harga) * Number(item.jumlah)), 0);
    } else {
        menuCount = jumlah > 0 ? 1 : 0;
        const totalEl = document.getElementById('modalTotalTagihan');
        if (totalEl) {
            const num = totalEl.innerText.replace(/[^0-9]/g, '');
            totalTransaksi = num ? Number(num) : 0;
        }
    }

    // 5. Metode Pembayaran (Cash / QRIS)
    const radioMetode = form.querySelector('input[name="metode_pembayaran"]:checked');
    let metode = radioMetode ? radioMetode.value.trim().toLowerCase() : '';
    if (!metode && typeof currentPaymentMethod !== 'undefined' && currentPaymentMethod) {
        metode = currentPaymentMethod.toLowerCase();
    }

    // 6. Nominal Pembayaran Cash
    const nominalInput = form.querySelector('#inputNominalCash');
    const nominalRaw = nominalInput ? nominalInput.value.trim() : '';
    const nominalCash = (nominalRaw !== '' && !isNaN(Number(nominalRaw))) ? Number(nominalRaw) : NaN;

    // 7. Catatan Pesanan (Opsional)
    const catatanInput = form.querySelector('#inputCatatanPesanan');
    const catatan = catatanInput ? catatanInput.value.trim() : '';

    // 8. Checkbox Konfirmasi Pesanan
    const konfirmasiInput = form.querySelector('#inputKonfirmasiPesanan');
    const konfirmasi = konfirmasiInput ? konfirmasiInput.checked : false;

    return {
        tanggal: tanggal,
        namaPemesan: namaPemesan,
        kontakPemesan: kontakPemesan,
        menuCount: menuCount,
        jumlah: jumlah,
        totalTransaksi: totalTransaksi,
        metode_pembayaran: metode,
        nominal_cash: nominalCash,
        nominalRaw: nominalRaw,
        catatan: catatan,
        konfirmasi: konfirmasi
    };
}

/**
 * B. validate(data)
 * Memvalidasi data transaksi kasir dengan aturan validasi spesifik:
 * 1. Keranjang minimal 1 item
 * 2. Nama pemesan wajib diisi dan minimal 3 karakter
 * 3. Kontak pemesan jika diisi harus berupa nomor telepon valid
 * 4. Tanggal transaksi wajib diisi dan valid
 * 5. Metode pembayaran wajib dipilih dan didukung (Cash / QRIS)
 * 6. Jika Tunai, nominal uang wajib diisi dan minimal sebesar total transaksi
 * 7. Catatan pesanan opsional (maks 255 karakter)
 * 8. Checkbox konfirmasi pesanan wajib dicentang
 * @param {Object} data
 * @returns {Object} errors - Pasangan field error dan pesan spesifik
 */
function validate(data) {
    const errors = {};

    // Aturan 1: Keranjang harus berisi minimal satu item sebelum transaksi dibayar
    if (!data.menuCount || data.menuCount <= 0 || !data.jumlah || data.jumlah < 1) {
        errors.menu = 'Keranjang belanja masih kosong! Silakan pilih minimal 1 menu makanan atau minuman.';
    }

    // Aturan 2: Nama pemesan wajib diisi dan minimal 3 karakter
    if (!data.namaPemesan || data.namaPemesan === '') {
        errors.nama = 'Nama pemesan wajib diisi.';
    } else if (data.namaPemesan.length < 3) {
        errors.nama = 'Nama pemesan minimal 3 karakter.';
    }

    // Aturan 3: Kontak pemesan (jika diisi, format harus berupa nomor telepon yang valid)
    if (data.kontakPemesan && data.kontakPemesan !== '') {
        const cleanPhone = data.kontakPemesan.replace(/[\s\-\(\)\+]/g, '');
        if (!/^[0-9]{9,15}$/.test(cleanPhone)) {
            errors.kontak = 'Format nomor kontak tidak valid (contoh: 081234567890).';
        }
    }

    // Aturan 4: Tanggal transaksi wajib diisi dan valid
    if (!data.tanggal || data.tanggal === '') {
        errors.tanggal = 'Tanggal transaksi wajib diisi.';
    } else if (isNaN(Date.parse(data.tanggal))) {
        errors.tanggal = 'Format tanggal transaksi tidak valid.';
    }

    // Aturan 5: Metode pembayaran wajib dipilih (hanya Cash atau QRIS)
    if (!data.metode_pembayaran || (data.metode_pembayaran !== 'cash' && data.metode_pembayaran !== 'qris')) {
        errors.metode = 'Metode pembayaran wajib dipilih (Tunai atau QRIS).';
    }

    // Aturan 6: Nominal pembayaran Tunai
    // Jika Tunai: nominal wajib diisi dan tidak boleh kurang dari total transaksi.
    // Jika QRIS: aturan nominal Tunai tidak diuji.
    if (data.metode_pembayaran === 'cash') {
        if (data.nominalRaw === '' || isNaN(data.nominal_cash)) {
            errors.nominal = 'Nominal pembayaran wajib diisi untuk pembayaran Tunai.';
        } else if (data.nominal_cash < data.totalTransaksi) {
            const kekurangan = data.totalTransaksi - data.nominal_cash;
            errors.nominal = 'Nominal pembayaran tidak boleh kurang dari total transaksi (kurang ' + formatRupiah(kekurangan) + ').';
        }
    }

    // Aturan 7: Catatan pesanan opsional (maksimal 255 karakter)
    if (data.catatan && data.catatan.length > 255) {
        errors.catatan = 'Catatan pesanan maksimal 255 karakter.';
    }

    // Aturan 8: Checkbox konfirmasi harus dicentang sebelum transaksi diproses
    if (!data.konfirmasi) {
        errors.konfirmasi = 'Harap centang konfirmasi bahwa data pesanan dan rincian pembayaran sudah benar.';
    }

    return errors;
}

/**
 * C. renderErrors(errors)
 * Menampilkan pesan error dan error summary dengan standar aksesibilitas:
 * - Reset error lama & atribut aria-invalid
 * - Menampilkan teks error spesifik di dekat field terkait
 * - Menambahkan aria-invalid="true" dan class .is-invalid pada field bermasalah
 * - Menampilkan error summary di atas form dengan role="alert" & aria-live="polite"
 * - Fokus otomatis keyboard ke field error pertama yang tidak valid
 * @param {Object} errors
 * @returns {boolean} isValid - True jika tidak ada error
 */
function renderErrors(errors = {}) {
    // 1. Reset pesan error lama dan status invalid
    FIELD_MAP.forEach(item => {
        const errEl = document.getElementById(item.errorId);
        if (errEl) {
            errEl.innerText = '';
            errEl.style.display = 'none';
        }
        const inputEl = document.getElementById(item.inputId);
        if (inputEl) {
            inputEl.removeAttribute('aria-invalid');
            inputEl.classList.remove('is-invalid');
        }
    });

    const summaryEl = document.getElementById('form-errors');
    if (summaryEl) {
        summaryEl.innerHTML = '';
        summaryEl.hidden = true;
    }

    const errorKeys = Object.keys(errors);
    if (errorKeys.length === 0) {
        return true; // Form valid
    }

    let firstErrorElement = null;
    const summaryList = [];

    // Prioritas urutan tampilan feedback error & fokus keyboard
    const priority = ['tanggal', 'nama', 'kontak', 'menu', 'jumlah', 'metode', 'nominal', 'catatan', 'konfirmasi'];

    priority.forEach(key => {
        if (errors[key]) {
            const message = errors[key];
            summaryList.push(message);

            const mapItem = FIELD_MAP.find(m => m.key === key);
            if (mapItem) {
                const errEl = document.getElementById(mapItem.errorId);
                const inputEl = document.getElementById(mapItem.inputId);

                // Tampilkan pesan teks error dekat field
                if (errEl) {
                    errEl.innerText = message;
                    errEl.style.display = 'block';
                }

                // Tambahkan aria-invalid="true" dan class is-invalid pada field error
                if (inputEl) {
                    inputEl.setAttribute('aria-invalid', 'true');
                    inputEl.classList.add('is-invalid');
                    if (!firstErrorElement) {
                        firstErrorElement = inputEl;
                    }
                }
            }
        }
    });

    // Isi dan buka error summary di bagian atas form
    if (summaryEl && summaryList.length > 0) {
        summaryEl.hidden = false;
        let html = '<div style="font-weight: 700; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.4rem;">';
        html += '<span>⚠️</span><span>Terdapat ' + summaryList.length + ' kesalahan pada form pembayaran:</span></div>';
        html += '<ul>';
        summaryList.forEach(msg => {
            html += '<li>' + escapeHtml(msg) + '</li>';
        });
        html += '</ul>';
        summaryEl.innerHTML = html;
    }

    // Fokus keyboard otomatis ke elemen error pertama
    if (firstErrorElement) {
        if (firstErrorElement.type === 'hidden') {
            const summaryBadge = document.getElementById('modalItemsSummary') || document.getElementById('btnSubmitPayment');
            if (summaryBadge) {
                summaryBadge.setAttribute('tabindex', '-1');
                summaryBadge.focus();
            }
        } else {
            firstErrorElement.focus();
        }
    }

    return false;
}

/**
 * D. Real-Time Error Clearing
 * Menghapus penanda error seketika saat pengguna memperbaiki nilai input.
 * @param {HTMLFormElement} form
 */
function setupRealtimeErrorClearing(form) {
    if (!form) return;

    FIELD_MAP.forEach(item => {
        const inputEl = document.getElementById(item.inputId);
        const errEl = document.getElementById(item.errorId);
        if (!inputEl) return;

        const clearCurrentField = () => {
            if (inputEl.classList.contains('is-invalid') || inputEl.getAttribute('aria-invalid') === 'true') {
                inputEl.classList.remove('is-invalid');
                inputEl.removeAttribute('aria-invalid');
                if (errEl) {
                    errEl.innerText = '';
                    errEl.style.display = 'none';
                }

                // Periksa apakah masih ada error lain yang tersisa
                const remainingInvalid = form.querySelectorAll('.is-invalid');
                if (remainingInvalid.length === 0) {
                    const summaryEl = document.getElementById('form-errors');
                    if (summaryEl) {
                        summaryEl.innerHTML = '';
                        summaryEl.hidden = true;
                    }
                }
            }
        };

        inputEl.addEventListener('input', clearCurrentField);
        inputEl.addEventListener('change', clearCurrentField);
    });
}

// E. Inisialisasi Event Listener Submit Form Transaksi
document.addEventListener('DOMContentLoaded', function () {
    const formTransaksi = document.getElementById('formTransaksi');
    if (formTransaksi) {
        formTransaksi.addEventListener('submit', function (e) {
            e.preventDefault();

            // Panggil submitTransaction yang terintegrasi dengan validasi
            if (typeof submitTransaction === 'function') {
                submitTransaction();
            }
        });

        // Pasang real-time error clearing
        setupRealtimeErrorClearing(formTransaksi);
    }
});

/* ==========================================================================
   PRAKTIKUM & TUGAS OBE PERTEMUAN 7: MINI DASHBOARD BERBASIS API
   Warung Makan Hanisa
   Fitur:
   1. Pengambilan data asinkron: fetch(), async/await, try/catch, response.ok
   2. Status tampilan: Loading state, Error state, Success state, Empty state
   3. Penanganan kegagalan dengan tombol "Coba Lagi" (Retry)
   4. Tampilan kartu metrik penjualan & tabel monitoring stok produk
   5. Pencarian realtime & filter kategori + filter status stok
   6. Tombol pengujian kegagalan (Latihan 3 error simulation)
   ========================================================================== */

(function () {
    let cachedDashboardData = null;
    let isSimulateErrorActive = false;

    function safeFormatRupiah(val) {
        if (typeof formatRupiah === 'function') return formatRupiah(val);
        return 'Rp' + new Intl.NumberFormat('id-ID').format(Math.round(val || 0));
    }

    function safeEscapeHtml(text) {
        if (typeof escapeHtml === 'function') return escapeHtml(text);
        if (!text) return '';
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    function initMiniDashboard() {
        const section = document.getElementById('mini-dashboard-section');
        if (!section) return;

        const baseUrl = section.dataset.baseUrl || '/pemweb-obe/';
        const defaultEndpoint = `${baseUrl}api/dashboard.php`;

        const loadingEl = document.getElementById('miniDashLoading');
        const errorEl = document.getElementById('miniDashError');
        const contentEl = document.getElementById('miniDashContent');
        const emptyEl = document.getElementById('miniDashEmpty');
        const errorMessageEl = document.getElementById('miniDashErrorMessage');
        const statusBadge = document.getElementById('apiStatusBadge');
        const endpointDisplay = document.getElementById('apiEndpointUrl');
        const lastUpdatedEl = document.getElementById('apiLastUpdated');

        const searchInput = document.getElementById('miniDashSearchInput');
        const categoryFilter = document.getElementById('miniDashCategoryFilter');
        const stockFilter = document.getElementById('miniDashStockFilter');

        const btnRefresh = document.getElementById('btnRefreshDashboard');
        const btnRetry = document.getElementById('btnRetryDashboard');
        const btnSimulateError = document.getElementById('btnSimulateError');
        const btnResetEndpoint = document.getElementById('btnResetEndpoint');

        /**
         * Mengambil data dashboard dari API secara Asynchronous
         * Menggunakan fetch(), async/await, try/catch, dan pemeriksaan response.ok
         */
        async function fetchDashboardData(urlToFetch) {
            const url = urlToFetch || (isSimulateErrorActive ? `${defaultEndpoint}?simulate_error=1` : defaultEndpoint);

            // 1. TAMPILKAN STATUS LOADING (Latihan 3)
            if (loadingEl) loadingEl.classList.remove('hidden');
            if (errorEl) errorEl.classList.add('hidden');
            if (contentEl) contentEl.classList.add('hidden');
            if (emptyEl) emptyEl.classList.add('hidden');

            if (statusBadge) {
                statusBadge.className = 'badge badge-warning';
                statusBadge.innerHTML = '⏳ Memuat data...';
            }
            if (endpointDisplay) {
                endpointDisplay.textContent = url;
            }

            try {
                // Latihan 2: Pemanggilan fetch() dengan async/await
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    cache: 'no-cache'
                });

                // Latihan 2 & 3: Pemeriksaan response.ok
                if (!response.ok) {
                    const statusText = response.statusText || 'Kesalahan Server';
                    throw new Error(`Permintaan HTTP gagal dengan status ${response.status} (${statusText})`);
                }

                // Parsing JSON dari endpoint
                const result = await response.json();

                if (!result || result.status !== 'success' || !result.data) {
                    throw new Error(result?.message || 'Format respons JSON dari endpoint tidak valid.');
                }

                // Simpan cache data yang berhasil didapatkan
                cachedDashboardData = result.data;

                // 2. TAMPILKAN STATUS SUKSES (DATA STATE)
                if (loadingEl) loadingEl.classList.add('hidden');
                if (errorEl) errorEl.classList.add('hidden');
                if (contentEl) contentEl.classList.remove('hidden');

                if (statusBadge) {
                    statusBadge.className = 'badge badge-success';
                    statusBadge.innerHTML = '🟢 API Terhubung (200 OK)';
                }

                if (lastUpdatedEl) {
                    const now = new Date();
                    lastUpdatedEl.textContent = now.toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    }) + ' WIB';
                }

                // Populasi dropdown kategori jika belum terisi
                populateCategoryFilter(cachedDashboardData.kategori || []);

                // Render kartu metrik & tabel
                renderDashboardView();

            } catch (error) {
                // Latihan 3: Penanganan Kesalahan (Error State) melalui try/catch
                console.error('[Mini Dashboard API Error]:', error);

                if (loadingEl) loadingEl.classList.add('hidden');
                if (contentEl) contentEl.classList.add('hidden');
                if (errorEl) errorEl.classList.remove('hidden');

                if (errorMessageEl) {
                    errorMessageEl.textContent = error.message || 'Terjadi kesalahan jaringan atau server saat mengakses endpoint.';
                }

                if (statusBadge) {
                    statusBadge.className = 'badge badge-danger';
                    statusBadge.innerHTML = '🔴 Gagal Terhubung';
                }

                if (btnResetEndpoint) {
                    btnResetEndpoint.classList.toggle('hidden', !isSimulateErrorActive);
                }
            }
        }

        /**
         * Mengisi opsi kategori pada dropdown filter secara dinamis dari data API
         */
        function populateCategoryFilter(categories) {
            if (!categoryFilter || categoryFilter.options.length > 1) return;

            categories.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.nama_kategori;
                opt.textContent = `${cat.nama_kategori} (${cat.jumlah_produk || 0})`;
                categoryFilter.appendChild(opt);
            });
        }

        /**
         * Render kartu metrik dan tabel berdasarkan filter pencarian & kategori
         */
        function renderDashboardView() {
            if (!cachedDashboardData) return;

            const { ringkasan, produk } = cachedDashboardData;

            // A. Update Kartu Metrik Ringkasan
            const elTotalPenjualan = document.getElementById('apiTotalPenjualan');
            const elPenjualanHariIni = document.getElementById('apiPenjualanHariIni');
            const elTotalTransaksi = document.getElementById('apiTotalTransaksi');
            const elTransaksiHariIni = document.getElementById('apiTransaksiHariIni');
            const elTotalProduk = document.getElementById('apiTotalProduk');
            const elTotalStok = document.getElementById('apiTotalStok');
            const elStatusRestock = document.getElementById('apiStatusRestock');
            const elDetailRestock = document.getElementById('apiDetailRestock');

            if (elTotalPenjualan) elTotalPenjualan.textContent = safeFormatRupiah(ringkasan.total_penjualan || 0);
            if (elPenjualanHariIni) elPenjualanHariIni.textContent = `Hari ini: ${safeFormatRupiah(ringkasan.penjualan_hari_ini || 0)}`;
            if (elTotalTransaksi) elTotalTransaksi.textContent = `${ringkasan.total_transaksi || 0} Trx`;
            if (elTransaksiHariIni) elTransaksiHariIni.textContent = `Hari ini: ${ringkasan.transaksi_hari_ini || 0} pesanan`;
            if (elTotalProduk) elTotalProduk.textContent = `${ringkasan.total_produk || 0} Menu`;
            if (elTotalStok) elTotalStok.textContent = `${ringkasan.total_stok || 0} porsi stok tersedia`;

            const totalPerluRestock = (ringkasan.produk_stok_menipis || 0) + (ringkasan.produk_habis || 0);
            if (elStatusRestock) elStatusRestock.textContent = `${totalPerluRestock} Menu`;
            if (elDetailRestock) {
                if (totalPerluRestock === 0) {
                    elDetailRestock.textContent = 'Semua stok menu aman';
                } else {
                    elDetailRestock.textContent = `${ringkasan.produk_stok_menipis || 0} menipis, ${ringkasan.produk_habis || 0} habis`;
                }
            }

            // B. Filter Produk Realtime
            const searchQuery = (searchInput ? searchInput.value : '').toLowerCase().trim();
            const selectedCategory = categoryFilter ? categoryFilter.value : 'all';
            const selectedStock = stockFilter ? stockFilter.value : 'all';

            const filteredProducts = (produk || []).filter(item => {
                // Filter pencarian teks
                const matchSearch = !searchQuery || 
                    item.nama_produk.toLowerCase().includes(searchQuery) ||
                    item.nama_kategori.toLowerCase().includes(searchQuery);

                // Filter kategori
                const matchCategory = (selectedCategory === 'all') || 
                    (item.nama_kategori === selectedCategory);

                // Filter status stok
                let matchStock = true;
                if (selectedStock === 'aman') {
                    matchStock = item.stok > 10;
                } else if (selectedStock === 'menipis') {
                    matchStock = item.stok > 0 && item.stok <= 10;
                } else if (selectedStock === 'habis') {
                    matchStock = item.stok <= 0;
                }

                return matchSearch && matchCategory && matchStock;
            });

            // C. Render Baris Tabel
            const tbody = document.getElementById('apiProductTableBody');
            if (!tbody) return;

            if (filteredProducts.length === 0) {
                tbody.innerHTML = '';
                if (emptyEl) emptyEl.classList.remove('hidden');
            } else {
                if (emptyEl) emptyEl.classList.add('hidden');

                tbody.innerHTML = filteredProducts.map(p => {
                    let badgeClass = 'badge-stock-aman';
                    let badgeLabel = '✓ Aman';

                    if (p.stok <= 0) {
                        badgeClass = 'badge-stock-habis';
                        badgeLabel = '✕ Habis';
                    } else if (p.stok <= 10) {
                        badgeClass = 'badge-stock-menipis';
                        badgeLabel = '⚠️ Menipis';
                    }

                    const formatSuhu = (suhu) => {
                        if (suhu === 'panas_dingin') return '🔥 Panas / ❄ Dingin';
                        if (suhu === 'dingin') return '❄ Dingin';
                        return 'Standard';
                    };

                    const photoUrl = p.gambar 
                        ? `${baseUrl}assets/uploads/produk/${p.gambar}` 
                        : `${baseUrl}images/default.jpg`;
                    const fallbackUrl = `${baseUrl}images/${p.gambar || 'default.jpg'}`;

                    return `
                        <tr>
                            <td style="text-align: center;">
                                <img src="${photoUrl}" 
                                     alt="${safeEscapeHtml(p.nama_produk)}" 
                                     class="product-thumb-sm" 
                                     onerror="this.onerror=null; this.src='${fallbackUrl}';">
                            </td>
                            <td>
                                <strong>${safeEscapeHtml(p.nama_produk)}</strong>
                            </td>
                            <td>
                                <span class="badge badge-info" style="font-size: 0.75rem;">
                                    ${safeEscapeHtml(p.nama_kategori)}
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--accent-orange); font-size: 0.9rem;">
                                    ${safeFormatRupiah(p.harga)}
                                </strong>
                            </td>
                            <td style="text-align: center; font-weight: 700;">
                                ${p.stok} porsi
                            </td>
                            <td style="text-align: center;">
                                <span class="badge ${badgeClass}" style="font-size: 0.75rem;">
                                    ${badgeLabel}
                                </span>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.82rem;">
                                ${formatSuhu(p.opsi_suhu)}
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        }

        // D. Pasang Event Listeners
        if (searchInput) {
            searchInput.addEventListener('input', renderDashboardView);
        }
        if (categoryFilter) {
            categoryFilter.addEventListener('change', renderDashboardView);
        }
        if (stockFilter) {
            stockFilter.addEventListener('change', renderDashboardView);
        }

        // Tombol Perbarui Data
        if (btnRefresh) {
            btnRefresh.addEventListener('click', () => {
                isSimulateErrorActive = false;
                fetchDashboardData();
            });
        }

        // Tombol Coba Lagi (Retry saat terjadi Error)
        if (btnRetry) {
            btnRetry.addEventListener('click', () => {
                fetchDashboardData();
            });
        }

        // Tombol Pengujian Error State (Latihan 3)
        if (btnSimulateError) {
            btnSimulateError.addEventListener('click', () => {
                isSimulateErrorActive = true;
                fetchDashboardData();
            });
        }

        // Tombol Pulihkan Endpoint Normal setelah simulasi error
        if (btnResetEndpoint) {
            btnResetEndpoint.addEventListener('click', () => {
                isSimulateErrorActive = false;
                fetchDashboardData();
            });
        }

        // Ambil data pertama kali saat halaman dimuat
        fetchDashboardData();
    }

    // Jalankan inisialisasi Mini Dashboard saat DOM siap
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMiniDashboard);
    } else {
        initMiniDashboard();
    }
})();

