<?php
/**
 * Catat Pengeluaran Baru - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Catat Pengeluaran';
$page_subtitle = 'Input biaya operasional, pembelian bahan baku, dll.';
$current_page = 'pengeluaran';

$error = '';
$categories = $pdo->query("SELECT * FROM kategori_pengeluaran ORDER BY nama_kategori ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kategori = (int)($_POST['id_kategori_pengeluaran'] ?? 0);
    $tanggal = trim($_POST['tanggal'] ?? '');
    $jumlah = (float)($_POST['jumlah'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');
    $id_user = (int)$_SESSION['user_id'];

    if ($id_kategori <= 0) {
        $error = 'Pilih kategori pengeluaran yang valid.';
    } elseif (empty($tanggal)) {
        $error = 'Tanggal pengeluaran wajib diisi.';
    } elseif ($jumlah <= 0) {
        $error = 'Jumlah pengeluaran harus lebih dari Rp0.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO pengeluaran (id_user, id_kategori_pengeluaran, tanggal, jumlah, keterangan)
            VALUES (?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([$id_user, $id_kategori, $tanggal, $jumlah, $keterangan])) {
            set_flash('success', 'Catatan pengeluaran sebesar ' . format_rupiah($jumlah) . ' berhasil disimpan.');
            header('Location: ' . BASE_URL . 'pengeluaran/index.php');
            exit;
        } else {
            $error = 'Gagal menyimpan data pengeluaran ke database.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Form Catat Pengeluaran</h3>
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
                       value="<?= isset($_POST['tanggal']) ? htmlspecialchars($_POST['tanggal']) : date('Y-m-d') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="id_kategori_pengeluaran">Kategori Biaya <span style="color:var(--status-danger)">*</span></label>
                <select id="id_kategori_pengeluaran" name="id_kategori_pengeluaran" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id_kategori_pengeluaran'] ?>" <?= (isset($_POST['id_kategori_pengeluaran']) && $_POST['id_kategori_pengeluaran'] == $c['id_kategori_pengeluaran']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="jumlah">Jumlah Biaya (Rp) <span style="color:var(--status-danger)">*</span></label>
                <input type="number" id="jumlah" name="jumlah" class="form-control" placeholder="Contoh: 150000" min="500" step="500" required
                       value="<?= isset($_POST['jumlah']) ? htmlspecialchars($_POST['jumlah']) : '' ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="keterangan">Keterangan / Rincian Belanja</label>
                <textarea id="keterangan" name="keterangan" class="form-control" rows="3" 
                          placeholder="Misal: Beli ayam 5 kg dan bumbu rempah di pasar"><?= isset($_POST['keterangan']) ? htmlspecialchars($_POST['keterangan']) : '' ?></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
                <a href="<?= BASE_URL ?>pengeluaran/index.php" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Pengeluaran</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
