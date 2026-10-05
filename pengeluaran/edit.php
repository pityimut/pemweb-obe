<?php
/**
 * Edit Catatan Pengeluaran - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    set_flash('error', 'Catatan pengeluaran tidak valid.');
    header('Location: ' . BASE_URL . 'pengeluaran/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pengeluaran WHERE id_pengeluaran = ?");
$stmt->execute([$id]);
$expense = $stmt->fetch();

if (!$expense) {
    set_flash('error', 'Data pengeluaran tidak ditemukan.');
    header('Location: ' . BASE_URL . 'pengeluaran/index.php');
    exit;
}

$page_title = 'Edit Pengeluaran';
$page_subtitle = 'Perbarui catatan biaya operasional';
$current_page = 'pengeluaran';

$error = '';
$categories = $pdo->query("SELECT * FROM kategori_pengeluaran ORDER BY nama_kategori ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kategori = (int)($_POST['id_kategori_pengeluaran'] ?? 0);
    $tanggal = trim($_POST['tanggal'] ?? '');
    $jumlah = (float)($_POST['jumlah'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($id_kategori <= 0) {
        $error = 'Pilih kategori pengeluaran yang valid.';
    } elseif (empty($tanggal)) {
        $error = 'Tanggal pengeluaran wajib diisi.';
    } elseif ($jumlah <= 0) {
        $error = 'Jumlah pengeluaran harus lebih dari Rp0.';
    } else {
        $stmtUpdate = $pdo->prepare("
            UPDATE pengeluaran 
            SET id_kategori_pengeluaran = ?, tanggal = ?, jumlah = ?, keterangan = ?
            WHERE id_pengeluaran = ?
        ");
        if ($stmtUpdate->execute([$id_kategori, $tanggal, $jumlah, $keterangan, $id])) {
            set_flash('success', 'Catatan pengeluaran berhasil diperbarui.');
            header('Location: ' . BASE_URL . 'pengeluaran/index.php');
            exit;
        } else {
            $error = 'Gagal memperbarui catatan pengeluaran.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Catatan Pengeluaran</h3>
            <a href="<?= BASE_URL ?>pengeluaran/index.php" class="btn btn-secondary btn-sm">
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
                <label class="form-label" for="tanggal">Tanggal Pengeluaran <span style="color:var(--status-danger)">*</span></label>
                <input type="date" id="tanggal" name="tanggal" class="form-control" required
                       value="<?= htmlspecialchars($_POST['tanggal'] ?? $expense['tanggal']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="id_kategori_pengeluaran">Kategori Biaya <span style="color:var(--status-danger)">*</span></label>
                <select id="id_kategori_pengeluaran" name="id_kategori_pengeluaran" class="form-select" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id_kategori_pengeluaran'] ?>" 
                            <?= (($_POST['id_kategori_pengeluaran'] ?? $expense['id_kategori_pengeluaran']) == $c['id_kategori_pengeluaran']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="jumlah">Jumlah Biaya (Rp) <span style="color:var(--status-danger)">*</span></label>
                <input type="number" id="jumlah" name="jumlah" class="form-control" min="500" step="500" required
                       value="<?= htmlspecialchars($_POST['jumlah'] ?? $expense['jumlah']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="keterangan">Keterangan / Rincian Belanja</label>
                <textarea id="keterangan" name="keterangan" class="form-control" rows="3"><?= htmlspecialchars($_POST['keterangan'] ?? ($expense['keterangan'] ?? '')) ?></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <a href="<?= BASE_URL ?>pengeluaran/index.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
