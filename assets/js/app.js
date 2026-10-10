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
