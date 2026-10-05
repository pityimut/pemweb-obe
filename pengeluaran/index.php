<?php
/**
 * Data Pengeluaran Operasional - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Pengeluaran Warung';
$page_subtitle = 'Pencatatan biaya bahan baku, gas, listrik, dan operasional';
$current_page = 'pengeluaran';

// Filter
$kategori_filter = (int)($_GET['kategori'] ?? 0);
$tgl_mulai = trim($_GET['tgl_mulai'] ?? '');
$tgl_selesai = trim($_GET['tgl_selesai'] ?? '');

$sql = "
    SELECT p.*, kp.nama_kategori, u.nama AS nama_user
    FROM pengeluaran p
    JOIN kategori_pengeluaran kp ON p.id_kategori_pengeluaran = kp.id_kategori_pengeluaran
    JOIN users u ON p.id_user = u.id_user
    WHERE 1=1
";
$params = [];

if ($kategori_filter > 0) {
    $sql .= " AND p.id_kategori_pengeluaran = ?";
    $params[] = $kategori_filter;
}

if (!empty($tgl_mulai)) {
    $sql .= " AND p.tanggal >= ?";
    $params[] = $tgl_mulai;
}

if (!empty($tgl_selesai)) {
    $sql .= " AND p.tanggal <= ?";
    $params[] = $tgl_selesai;
}

$sql .= " ORDER BY p.tanggal DESC, p.id_pengeluaran DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Total pengeluaran
$totalPengeluaran = 0;
foreach ($expenses as $ex) {
    $totalPengeluaran += (float)$ex['jumlah'];
}

$categories = $pdo->query("SELECT * FROM kategori_pengeluaran ORDER BY nama_kategori ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="metric-card" style="margin-bottom: 1.5rem;">
    <div class="metric-icon-wrap metric-icon-pengeluaran">
        💸
    </div>
    <div class="metric-body">
        <div class="metric-label">Total Pengeluaran (Sesuai Filter)</div>
        <div class="metric-value"><?= format_rupiah($totalPengeluaran) ?></div>
        <div class="metric-sub"><?= count($expenses) ?> pos pengeluaran tercatat</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Pengeluaran Warung</h3>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?= BASE_URL ?>kategori_pengeluaran/index.php" class="btn btn-secondary btn-sm">
                🏷️ Kelola Kategori
            </a>
            <a href="<?= BASE_URL ?>pengeluaran/tambah.php" class="btn btn-primary btn-sm">
                ➕ Catat Pengeluaran
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="" style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div style="flex: 1; min-width: 150px;">
            <select name="kategori" class="form-select">
                <option value="0">Semua Kategori Biaya</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id_kategori_pengeluaran'] ?>" <?= ($kategori_filter == $c['id_kategori_pengeluaran']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="flex: 1; min-width: 140px;">
            <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tgl_mulai) ?>" title="Tanggal Mulai">
        </div>

        <div style="flex: 1; min-width: 140px;">
            <input type="date" name="tgl_selesai" class="form-control" value="<?= htmlspecialchars($tgl_selesai) ?>" title="Tanggal Selesai">
        </div>

        <button type="submit" class="btn btn-secondary">Filter</button>
        <?php if ($kategori_filter > 0 || !empty($tgl_mulai) || !empty($tgl_selesai)): ?>
            <a href="<?= BASE_URL ?>pengeluaran/index.php" class="btn btn-outline-danger">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Tanggal</th>
                    <th>Kategori Biaya</th>
                    <th>Keterangan</th>
                    <th>Dicatat Oleh</th>
                    <th style="text-align: right;">Jumlah</th>
                    <th style="width: 140px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($expenses)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            Belum ada catatan pengeluaran pada filter ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($expenses as $idx => $ex): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><?= date('d/m/Y', strtotime($ex['tanggal'])) ?></td>
                            <td><span class="badge badge-warning"><?= htmlspecialchars($ex['nama_kategori']) ?></span></td>
                            <td><?= htmlspecialchars($ex['keterangan'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($ex['nama_user']) ?></td>
                            <td style="text-align: right;">
                                <strong style="color: var(--status-danger); font-family: 'Outfit';">
                                    -<?= format_rupiah($ex['jumlah']) ?>
                                </strong>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <a href="<?= BASE_URL ?>pengeluaran/edit.php?id=<?= $ex['id_pengeluaran'] ?>" class="btn btn-secondary btn-sm" title="Edit">
                                        ✏️
                                    </a>
                                    <a href="<?= BASE_URL ?>pengeluaran/hapus.php?id=<?= $ex['id_pengeluaran'] ?>" class="btn btn-outline-danger btn-sm" title="Hapus"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data pengeluaran ini?')">
                                        🗑️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
