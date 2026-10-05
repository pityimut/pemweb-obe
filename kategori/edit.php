<?php
/**
 * Edit Kategori Menu - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    set_flash('error', 'Kategori tidak valid.');
    header('Location: ' . BASE_URL . 'kategori/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM kategori WHERE id_kategori = ?");
$stmt->execute([$id]);
$kategori = $stmt->fetch();

if (!$kategori) {
    set_flash('error', 'Kategori tidak ditemukan.');
    header('Location: ' . BASE_URL . 'kategori/index.php');
    exit;
}

$page_title = 'Edit Kategori Menu';
$page_subtitle = 'Perbarui data kategori menu';
$current_page = 'kategori';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif']) ? $_POST['status'] : 'aktif';

    if (empty($nama_kategori)) {
        $error = 'Nama kategori wajib diisi.';
    } else {
        // Cek duplikasi dengan kategori lain
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM kategori WHERE LOWER(nama_kategori) = LOWER(?) AND id_kategori != ?");
        $stmtCheck->execute([$nama_kategori, $id]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = 'Kategori dengan nama tersebut sudah digunakan oleh kategori lain.';
        } else {
            $stmtUpdate = $pdo->prepare("UPDATE kategori SET nama_kategori = ?, deskripsi = ?, status = ? WHERE id_kategori = ?");
            if ($stmtUpdate->execute([$nama_kategori, $deskripsi, $status, $id])) {
                set_flash('success', "Kategori '{$nama_kategori}' berhasil diperbarui.");
                header('Location: ' . BASE_URL . 'kategori/index.php');
                exit;
            } else {
                $error = 'Gagal memperbarui data kategori.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Kategori: <?= htmlspecialchars($kategori['nama_kategori']) ?></h3>
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
                <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" required
                       value="<?= htmlspecialchars($_POST['nama_kategori'] ?? $kategori['nama_kategori']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($_POST['deskripsi'] ?? ($kategori['deskripsi'] ?? '')) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="aktif" <?= (($_POST['status'] ?? $kategori['status']) === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= (($_POST['status'] ?? $kategori['status']) === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <a href="<?= BASE_URL ?>kategori/index.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Perbarui Kategori</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
