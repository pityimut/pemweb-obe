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
   PRAKTIKUM 6 - NOMOR 5: CLIENT-SIDE FORM VALIDATION
   Warung Makan Hanisa POS System
   Mahasiswa : Fidiani (NPM: 2440304022)
   ========================================================================== */

/**
 * A. readFormData(form)
 * Membaca dan menormalisasi seluruh data dari form transaksi kasir.
 * Mengikuti prinsip:
 * - string -> trim()
 * - angka  -> Number()
 * - opsi   -> ambil value
 * - tanggal-> ambil value
 * @param {HTMLFormElement} form
 * @returns {Object} Data transaksi yang ternormalisasi
 */
function readFormData(form) {
    if (!form) return {};

    // 1. Tanggal Transaksi
    const tanggalInput = form.querySelector('#tanggal');
    const tanggal = tanggalInput ? tanggalInput.value.trim() : '';

    // 2. Jumlah Pesanan & Total Menu
    const jumlahInput = form.querySelector('#inputJumlahPesanan');
    const jumlahRaw = jumlahInput ? jumlahInput.value.trim() : '0';
    const jumlah = (jumlahRaw !== '' && !isNaN(Number(jumlahRaw))) ? Number(jumlahRaw) : 0;

    // Ambil jumlah menu dari cart global jika tersedia
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

    // 3. Metode Pembayaran (Cash / QRIS)
    const radioMetode = form.querySelector('input[name="metode_pembayaran"]:checked');
    let metode = radioMetode ? radioMetode.value.trim().toLowerCase() : '';
    if (!metode && typeof currentPaymentMethod !== 'undefined' && currentPaymentMethod) {
        metode = currentPaymentMethod.toLowerCase();
    }

    // 4. Nominal Pembayaran Cash
    const nominalInput = form.querySelector('#inputNominalCash');
    const nominalRaw = nominalInput ? nominalInput.value.trim() : '';
    const nominalCash = (nominalRaw !== '' && !isNaN(Number(nominalRaw))) ? Number(nominalRaw) : NaN;

    // 5. Catatan Pesanan
    const catatanInput = form.querySelector('#inputCatatanPesanan');
    const catatan = catatanInput ? catatanInput.value.trim() : '';

    return {
        tanggal: tanggal,
        menuCount: menuCount,
        jumlah: jumlah,
        totalTransaksi: totalTransaksi,
        metode_pembayaran: metode,
        nominal_cash: nominalCash,
        nominalRaw: nominalRaw,
        catatan: catatan
    };
}

/**
 * B. validate(data)
 * Memvalidasi data transaksi kasir dengan minimal 5 aturan validasi spesifik.
 * @param {Object} data
 * @returns {Object} errors - Pasangan field error dan pesan spesifik
 */
function validate(data) {
    const errors = {};

    // Aturan 1: Tanggal transaksi wajib diisi
    if (!data.tanggal || data.tanggal === '') {
        errors.tanggal = 'Tanggal transaksi wajib diisi.';
    }

    // Aturan 2: Menu / produk wajib dipilih
    if (!data.menuCount || data.menuCount <= 0) {
        errors.menu = 'Menu makanan atau minuman wajib dipilih.';
    }

    // Aturan 3: Jumlah pesanan wajib diisi, berupa angka, dan minimal 1
    if (data.jumlah === undefined || data.jumlah === null || isNaN(data.jumlah) || data.jumlah < 1) {
        errors.jumlah = 'Jumlah pesanan wajib diisi dan minimal 1.';
    }

    // Aturan 4: Metode pembayaran wajib dipilih (hanya Cash atau QRIS)
    if (!data.metode_pembayaran || (data.metode_pembayaran !== 'cash' && data.metode_pembayaran !== 'qris')) {
        errors.metode = 'Metode pembayaran wajib dipilih.';
    }

    // Aturan 5: Nominal pembayaran Cash
    // Jika metode Cash: nominal wajib diisi dan tidak boleh kurang dari total transaksi.
    // Jika QRIS: aturan nominal Cash tidak diuji.
    if (data.metode_pembayaran === 'cash') {
        if (data.nominalRaw === '' || isNaN(data.nominal_cash)) {
            errors.nominal = 'Nominal pembayaran wajib diisi untuk pembayaran Cash.';
        } else if (data.nominal_cash < data.totalTransaksi) {
            errors.nominal = 'Nominal pembayaran tidak boleh kurang dari total transaksi.';
        }
    }

    // Aturan 6 (Tambahan): Panjang catatan pesanan maksimal 255 karakter
    if (data.catatan && data.catatan.length > 255) {
        errors.catatan = 'Catatan pesanan maksimal 255 karakter.';
    }

    return errors;
}

/**
 * C. renderErrors(errors)
 * Menampilkan pesan error dan error summary dengan standar aksesibilitas:
 * - Reset error lama & aria-invalid
 * - Menampilkan teks error dekat field
 * - Memberi aria-invalid="true" pada field invalid
 * - Menampilkan error summary di atas form
 * - Fokus otomatis ke field error pertama
 * @param {Object} errors
 */
function renderErrors(errors) {
    const fieldMap = [
        { key: 'tanggal', inputId: 'tanggal', errorId: 'tanggal-error' },
        { key: 'menu', inputId: 'inputJumlahPesanan', errorId: 'jumlah-error' },
        { key: 'jumlah', inputId: 'inputJumlahPesanan', errorId: 'jumlah-error' },
        { key: 'metode', inputId: 'metodeCash', errorId: 'metode-error' },
        { key: 'nominal', inputId: 'inputNominalCash', errorId: 'nominal-error' },
        { key: 'catatan', inputId: 'inputCatatanPesanan', errorId: 'catatan-error' }
    ];

    // 1 & 2. Reset pesan error lama dan status invalid
    fieldMap.forEach(item => {
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
        return; // Tidak ada error, form valid
    }

    let firstErrorElement = null;
    const summaryList = [];

    // Prioritas urutan tampilan feedback error & fokus
    const priority = ['tanggal', 'menu', 'jumlah', 'metode', 'nominal', 'catatan'];

    priority.forEach(key => {
        if (errors[key]) {
            const message = errors[key];
            summaryList.push(message);

            const mapItem = fieldMap.find(m => m.key === key);
            if (mapItem) {
                const errEl = document.getElementById(mapItem.errorId);
                const inputEl = document.getElementById(mapItem.inputId);

                // 3 & 7 & 8. Tampilkan pesan teks error dekat field
                if (errEl) {
                    errEl.innerText = message;
                    errEl.style.display = 'block';
                }

                // 4. Tambahkan aria-invalid="true" pada field error
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

    // 6 & 8. Isi dan buka error summary di atas form
    if (summaryEl && summaryList.length > 0) {
        summaryEl.hidden = false;
        let html = '<div style="font-weight: 700; margin-bottom: 0.35rem;">⚠️ Terdapat ' + summaryList.length + ' kesalahan pada form transaksi:</div>';
        html += '<ul style="margin: 0; padding-left: 1.25rem;">';
        summaryList.forEach(msg => {
            html += '<li>' + escapeHtml(msg) + '</li>';
        });
        html += '</ul>';
        summaryEl.innerHTML = html;
    }

    // 9. Fokus ke elemen error pertama
    if (firstErrorElement) {
        if (firstErrorElement.type === 'hidden') {
            const summaryBadge = document.getElementById('modalItemsSummary') || document.getElementById('btnOpenCheckout');
            if (summaryBadge) {
                summaryBadge.setAttribute('tabindex', '-1');
                summaryBadge.focus();
            }
        } else {
            firstErrorElement.focus();
        }
    }
}

// D. Event Listener Submit Form Transaksi
document.addEventListener('DOMContentLoaded', function () {
    const formTransaksi = document.getElementById('formTransaksi');
    if (formTransaksi) {
        formTransaksi.addEventListener('submit', function (e) {
            e.preventDefault();

            const data = readFormData(formTransaksi);
            const errors = validate(data);

            if (Object.keys(errors).length > 0) {
                renderErrors(errors);
                return; // Hentikan proses jika form tidak valid
            }

            // Bersihkan error jika valid
            renderErrors({});

            // Lanjutkan proses submit transaksi yang sudah ada
            if (typeof submitTransaction === 'function') {
                submitTransaction();
            }
        });
    }
});
