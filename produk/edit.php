<?php
/**
 * Edit Produk Menu - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    set_flash('error', 'Produk tidak valid.');
    header('Location: ' . BASE_URL . 'produk/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM produk WHERE id_produk = ?");
$stmt->execute([$id]);
$produk = $stmt->fetch();

if (!$produk) {
    set_flash('error', 'Produk tidak ditemukan.');
    header('Location: ' . BASE_URL . 'produk/index.php');
    exit;
}

$page_title = 'Edit Produk Menu';
$page_subtitle = 'Perbarui data menu makanan atau minuman';
$current_page = 'produk';

$error = '';
$categories = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $harga = (float)($_POST['harga'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $opsi_suhu = in_array($_POST['opsi_suhu'] ?? '', ['tidak_berlaku', 'panas_dingin', 'dingin']) ? $_POST['opsi_suhu'] : 'tidak_berlaku';
    $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif']) ? $_POST['status'] : 'aktif';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (empty($nama_produk)) {
        $error = 'Nama produk wajib diisi.';
    } elseif ($id_kategori <= 0) {
        $error = 'Pilih kategori produk yang valid.';
    } elseif ($harga <= 0) {
        $error = 'Harga produk harus lebih dari 0.';
    } elseif ($stok < 0) {
        $error = 'Stok tidak boleh bernilai negatif.';
    } else {
        $nama_file_gambar = $produk['gambar'];

        // Cek jika kasir/admin mengupload gambar baru
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
                $newFileName = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                $uploadDir = __DIR__ . '/../assets/uploads/produk/';
                $destPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmp, $destPath)) {
                    $nama_file_gambar = $newFileName;
                } else {
                    $error = 'Gagal mengunggah file gambar baru.';
                }
            }
        }

        if (empty($error)) {
            $sql = "UPDATE produk SET 
                        id_kategori = ?, 
                        nama_produk = ?, 
                        harga = ?, 
                        gambar = ?, 
                        deskripsi = ?, 
                        stok = ?, 
                        opsi_suhu = ?, 
                        status = ? 
                    WHERE id_produk = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$id_kategori, $nama_produk, $harga, $nama_file_gambar, $deskripsi, $stok, $opsi_suhu, $status, $id])) {
                set_flash('success', "Produk '{$nama_produk}' berhasil diperbarui.");
                header('Location: ' . BASE_URL . 'produk/index.php');
                exit;
            } else {
                $error = 'Gagal memperbarui data produk di database.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 750px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Produk: <?= htmlspecialchars($produk['nama_produk']) ?></h3>
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
                    <input type="text" id="nama_produk" name="nama_produk" class="form-control" required
                           value="<?= htmlspecialchars($_POST['nama_produk'] ?? $produk['nama_produk']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="id_kategori">Kategori <span style="color:var(--status-danger)">*</span></label>
                    <select id="id_kategori" name="id_kategori" class="form-select" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id_kategori'] ?>" 
                                <?= (($_POST['id_kategori'] ?? $produk['id_kategori']) == $cat['id_kategori']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="harga">Harga (Rp) <span style="color:var(--status-danger)">*</span></label>
                    <input type="number" id="harga" name="harga" class="form-control" min="100" step="500" required
                           value="<?= htmlspecialchars($_POST['harga'] ?? $produk['harga']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="stok">Stok Porsi Saat Ini <span style="color:var(--status-danger)">*</span></label>
                    <input type="number" id="stok" name="stok" class="form-control" min="0" required
                           value="<?= htmlspecialchars($_POST['stok'] ?? $produk['stok']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="opsi_suhu">Opsi Suhu <span style="color:var(--status-danger)">*</span></label>
                    <select id="opsi_suhu" name="opsi_suhu" class="form-select" required>
                        <option value="tidak_berlaku" <?= (($_POST['opsi_suhu'] ?? $produk['opsi_suhu']) === 'tidak_berlaku') ? 'selected' : '' ?>>Tidak Berlaku (Makanan)</option>
                        <option value="panas_dingin" <?= (($_POST['opsi_suhu'] ?? $produk['opsi_suhu']) === 'panas_dingin') ? 'selected' : '' ?>>Panas & Dingin (Minuman Fleksibel)</option>
                        <option value="dingin" <?= (($_POST['opsi_suhu'] ?? $produk['opsi_suhu']) === 'dingin') ? 'selected' : '' ?>>Khusus Dingin (Misal: Air Es)</option>
                    </select>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Foto Saat Ini</label>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                        <img src="<?= BASE_URL . 'assets/uploads/produk/' . htmlspecialchars($produk['gambar']) ?>" 
                             style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-md); border: 2px solid var(--border-color);"
                             onerror="this.src='<?= BASE_URL ?>assets/uploads/produk/mie-ayam-biasa.jpg'">
                        <span style="font-size: 0.85rem; color: var(--text-muted);"><?= htmlspecialchars($produk['gambar']) ?></span>
                    </div>

                    <label class="form-label" for="gambar">Ganti Foto Menu (Opsional)</label>
                    <input type="file" id="gambar" name="gambar" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <small class="form-text">Biarkan kosong jika tidak ingin mengganti foto produk.</small>
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label" for="deskripsi">Deskripsi Menu</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($_POST['deskripsi'] ?? ($produk['deskripsi'] ?? '')) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status Menu</label>
                    <select id="status" name="status" class="form-select">
                        <option value="aktif" <?= (($_POST['status'] ?? $produk['status']) === 'aktif') ? 'selected' : '' ?>>Aktif (Tampil di Kasir)</option>
                        <option value="nonaktif" <?= (($_POST['status'] ?? $produk['status']) === 'nonaktif') ? 'selected' : '' ?>>Nonaktif (Disembunyikan)</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <a href="<?= BASE_URL ?>produk/index.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
