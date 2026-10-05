<?php
/**
 * Riwayat Transaksi Penjualan - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$page_title = 'Riwayat Transaksi';
$page_subtitle = 'Daftar semua transaksi penjualan kasir Warung Hanisa';
$current_page = 'transaksi';

$role = $_SESSION['role'] ?? 'kasir';
$userId = (int)$_SESSION['user_id'];

// Filter & Search
$search = trim($_GET['search'] ?? '');
$tgl_mulai = trim($_GET['tgl_mulai'] ?? '');
$tgl_selesai = trim($_GET['tgl_selesai'] ?? '');
$metode_filter = trim($_GET['metode'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$sql = "
    SELECT p.*, u.nama AS nama_kasir, pm.metode_pembayaran, pm.nominal_diterima, pm.kembalian, pm.status_pembayaran
    FROM pesanan p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan
    WHERE 1=1
";
$params = [];

// Jika kasir biasa, bisa melihat semua riwayat atau riwayat miliknya (sesuai kebutuhan kasir)
// Kasir di kafe umumnya melihat riwayat pesanan hari ini/semua pesanan toko
if (!empty($search)) {
    $sql .= " AND (p.nomor_pesanan LIKE ? OR u.nama LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($tgl_mulai)) {
    $sql .= " AND DATE(p.tanggal) >= ?";
    $params[] = $tgl_mulai;
}

if (!empty($tgl_selesai)) {
    $sql .= " AND DATE(p.tanggal) <= ?";
    $params[] = $tgl_selesai;
}

if (!empty($metode_filter)) {
    $sql .= " AND pm.metode_pembayaran = ?";
    $params[] = $metode_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND p.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY p.id_pesanan DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Hitung total ringkasan dari hasil filter
$totalNilaiFilter = 0;
foreach ($orders as $o) {
    if ($o['status'] !== 'batal') {
        $totalNilaiFilter += $o['total'];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Riwayat Transaksi (<?= count($orders) ?> Data)</h3>
        <div style="font-weight: 700; color: var(--accent-orange); font-size: 1.05rem;">
            Total Omzet: <?= format_rupiah($totalNilaiFilter) ?>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="" style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="search" class="form-control" 
                   placeholder="Cari nomor pesanan atau kasir..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <div style="flex: 1; min-width: 140px;">
            <input type="date" name="tgl_mulai" class="form-control" 
                   value="<?= htmlspecialchars($tgl_mulai) ?>" title="Tanggal Mulai">
        </div>

        <div style="flex: 1; min-width: 140px;">
            <input type="date" name="tgl_selesai" class="form-control" 
                   value="<?= htmlspecialchars($tgl_selesai) ?>" title="Tanggal Selesai">
        </div>

        <div style="width: 140px;">
            <select name="metode" class="form-select">
                <option value="">Semua Metode</option>
                <option value="cash" <?= ($metode_filter === 'cash') ? 'selected' : '' ?>>Cash / Tunai</option>
                <option value="qris" <?= ($metode_filter === 'qris') ? 'selected' : '' ?>>QRIS Digital</option>
            </select>
        </div>

        <div style="width: 140px;">
            <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <option value="selesai" <?= ($status_filter === 'selesai') ? 'selected' : '' ?>>Selesai</option>
                <option value="diproses" <?= ($status_filter === 'diproses') ? 'selected' : '' ?>>Diproses</option>
                <option value="batal" <?= ($status_filter === 'batal') ? 'selected' : '' ?>>Batal</option>
            </select>
        </div>

        <button type="submit" class="btn btn-secondary">Filter</button>
        <?php if (!empty($search) || !empty($tgl_mulai) || !empty($tgl_selesai) || !empty($metode_filter) || !empty($status_filter)): ?>
            <a href="<?= BASE_URL ?>transaksi/index.php" class="btn btn-outline-danger">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nomor Pesanan</th>
                    <th>Tanggal & Waktu</th>
                    <th>Kasir</th>
                    <th>Metode</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th style="width: 100px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            Belum ada transaksi yang sesuai filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $idx => $item): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($item['nomor_pesanan']) ?></strong>
                                <?php if (!empty($item['catatan'])): ?>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        Note: <?= htmlspecialchars($item['catatan']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($item['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($item['nama_kasir']) ?></td>
                            <td>
                                <span class="badge <?= ($item['metode_pembayaran'] === 'cash') ? 'badge-success' : 'badge-info' ?>">
                                    <?= strtoupper($item['metode_pembayaran'] ?? 'CASH') ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--accent-orange); font-family: 'Outfit';">
                                    <?= format_rupiah($item['total']) ?>
                                </strong>
                            </td>
                            <td>
                                <?php if ($item['status'] === 'selesai'): ?>
                                    <span class="badge badge-success">Selesai</span>
                                <?php elseif ($item['status'] === 'diproses'): ?>
                                    <span class="badge badge-warning">Diproses</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Batal</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="<?= BASE_URL ?>transaksi/detail.php?id=<?= $item['id_pesanan'] ?>" 
                                   class="btn btn-secondary btn-sm" title="Lihat Detail Transaksi">
                                    📄 Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
