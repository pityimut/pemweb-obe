<?php
/**
 * Kategori Pengeluaran Operasional - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Kategori Pengeluaran';
$page_subtitle = 'Kelola pos kategori biaya operasional warung';
$current_page = 'pengeluaran';

$error = '';

// Proses Tambah Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nama = trim($_POST['nama_kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (empty($nama)) {
        $error = 'Nama kategori wajib diisi.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO kategori_pengeluaran (nama_kategori, deskripsi) VALUES (?, ?)");
        if ($stmt->execute([$nama, $deskripsi])) {
            set_flash('success', "Kategori pengeluaran '{$nama}' berhasil ditambahkan.");
            header('Location: ' . BASE_URL . 'kategori_pengeluaran/index.php');
            exit;
        } else {
            $error = 'Gagal menyimpan kategori pengeluaran.';
        }
    }
}

// Proses Hapus Kategori
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmtC = $pdo->prepare("SELECT COUNT(*) FROM pengeluaran WHERE id_kategori_pengeluaran = ?");
    $stmtC->execute([$delId]);
    if ($stmtC->fetchColumn() > 0) {
        set_flash('error', 'Kategori ini tidak dapat dihapus karena sudah memiliki riwayat catatan pengeluaran.');
    } else {
        $stmtD = $pdo->prepare("DELETE FROM kategori_pengeluaran WHERE id_kategori_pengeluaran = ?");
        $stmtD->execute([$delId]);
        set_flash('success', 'Kategori pengeluaran berhasil dihapus.');
    }
    header('Location: ' . BASE_URL . 'kategori_pengeluaran/index.php');
    exit;
}

// Ambil daftar kategori beserta total pemakaian
$categories = $pdo->query("
    SELECT kp.*, COUNT(p.id_pengeluaran) AS total_pemakaian, COALESCE(SUM(p.jumlah), 0) AS total_biaya
    FROM kategori_pengeluaran kp
    LEFT JOIN pengeluaran p ON kp.id_kategori_pengeluaran = p.id_kategori_pengeluaran
    GROUP BY kp.id_kategori_pengeluaran
    ORDER BY kp.id_kategori_pengeluaran ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; align-items: start;">
    <!-- Tabel Kategori -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Kategori Pengeluaran</h3>
            <a href="<?= BASE_URL ?>pengeluaran/index.php" class="btn btn-secondary btn-sm">
                ← Kembali ke Pengeluaran
            </a>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th>Transaksi</th>
                        <th style="text-align: right;">Total Biaya</th>
                        <th style="width: 80px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $idx => $cat): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($cat['nama_kategori']) ?></strong></td>
                            <td><?= htmlspecialchars($cat['deskripsi'] ?? '-') ?></td>
                            <td><span class="badge badge-info"><?= $cat['total_pemakaian'] ?> catatan</span></td>
                            <td style="text-align: right; font-weight: 700; color: var(--status-danger);">
                                <?= format_rupiah($cat['total_biaya']) ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($cat['total_pemakaian'] == 0): ?>
                                    <a href="<?= BASE_URL ?>kategori_pengeluaran/index.php?delete=<?= $cat['id_kategori_pengeluaran'] ?>" 
                                       class="btn btn-outline-danger btn-sm" title="Hapus"
                                       onclick="return confirm('Hapus kategori ini?')">
                                        🗑️
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--text-light); font-size: 0.8rem;">Terkunci</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form Tambah Cepat -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tambah Kategori</h3>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span>⚠️</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label class="form-label" for="nama_kategori">Nama Kategori <span style="color:var(--status-danger)">*</span></label>
                <input type="text" id="nama_kategori" name="nama_kategori" class="form-control" 
                       placeholder="Misal: Promosi / Iklan" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="deskripsi">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3" 
                          placeholder="Penjelasan kategori biaya"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Tambah Kategori
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
