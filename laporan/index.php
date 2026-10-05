<?php
/**
 * Laporan Penjualan & Analitik Keuangan - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Laporan & Analitik';
$page_subtitle = 'Rekapitulasi penjualan, keuntungan bersih, dan performa menu';
$current_page = 'laporan';

// Filter Periode
$filter = $_GET['filter'] ?? 'bulan_ini';
$tgl_mulai = $_GET['tgl_mulai'] ?? '';
$tgl_selesai = $_GET['tgl_selesai'] ?? '';

$whereDatePesanan = "1=1";
$whereDatePemasukan = "1=1";
$whereDatePengeluaran = "1=1";
$params = [];

if ($filter === 'hari_ini') {
    $whereDatePesanan = "DATE(p.tanggal) = CURDATE()";
    $whereDatePemasukan = "pem.tanggal = CURDATE()";
    $whereDatePengeluaran = "peng.tanggal = CURDATE()";
    $labelPeriode = "Hari Ini (" . date('d F Y') . ")";
} elseif ($filter === '7_hari') {
    $whereDatePesanan = "DATE(p.tanggal) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
    $whereDatePemasukan = "pem.tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
    $whereDatePengeluaran = "peng.tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)";
    $labelPeriode = "7 Hari Terakhir";
} elseif ($filter === 'bulan_ini') {
    $whereDatePesanan = "MONTH(p.tanggal) = MONTH(CURDATE()) AND YEAR(p.tanggal) = YEAR(CURDATE())";
    $whereDatePemasukan = "MONTH(pem.tanggal) = MONTH(CURDATE()) AND YEAR(pem.tanggal) = YEAR(CURDATE())";
    $whereDatePengeluaran = "MONTH(peng.tanggal) = MONTH(CURDATE()) AND YEAR(peng.tanggal) = YEAR(CURDATE())";
    $labelPeriode = "Bulan Ini (" . date('F Y') . ")";
} elseif ($filter === 'tahun_ini') {
    $whereDatePesanan = "YEAR(p.tanggal) = YEAR(CURDATE())";
    $whereDatePemasukan = "YEAR(pem.tanggal) = YEAR(CURDATE())";
    $whereDatePengeluaran = "YEAR(peng.tanggal) = YEAR(CURDATE())";
    $labelPeriode = "Tahun Ini (" . date('Y') . ")";
} elseif ($filter === 'custom' && !empty($tgl_mulai) && !empty($tgl_selesai)) {
    $whereDatePesanan = "DATE(p.tanggal) BETWEEN ? AND ?";
    $whereDatePemasukan = "pem.tanggal BETWEEN ? AND ?";
    $whereDatePengeluaran = "peng.tanggal BETWEEN ? AND ?";
    $labelPeriode = date('d/m/Y', strtotime($tgl_mulai)) . " s/d " . date('d/m/Y', strtotime($tgl_selesai));
} else {
    $labelPeriode = "Semua Waktu";
}

// 1. Total Transaksi
$sqlTrx = "SELECT COUNT(*) FROM pesanan p WHERE {$whereDatePesanan} AND p.status != 'batal'";
$stmtTrx = $pdo->prepare($sqlTrx);
if ($filter === 'custom' && !empty($tgl_mulai) && !empty($tgl_selesai)) {
    $stmtTrx->execute([$tgl_mulai, $tgl_selesai]);
} else {
    $stmtTrx->execute();
}
$totalTransaksi = (int)$stmtTrx->fetchColumn();

// 2. Total Pemasukan
$sqlPem = "SELECT COALESCE(SUM(jumlah), 0) FROM pemasukan pem WHERE {$whereDatePemasukan}";
$stmtPem = $pdo->prepare($sqlPem);
if ($filter === 'custom' && !empty($tgl_mulai) && !empty($tgl_selesai)) {
    $stmtPem->execute([$tgl_mulai, $tgl_selesai]);
} else {
    $stmtPem->execute();
}
$totalPemasukan = (float)$stmtPem->fetchColumn();

// 3. Total Pengeluaran
$sqlPeng = "SELECT COALESCE(SUM(jumlah), 0) FROM pengeluaran peng WHERE {$whereDatePengeluaran}";
$stmtPeng = $pdo->prepare($sqlPeng);
if ($filter === 'custom' && !empty($tgl_mulai) && !empty($tgl_selesai)) {
    $stmtPeng->execute([$tgl_mulai, $tgl_selesai]);
} else {
    $stmtPeng->execute();
}
$totalPengeluaran = (float)$stmtPeng->fetchColumn();

// 4. Pendapatan Bersih = Total Pemasukan - Total Pengeluaran
$pendapatanBersih = $totalPemasukan - $totalPengeluaran;

// 5. Produk Terlaris (Ranking)
$sqlTop = "
    SELECT pr.nama_produk, k.nama_kategori, pr.harga,
           SUM(dp.jumlah) AS total_qty,
           SUM(dp.subtotal) AS total_omzet
    FROM detail_pesanan dp
    JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
    JOIN produk pr ON dp.id_produk = pr.id_produk
    JOIN kategori k ON pr.id_kategori = k.id_kategori
    WHERE {$whereDatePesanan} AND p.status != 'batal'
    GROUP BY dp.id_produk
    ORDER BY total_qty DESC
    LIMIT 10
";
$stmtTop = $pdo->prepare($sqlTop);
if ($filter === 'custom' && !empty($tgl_mulai) && !empty($tgl_selesai)) {
    $stmtTop->execute([$tgl_mulai, $tgl_selesai]);
} else {
    $stmtTop->execute();
}
$topProducts = $stmtTop->fetchAll();

// Data untuk bar chart produk terlaris
$topLabels = [];
$topDataQty = [];
foreach ($topProducts as $tp) {
    $topLabels[] = $tp['nama_produk'];
    $topDataQty[] = (int)$tp['total_qty'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h3 style="margin: 0; font-size: 1.25rem;">Periode Laporan: <span style="color: var(--accent-orange);"><?= htmlspecialchars($labelPeriode) ?></span></h3>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
            🖨️ Cetak Laporan
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 1.5rem;">
    <form method="GET" action="" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end;">
        <div style="width: 170px;">
            <label class="form-label">Pilih Periode</label>
            <select name="filter" class="form-select" id="laporanFilterSelect" onchange="toggleCustomDate(this.value)">
                <option value="hari_ini" <?= ($filter === 'hari_ini') ? 'selected' : '' ?>>Hari Ini</option>
                <option value="7_hari" <?= ($filter === '7_hari') ? 'selected' : '' ?>>7 Hari Terakhir</option>
                <option value="bulan_ini" <?= ($filter === 'bulan_ini') ? 'selected' : '' ?>>Bulan Ini</option>
                <option value="tahun_ini" <?= ($filter === 'tahun_ini') ? 'selected' : '' ?>>Tahun Ini</option>
                <option value="semua" <?= ($filter === 'semua') ? 'selected' : '' ?>>Semua Waktu</option>
                <option value="custom" <?= ($filter === 'custom') ? 'selected' : '' ?>>Kustom Tanggal</option>
            </select>
        </div>

        <div id="laporanCustomDate" style="display: <?= ($filter === 'custom') ? 'flex' : 'none' ?>; gap: 0.5rem;">
            <div>
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tgl_mulai) ?>">
            </div>
            <div>
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="tgl_selesai" class="form-control" value="<?= htmlspecialchars($tgl_selesai) ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
        <a href="<?= BASE_URL ?>laporan/index.php" class="btn btn-secondary">Reset</a>
    </form>
</div>

<!-- Metric Cards Summary -->
<div class="metric-grid">
    <div class="metric-card">
        <div class="metric-icon-wrap metric-icon-transaksi">🧾</div>
        <div class="metric-body">
            <div class="metric-label">Total Transaksi</div>
            <div class="metric-value"><?= $totalTransaksi ?></div>
            <div class="metric-sub">Pesanan pelanggan</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon-wrap metric-icon-pemasukan">💵</div>
        <div class="metric-body">
            <div class="metric-label">Total Pemasukan</div>
            <div class="metric-value" style="color: var(--status-success);"><?= format_rupiah($totalPemasukan) ?></div>
            <div class="metric-sub">Omzet kotor penjualan</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon-wrap metric-icon-pengeluaran">💸</div>
        <div class="metric-body">
            <div class="metric-label">Total Pengeluaran</div>
            <div class="metric-value" style="color: var(--status-danger);"><?= format_rupiah($totalPengeluaran) ?></div>
            <div class="metric-sub">Biaya operasional warung</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon-wrap metric-icon-terlaris">💎</div>
        <div class="metric-body">
            <div class="metric-label">Pendapatan Bersih</div>
            <div class="metric-value" style="color: <?= ($pendapatanBersih >= 0) ? 'var(--status-success)' : 'var(--status-danger)' ?>;">
                <?= format_rupiah($pendapatanBersih) ?>
            </div>
            <div class="metric-sub">Pemasukan - Pengeluaran</div>
        </div>
    </div>
</div>

<!-- Grafik Produk Terlaris & Tabel Ranking -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;" class="laporan-grid-responsive">
    <!-- Chart Produk Terlaris -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title">📊 Grafik 10 Produk Terlaris (Porsi)</h3>
        </div>
        <div style="position: relative; height: 320px;">
            <?php if (empty($topLabels)): ?>
                <div style="text-align: center; color: var(--text-muted); padding: 5rem 1rem;">
                    Belum ada data penjualan pada periode ini.
                </div>
            <?php else: ?>
                <canvas id="topProductsChart"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabel Ranking Produk -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <h3 class="card-title">🏆 Ranking Menu Terlaris</h3>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Menu</th>
                        <th>Kategori</th>
                        <th style="text-align: center;">Terjual</th>
                        <th style="text-align: right;">Total Omzet</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topProducts)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Belum ada menu terjual.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($topProducts as $i => $item): ?>
                            <tr>
                                <td><strong><?= $i + 1 ?></strong></td>
                                <td><strong><?= htmlspecialchars($item['nama_produk']) ?></strong></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($item['nama_kategori']) ?></span></td>
                                <td style="text-align: center;">
                                    <span class="badge badge-success"><?= $item['total_qty'] ?> porsi</span>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: var(--accent-orange);">
                                    <?= format_rupiah($item['total_omzet']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleCustomDate(val) {
    document.getElementById('laporanCustomDate').style.display = (val === 'custom') ? 'flex' : 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('topProductsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($topLabels) ?>,
                datasets: [{
                    label: 'Jumlah Terjual (Porsi)',
                    data: <?= json_encode($topDataQty) ?>,
                    backgroundColor: 'rgba(230, 81, 0, 0.8)',
                    borderColor: '#E65100',
                    borderWidth: 1.5,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
});
</script>

<style>
@media (max-width: 900px) {
    .laporan-grid-responsive {
        grid-template-columns: 1fr !important;
    }
}
@media print {
    .app-sidebar, .app-header, .card form, .btn {
        display: none !important;
    }
    .app-main {
        margin-left: 0 !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
