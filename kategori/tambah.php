<?php
/**
 * Tambah Kategori Menu - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Tambah Kategori Menu';
$page_subtitle = 'Tambahkan kategori baru untuk makanan atau minuman';
$current_page = 'kategori';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif']) ? $_POST['status'] : 'aktif';

    if (empty($nama_kategori)) {
        $error = 'Nama kategori wajib diisi.';
    } else {
        // Cek duplikasi nama kategori
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM kategori WHERE LOWER(nama_kategori) = LOWER(?)");
        $stmtCheck->execute([$nama_kategori]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = 'Kategori dengan nama tersebut sudah ada.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, deskripsi, status) VALUES (?, ?, ?)");
            if ($stmt->execute([$nama_kategori, $deskripsi, $status])) {
                set_flash('success', "Kategori '{$nama_kategori}' berhasil ditambahkan.");
                header('Location: ' . BASE_URL . 'kategori/index.php');
                exit;
            } else {
                $error = 'Gagal menyimpan kategori ke database.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Form Tambah Kategori</h3>
            <a href="<?= BASE_URL ?>kategori/index.php" class="btn btn-secondary btn-sm">
                ← Kembali
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span>⚠️</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="nama_kategori">Nama Kategori <span style="color:var(--status-danger)">*</span></label>
                <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" 
                       placeholder="Contoh: Makanan, Minuman, Camilan" required autofocus
                       value="<?= isset($_POST['nama_kategori']) ? htmlspecialchars($_POST['nama_kategori']) : '' ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3" 
                          placeholder="Penjelasan singkat mengenai kategori menu ini"><?= isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : '' ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="aktif" <?= (isset($_POST['status']) && $_POST['status'] === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= (isset($_POST['status']) && $_POST['status'] === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <a href="<?= BASE_URL ?>kategori/index.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
