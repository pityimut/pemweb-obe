<?php
/**
 * Data Pemasukan Keuangan - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Pemasukan Kasir';
$page_subtitle = 'Pencatatan pemasukan otomatis dari pesanan yang telah dibayar';
$current_page = 'pemasukan';

// Filter periode
$periode = $_GET['periode'] ?? 'hari_ini';
$tgl_mulai = $_GET['tgl_mulai'] ?? '';
$tgl_selesai = $_GET['tgl_selesai'] ?? '';

$sql = "
    SELECT pem.*, pes.nomor_pesanan, pm.metode_pembayaran, u.nama AS nama_kasir
    FROM pemasukan pem
    JOIN pesanan pes ON pem.id_pesanan = pes.id_pesanan
    LEFT JOIN pembayaran pm ON pes.id_pesanan = pm.id_pesanan
    LEFT JOIN users u ON pes.id_user = u.id_user
    WHERE 1=1
";
$params = [];

if ($periode === 'hari_ini') {
    $sql .= " AND pem.tanggal = CURDATE()";
} elseif ($periode === 'minggu_ini') {
    $sql .= " AND YEARWEEK(pem.tanggal, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($periode === 'bulan_ini') {
    $sql .= " AND MONTH(pem.tanggal) = MONTH(CURDATE()) AND YEAR(pem.tanggal) = YEAR(CURDATE())";
} elseif ($periode === 'custom' && !empty($tgl_mulai) && !empty($tgl_selesai)) {
    $sql .= " AND pem.tanggal BETWEEN ? AND ?";
    $params[] = $tgl_mulai;
    $params[] = $tgl_selesai;
}

$sql .= " ORDER BY pem.id_pemasukan DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$incomes = $stmt->fetchAll();

// Hitung total pemasukan sesuai filter
$totalPemasukan = 0;
foreach ($incomes as $inc) {
    $totalPemasukan += (float)$inc['jumlah'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="metric-card" style="margin-bottom: 1.5rem;">
    <div class="metric-icon-wrap metric-icon-pemasukan">
        💰
    </div>
    <div class="metric-body">
        <div class="metric-label">Total Pemasukan (Sesuai Filter)</div>
        <div class="metric-value"><?= format_rupiah($totalPemasukan) ?></div>
        <div class="metric-sub"><?= count($incomes) ?> transaksi tercatat</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Pemasukan Toko</h3>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="" style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.25rem; align-items: flex-end;">
        <div style="width: 180px;">
            <label class="form-label" style="margin-bottom: 0.25rem;">Periode Waktu</label>
            <select name="periode" class="form-select" id="periodeSelect" onchange="toggleCustomDate(this.value)">
                <option value="hari_ini" <?= ($periode === 'hari_ini') ? 'selected' : '' ?>>Hari Ini</option>
                <option value="minggu_ini" <?= ($periode === 'minggu_ini') ? 'selected' : '' ?>>Minggu Ini</option>
                <option value="bulan_ini" <?= ($periode === 'bulan_ini') ? 'selected' : '' ?>>Bulan Ini</option>
                <option value="semua" <?= ($periode === 'semua') ? 'selected' : '' ?>>Semua Data</option>
                <option value="custom" <?= ($periode === 'custom') ? 'selected' : '' ?>>Kustom Tanggal</option>
            </select>
        </div>

        <div id="customDateWrap" style="display: <?= ($periode === 'custom') ? 'flex' : 'none' ?>; gap: 0.5rem;">
            <div>
                <label class="form-label" style="margin-bottom: 0.25rem;">Dari Tanggal</label>
                <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tgl_mulai) ?>">
            </div>
            <div>
                <label class="form-label" style="margin-bottom: 0.25rem;">Sampai Tanggal</label>
                <input type="date" name="tgl_selesai" class="form-control" value="<?= htmlspecialchars($tgl_selesai) ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-secondary">Terapkan Filter</button>
        <a href="<?= BASE_URL ?>pemasukan/index.php" class="btn btn-outline-danger">Reset</a>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Tanggal</th>
                    <th>Nomor Transaksi</th>
                    <th>Metode</th>
                    <th>Kasir</th>
                    <th>Keterangan</th>
                    <th style="text-align: right;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($incomes)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            Tidak ada data pemasukan pada periode yang dipilih.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($incomes as $idx => $inc): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><?= date('d/m/Y', strtotime($inc['tanggal'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>transaksi/detail.php?id=<?= $inc['id_pesanan'] ?>" style="color: var(--primary-dark); font-weight: 700; text-decoration: underline;">
                                    <?= htmlspecialchars($inc['nomor_pesanan']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge <?= ($inc['metode_pembayaran'] === 'cash') ? 'badge-success' : 'badge-info' ?>">
                                    <?= strtoupper($inc['metode_pembayaran'] ?? 'CASH') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($inc['nama_kasir'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($inc['keterangan'] ?? '-') ?></td>
                            <td style="text-align: right;">
                                <strong style="color: var(--status-success); font-family: 'Outfit';">
                                    +<?= format_rupiah($inc['jumlah']) ?>
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleCustomDate(val) {
    document.getElementById('customDateWrap').style.display = (val === 'custom') ? 'flex' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
