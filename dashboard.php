<?php
/**
 * Dashboard Utama (Role-Based: Admin & Kasir)
 * Warung Makan Hanisa
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Pastikan user wajib login melalui session PHP
require_login();

$role = $_SESSION['role'] ?? 'kasir';
$userId = (int)$_SESSION['user_id'];
$userName = $_SESSION['nama'] ?? 'Pengguna';

$page_title = ($role === 'admin') ? 'Dashboard Administrator' : 'Dashboard Kasir';
$page_subtitle = ($role === 'admin') ? 'Ringkasan performa penjualan, keuangan, dan analitik toko' : 'Selamat bertugas! Kelola pesanan dan transaksi pelanggan';
$current_page = 'dashboard';

// ==========================================
// 1. DATA KHUSUS DASHBOARD ADMIN
// ==========================================
if ($role === 'admin') {
    // A. Pemasukan Hari Ini
    $stmtPem = $pdo->query("SELECT COALESCE(SUM(jumlah), 0) FROM pemasukan WHERE tanggal = CURDATE()");
    $pemasukanHariIni = (float)$stmtPem->fetchColumn();

    // B. Pengeluaran Hari Ini
    $stmtPeng = $pdo->query("SELECT COALESCE(SUM(jumlah), 0) FROM pengeluaran WHERE tanggal = CURDATE()");
    $pengeluaranHariIni = (float)$stmtPeng->fetchColumn();

    // C. Total Transaksi Hari Ini
    $stmtTrx = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE DATE(tanggal) = CURDATE() AND status != 'batal'");
    $transaksiHariIni = (int)$stmtTrx->fetchColumn();

    // D. Produk Terlaris
    $stmtTop = $pdo->query("
        SELECT p.nama_produk, COALESCE(SUM(dp.jumlah), 0) AS total_terjual
        FROM detail_pesanan dp
        JOIN pesanan pes ON dp.id_pesanan = pes.id_pesanan
        JOIN produk p ON dp.id_produk = p.id_produk
        WHERE pes.status != 'batal'
        GROUP BY dp.id_produk
        ORDER BY total_terjual DESC
        LIMIT 1
    ");
    $topProduct = $stmtTop->fetch();
    $produkTerlaris = $topProduct ? $topProduct['nama_produk'] . ' (' . $topProduct['total_terjual'] . ' porsi)' : 'Belum ada data';

    // E. Data Grafik 7 Hari Terakhir (Pemasukan vs Pengeluaran)
    $chartLabels = [];
    $chartPemasukan = [];
    $chartPengeluaran = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $dateLabel = date('d M', strtotime($date));
        $chartLabels[] = $dateLabel;

        // Pemasukan
        $stmtP = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM pemasukan WHERE tanggal = ?");
        $stmtP->execute([$date]);
        $chartPemasukan[] = (float)$stmtP->fetchColumn();

        // Pengeluaran
        $stmtQ = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM pengeluaran WHERE tanggal = ?");
        $stmtQ->execute([$date]);
        $chartPengeluaran[] = (float)$stmtQ->fetchColumn();
    }

    // F. 5 Transaksi Terkini
    $stmtRecent = $pdo->query("
        SELECT p.*, u.nama AS nama_kasir, pm.metode_pembayaran
        FROM pesanan p
        JOIN users u ON p.id_user = u.id_user
        LEFT JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan
        ORDER BY p.id_pesanan DESC
        LIMIT 5
    ");
    $recentOrders = $stmtRecent->fetchAll();

    // G. Total Menu & Stok Menipis
    $stmtTotalMenu = $pdo->query("SELECT COUNT(*) FROM produk WHERE status = 'aktif'");
    $totalMenuAktif = (int)$stmtTotalMenu->fetchColumn();

    $stmtLowStock = $pdo->query("SELECT COUNT(*) FROM produk WHERE status = 'aktif' AND stok <= 10");
    $totalStokMenipis = (int)$stmtLowStock->fetchColumn();
}

// ==========================================
// 2. DATA KHUSUS DASHBOARD KASIR
// ==========================================
if ($role === 'kasir') {
    // A. Transaksi yang diproses oleh kasir ini hari ini
    $stmtMyTrx = $pdo->prepare("
        SELECT COUNT(*) FROM pesanan 
        WHERE id_user = ? AND DATE(tanggal) = CURDATE() AND status != 'batal'
    ");
    $stmtMyTrx->execute([$userId]);
    $kasirTransaksiHariIni = (int)$stmtMyTrx->fetchColumn();

    // B. Total Omzet Penjualan oleh kasir ini hari ini
    $stmtMySales = $pdo->prepare("
        SELECT COALESCE(SUM(total), 0) FROM pesanan 
        WHERE id_user = ? AND DATE(tanggal) = CURDATE() AND status != 'batal'
    ");
    $stmtMySales->execute([$userId]);
    $kasirPenjualanHariIni = (float)$stmtMySales->fetchColumn();

    // C. Transaksi Terakhir oleh Kasir Ini
    $stmtMyRecent = $pdo->prepare("
        SELECT p.*, pm.metode_pembayaran
        FROM pesanan p
        LEFT JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan
        WHERE p.id_user = ?
        ORDER BY p.id_pesanan DESC
        LIMIT 5
    ");
    $stmtMyRecent->execute([$userId]);
    $kasirRecentOrders = $stmtMyRecent->fetchAll();

    // D. Menu Habis
    $stmtSoldOut = $pdo->query("SELECT nama_produk FROM produk WHERE status = 'aktif' AND stok <= 0");
    $soldOutItems = $stmtSoldOut->fetchAll(PDO::FETCH_COLUMN);
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- BANNER SAMBUTAN PENGGUNA -->
<div style="background: linear-gradient(135deg, var(--primary-dark), #5D4037); color: #fff; padding: 1.5rem 1.75rem; border-radius: var(--radius-lg); margin-bottom: 1.75rem; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="font-size: 0.85rem; color: var(--accent-light); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
            Selamat Datang di Sistem Kasir
        </div>
        <h2 style="font-size: 1.6rem; color: #fff; margin: 0.2rem 0 0.35rem; font-family: 'Outfit';">
            <?= htmlspecialchars($userName) ?> 
            <span class="badge <?= ($role === 'admin') ? 'badge-warning' : 'badge-info' ?>" style="font-size: 0.75rem; vertical-align: middle;">
                <?= ($role === 'admin') ? '🛡️ Administrator' : '💼 Kasir Penjualan' ?>
            </span>
        </h2>
        <p style="color: #D7CCC8; font-size: 0.88rem; margin: 0;">
            <?= ($role === 'admin') 
                ? 'Semua modul manajemen toko, laporan analitik, dan pengaturan operasional aktif.' 
                : 'Siap melayani pesanan pelanggan dengan cepat, akurat, dan ramah.' ?>
        </p>
    </div>

    <div>
        <a href="<?= BASE_URL ?>kasir/index.php" class="btn btn-primary btn-lg" style="box-shadow: 0 6px 16px rgba(230, 81, 0, 0.4);">
            <span>🛒 Buka Kasir POS</span>
        </a>
    </div>
</div>

<?php if ($role === 'admin'): ?>
    <!-- ==========================================================
         VIEW KHUSUS ADMINISTRATOR
         ========================================================== -->
    <div class="metric-grid">
        <!-- Pemasukan Hari Ini -->
        <div class="metric-card">
            <div class="metric-icon-wrap metric-icon-pemasukan">
                💵
            </div>
            <div class="metric-body">
                <div class="metric-label">Pemasukan Hari Ini</div>
                <div class="metric-value"><?= format_rupiah($pemasukanHariIni) ?></div>
                <div class="metric-sub">Pencatatan real-time sistem</div>
            </div>
        </div>

        <!-- Pengeluaran Hari Ini -->
        <div class="metric-card">
            <div class="metric-icon-wrap metric-icon-pengeluaran">
                💸
            </div>
            <div class="metric-body">
                <div class="metric-label">Pengeluaran Hari Ini</div>
                <div class="metric-value"><?= format_rupiah($pengeluaranHariIni) ?></div>
                <div class="metric-sub">Biaya operasional warung</div>
            </div>
        </div>

        <!-- Total Transaksi Hari Ini -->
        <div class="metric-card">
            <div class="metric-icon-wrap metric-icon-transaksi">
                🧾
            </div>
            <div class="metric-body">
                <div class="metric-label">Total Transaksi</div>
                <div class="metric-value"><?= $transaksiHariIni ?></div>
                <div class="metric-sub">Pesanan selesai hari ini</div>
            </div>
        </div>

        <!-- Produk Terlaris -->
        <div class="metric-card">
            <div class="metric-icon-wrap metric-icon-terlaris">
                🔥
            </div>
            <div class="metric-body">
                <div class="metric-label">Produk Terlaris</div>
                <div class="metric-value" style="font-size: 1.15rem;"><?= htmlspecialchars($produkTerlaris) ?></div>
                <div class="metric-sub">Paling banyak dipesan</div>
            </div>
        </div>
    </div>

    <!-- GRAFIK 7 HARI TERAKHIR (CHART.JS) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📈 Grafik Keuangan 7 Hari Terakhir</h3>
            <span style="font-size: 0.8rem; color: var(--text-muted);">Pemasukan vs Pengeluaran</span>
        </div>
        <div style="position: relative; height: 320px; width: 100%;">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- TABEL 5 TRANSAKSI TERAKHIR -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🧾 Transaksi Terbaru Warung</h3>
            <a href="<?= BASE_URL ?>transaksi/index.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nomor Pesanan</th>
                        <th>Waktu</th>
                        <th>Kasir</th>
                        <th>Metode</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                Belum ada transaksi penjualan yang tercatat.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ro['nomor_pesanan']) ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($ro['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($ro['nama_kasir']) ?></td>
                                <td>
                                    <span class="badge <?= ($ro['metode_pembayaran'] === 'cash') ? 'badge-success' : 'badge-info' ?>">
                                        <?= strtoupper($ro['metode_pembayaran'] ?? 'CASH') ?>
                                    </span>
                                </td>
                                <td><strong style="color: var(--accent-orange);"><?= format_rupiah($ro['total']) ?></strong></td>
                                <td><span class="badge badge-success"><?= ucfirst($ro['status']) ?></span></td>
                                <td style="text-align: center;">
                                    <a href="<?= BASE_URL ?>transaksi/detail.php?id=<?= $ro['id_pesanan'] ?>" class="btn btn-secondary btn-sm">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SCRIPT CHART.JS HANYA UNTUK ADMIN -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('salesChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chartLabels) ?>,
                    datasets: [
                        {
                            label: 'Pemasukan (Rp)',
                            data: <?= json_encode($chartPemasukan) ?>,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#10B981',
                            pointRadius: 4
                        },
                        {
                            label: 'Pengeluaran (Rp)',
                            data: <?= json_encode($chartPengeluaran) ?>,
                            borderColor: '#EF4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.08)',
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.35,
                            pointBackgroundColor: '#EF4444',
                            pointRadius: 4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: { family: 'Plus Jakarta Sans', size: 12 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + formatRupiah(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp' + (value / 1000) + 'k';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
    </script>

<?php else: ?>
    <!-- ==========================================================
         VIEW KHUSUS KASIR
         ========================================================== -->
    <div class="metric-grid">
        <!-- Transaksi Saya Hari Ini -->
        <div class="metric-card">
            <div class="metric-icon-wrap metric-icon-transaksi">
                🧾
            </div>
            <div class="metric-body">
                <div class="metric-label">Transaksi Saya Hari Ini</div>
                <div class="metric-value"><?= $kasirTransaksiHariIni ?></div>
                <div class="metric-sub">Pesanan dilayani oleh Anda</div>
            </div>
        </div>

        <!-- Omzet Penjualan Saya Hari Ini -->
        <div class="metric-card">
            <div class="metric-icon-wrap metric-icon-pemasukan">
                💰
            </div>
            <div class="metric-body">
                <div class="metric-label">Penjualan Saya Hari Ini</div>
                <div class="metric-value"><?= format_rupiah($kasirPenjualanHariIni) ?></div>
                <div class="metric-sub">Total penerimaan kasir Anda</div>
            </div>
        </div>

        <!-- Menu Habis / Peringatan Stok -->
        <div class="metric-card">
            <div class="metric-icon-wrap <?= empty($soldOutItems) ? 'metric-icon-terlaris' : 'metric-icon-pengeluaran' ?>">
                ⚠️
            </div>
            <div class="metric-body">
                <div class="metric-label">Menu Habis</div>
                <div class="metric-value"><?= count($soldOutItems) ?> Menu</div>
                <div class="metric-sub"><?= empty($soldOutItems) ? 'Semua menu tersedia' : implode(', ', array_slice($soldOutItems, 0, 2)) ?></div>
            </div>
        </div>
    </div>

    <!-- PANDUAN CEPAT KASIR & AKSES CEPAT -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <h3 class="card-title">💡 Panduan Singkat Kasir</h3>
            </div>
            <div style="font-size: 0.88rem; color: var(--text-dark); line-height: 1.6;">
                <p style="margin-bottom: 0.5rem;"><strong>1. Tambah Menu:</strong> Klik tombol <strong>+</strong> pada menu. Untuk minuman, tentukan pilihan <strong>🔥 Panas</strong> atau <strong>❄ Dingin</strong>.</p>
                <p style="margin-bottom: 0.5rem;"><strong>2. Pembayaran Tunai (Cash):</strong> Masukkan nominal uang yang diterima pelanggan. Sistem akan otomatis menghitung kembalian.</p>
                <p style="margin-bottom: 0.5rem;"><strong>3. Pembayaran QRIS:</strong> Tunjukkan kode QRIS kepada pelanggan, lalu klik tombol <em>Saya Sudah Membayar</em> setelah dana masuk.</p>
                <div style="margin-top: 1rem;">
                    <a href="<?= BASE_URL ?>kasir/index.php" class="btn btn-primary" style="width: 100%;">
                        🛒 Mulai Transaksi Sekarang
                    </a>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 0;">
            <div class="card-header">
                <h3 class="card-title">📋 5 Transaksi Terakhir Anda</h3>
                <a href="<?= BASE_URL ?>transaksi/index.php" class="btn btn-secondary btn-sm">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nomor</th>
                            <th>Total</th>
                            <th>Metode</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kasirRecentOrders)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                    Belum ada transaksi yang diproses hari ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($kasirRecentOrders as $ko): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ko['nomor_pesanan']) ?></strong></td>
                                    <td><strong style="color: var(--accent-orange);"><?= format_rupiah($ko['total']) ?></strong></td>
                                    <td>
                                        <span class="badge <?= ($ko['metode_pembayaran'] === 'cash') ? 'badge-success' : 'badge-info' ?>">
                                            <?= strtoupper($ko['metode_pembayaran'] ?? 'CASH') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>transaksi/detail.php?id=<?= $ko['id_pesanan'] ?>" class="btn btn-secondary btn-sm">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
