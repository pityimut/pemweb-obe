<?php
/**
 * Tambah Produk Menu - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Tambah Produk Menu';
$page_subtitle = 'Tambahkan item menu baru ke katalog penjualan';
$current_page = 'produk';

$error = '';

// Ambil kategori aktif
$categories = $pdo->query("SELECT * FROM kategori WHERE status = 'aktif' ORDER BY nama_kategori ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $harga = (float)($_POST['harga'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $opsi_suhu = in_array($_POST['opsi_suhu'] ?? '', ['tidak_berlaku', 'panas_dingin', 'dingin']) ? $_POST['opsi_suhu'] : 'tidak_berlaku';
    $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif']) ? $_POST['status'] : 'aktif';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    // Validasi
    if (empty($nama_produk)) {
        $error = 'Nama produk wajib diisi.';
    } elseif ($id_kategori <= 0) {
        $error = 'Pilih kategori produk yang valid.';
    } elseif ($harga <= 0) {
        $error = 'Harga produk harus lebih dari 0.';
    } elseif ($stok < 0) {
        $error = 'Stok tidak boleh bernilai negatif.';
    } else {
        // Handle Upload Gambar
        $nama_file_gambar = 'mie-ayam-biasa.jpg'; // default placeholder jika tidak ada upload

        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['gambar']['tmp_name'];
            $fileName = $_FILES['gambar']['name'];
            $fileSize = $_FILES['gambar']['size'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($fileExt, $allowedExts)) {
                $error = 'Format file gambar tidak valid. Gunakan JPG, JPEG, PNG, atau WEBP.';
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $error = 'Ukuran gambar maksimal 2 MB.';
            } else {
                // Generate safe unique filename
                $newFileName = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                $uploadDir = __DIR__ . '/../assets/uploads/produk/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $destPath = $uploadDir . $newFileName;
                if (move_uploaded_file($fileTmp, $destPath)) {
                    $nama_file_gambar = $newFileName;
                } else {
                    $error = 'Gagal mengunggah file gambar ke server.';
                }
            }
        }

        if (empty($error)) {
            $sql = "INSERT INTO produk (id_kategori, nama_produk, harga, gambar, deskripsi, stok, opsi_suhu, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$id_kategori, $nama_produk, $harga, $nama_file_gambar, $deskripsi, $stok, $opsi_suhu, $status])) {
                set_flash('success', "Produk '{$nama_produk}' berhasil ditambahkan ke menu.");
                header('Location: ' . BASE_URL . 'produk/index.php');
                exit;
            } else {
                $error = 'Gagal menyimpan data produk ke database.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 750px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Form Tambah Produk Baru</h3>
            <a href="<?= BASE_URL ?>produk/index.php" class="btn btn-secondary btn-sm">
                ← Kembali
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span>⚠️</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" for="nama_produk">Nama Produk <span style="color:var(--status-danger)">*</span></label>
                    <input type="text" id="nama_produk" name="nama_produk" class="form-control" 
                           placeholder="Contoh: Mie Ayam Spesial" required autofocus
                           value="<?= isset($_POST['nama_produk']) ? htmlspecialchars($_POST['nama_produk']) : '' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="id_kategori">Kategori <span style="color:var(--status-danger)">*</span></label>
                    <select id="id_kategori" name="id_kategori" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id_kategori'] ?>" <?= (isset($_POST['id_kategori']) && $_POST['id_kategori'] == $cat['id_kategori']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="harga">Harga (Rp) <span style="color:var(--status-danger)">*</span></label>
                    <input type="number" id="harga" name="harga" class="form-control" 
                           placeholder="Contoh: 20000" min="100" step="500" required
                           value="<?= isset($_POST['harga']) ? htmlspecialchars($_POST['harga']) : '' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="stok">Stok Porsi Awal <span style="color:var(--status-danger)">*</span></label>
                    <input type="number" id="stok" name="stok" class="form-control" 
                           placeholder="Contoh: 50" min="0" required
                           value="<?= isset($_POST['stok']) ? htmlspecialchars($_POST['stok']) : '50' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="opsi_suhu">Opsi Suhu <span style="color:var(--status-danger)">*</span></label>
                    <select id="opsi_suhu" name="opsi_suhu" class="form-select" required>
                        <option value="tidak_berlaku" <?= (isset($_POST['opsi_suhu']) && $_POST['opsi_suhu'] === 'tidak_berlaku') ? 'selected' : '' ?>>Tidak Berlaku (Makanan)</option>
                        <option value="panas_dingin" <?= (isset($_POST['opsi_suhu']) && $_POST['opsi_suhu'] === 'panas_dingin') ? 'selected' : '' ?>>Panas & Dingin (Minuman Fleksibel)</option>
                        <option value="dingin" <?= (isset($_POST['opsi_suhu']) && $_POST['opsi_suhu'] === 'dingin') ? 'selected' : '' ?>>Khusus Dingin (Misal: Air Es)</option>
                    </select>
                    <small class="form-text">Pilihan suhu akan memicu modal pilihan panas/dingin saat kasir memesan.</small>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" for="gambar">Foto Produk Menu</label>
                    <input type="file" id="gambar" name="gambar" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <small class="form-text">Format didukung: JPG, JPEG, PNG, WEBP (Maksimal 2 MB). Jika kosong, akan memakai foto default.</small>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" for="deskripsi">Deskripsi Menu</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3" 
                              placeholder="Deskripsi singkat rasa, porsi, atau komposisi menu"><?= isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : '' ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status Menu</label>
                    <select id="status" name="status" class="form-select">
                        <option value="aktif" <?= (isset($_POST['status']) && $_POST['status'] === 'aktif') ? 'selected' : '' ?>>Aktif (Tampil di Kasir)</option>
                        <option value="nonaktif" <?= (isset($_POST['status']) && $_POST['status'] === 'nonaktif') ? 'selected' : '' ?>>Nonaktif (Disembunyikan)</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <a href="<?= BASE_URL ?>produk/index.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
