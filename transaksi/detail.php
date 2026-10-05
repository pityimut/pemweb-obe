<?php
/**
 * Detail Transaksi & Nota Struk - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    set_flash('error', 'Transaksi tidak valid.');
    header('Location: ' . BASE_URL . 'transaksi/index.php');
    exit;
}

// Ambil data pesanan utama
$stmt = $pdo->prepare("
    SELECT p.*, u.nama AS nama_kasir, u.username AS username_kasir,
           pm.metode_pembayaran, pm.nominal_diterima, pm.kembalian, pm.status_pembayaran, pm.tanggal_pembayaran
    FROM pesanan p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan
    WHERE p.id_pesanan = ?
");
$stmt->execute([$id]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    set_flash('error', 'Data transaksi tidak ditemukan.');
    header('Location: ' . BASE_URL . 'transaksi/index.php');
    exit;
}

// Ambil detail item pesanan
$stmtItems = $pdo->prepare("
    SELECT dp.*, pr.nama_produk, pr.gambar, k.nama_kategori
    FROM detail_pesanan dp
    JOIN produk pr ON dp.id_produk = pr.id_produk
    JOIN kategori k ON pr.id_kategori = k.id_kategori
    WHERE dp.id_pesanan = ?
    ORDER BY dp.id_detail ASC
");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

$settings = get_app_settings($pdo);

$page_title = 'Detail Transaksi ' . $pesanan['nomor_pesanan'];
$page_subtitle = 'Rincian menu pesanan dan status pembayaran';
$current_page = 'transaksi';

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
        <a href="<?= BASE_URL ?>transaksi/index.php" class="btn btn-secondary btn-sm">
            ← Kembali ke Riwayat
        </a>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
            🖨️ Cetak Bukti Nota
        </button>
    </div>

    <!-- NOTA RESMI KASIR -->
    <div class="card" id="receiptPrintArea" style="padding: 2.25rem; border-radius: var(--radius-lg);">
        <!-- Kop Nota -->
        <div style="text-align: center; border-bottom: 2px dashed var(--border-color); padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.5rem; margin-bottom: 0.25rem; font-family: 'Outfit';"><?= htmlspecialchars($settings['nama_warung']) ?></h2>
            <div style="font-size: 0.85rem; color: var(--text-muted);"><?= htmlspecialchars($settings['slogan'] ?? '') ?></div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.2rem;"><?= htmlspecialchars($settings['alamat'] ?? '') ?></div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">Telp: <?= htmlspecialchars($settings['telepon'] ?? '') ?></div>
        </div>

        <!-- Metadata Transaksi -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.88rem; margin-bottom: 1.5rem;">
            <div>
                <div style="color: var(--text-muted); margin-bottom: 0.2rem;">Nomor Transaksi:</div>
                <strong style="font-size: 1.05rem; color: var(--primary-dark); font-family: 'Outfit';"><?= htmlspecialchars($pesanan['nomor_pesanan']) ?></strong>
                <div style="margin-top: 0.5rem; color: var(--text-muted);">Waktu Transaksi:</div>
                <div><?= date('d F Y, H:i', strtotime($pesanan['tanggal'])) ?> WIB</div>
            </div>
            <div style="text-align: right;">
                <div style="color: var(--text-muted); margin-bottom: 0.2rem;">Kasir Bertugas:</div>
                <strong><?= htmlspecialchars($pesanan['nama_kasir']) ?></strong>
                <div style="margin-top: 0.5rem; color: var(--text-muted);">Status Pesanan:</div>
                <div>
                    <span class="badge badge-success"><?= strtoupper($pesanan['status']) ?></span>
                </div>
            </div>
        </div>

        <!-- Tabel Detail Produk -->
        <div class="table-responsive" style="margin-bottom: 1.5rem;">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Menu Pesanan</th>
                        <th>Suhu</th>
                        <th style="text-align: right;">Harga Satuan</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $idx => $it): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($it['nama_produk']) ?></strong>
                                <span style="font-size: 0.75rem; color: var(--text-muted);"> (<?= htmlspecialchars($it['nama_kategori']) ?>)</span>
                            </td>
                            <td>
                                <?php if ($it['suhu'] === 'panas'): ?>
                                    <span class="badge badge-warning">🔥 Panas</span>
                                <?php elseif ($it['suhu'] === 'dingin'): ?>
                                    <span class="badge badge-info">❄ Dingin</span>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;"><?= format_rupiah($it['harga']) ?></td>
                            <td style="text-align: center; font-weight: 700;"><?= $it['jumlah'] ?></td>
                            <td style="text-align: right; font-weight: 700; color: var(--accent-orange);">
                                <?= format_rupiah($it['subtotal']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Rangkuman Keuangan & Pembayaran -->
        <div style="display: flex; justify-content: flex-end;">
            <div style="width: 100%; max-width: 360px; font-size: 0.92rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; color: var(--text-muted);">
                    <span>Metode Pembayaran:</span>
                    <strong class="badge badge-info"><?= strtoupper($pesanan['metode_pembayaran'] ?? 'CASH') ?></strong>
                </div>

                <div style="display: flex; justify-content: space-between; padding: 0.6rem 0; border-top: 1px dashed var(--border-color); font-weight: 800; font-size: 1.25rem;">
                    <span>Total Tagihan:</span>
                    <span style="color: var(--accent-orange); font-family: 'Outfit';"><?= format_rupiah($pesanan['total']) ?></span>
                </div>

                <?php if (($pesanan['metode_pembayaran'] ?? 'cash') === 'cash'): ?>
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0; color: var(--text-muted);">
                        <span>Uang Diterima:</span>
                        <span><?= format_rupiah($pesanan['nominal_diterima']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0; font-weight: 700;">
                        <span>Kembalian:</span>
                        <span style="color: var(--status-success);"><?= format_rupiah($pesanan['kembalian']) ?></span>
                    </div>
                <?php else: ?>
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0; color: var(--text-muted);">
                        <span>Status Pembayaran:</span>
                        <span class="badge badge-success">Lunas (QRIS Terverifikasi)</span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pesanan['catatan'])): ?>
                    <div style="margin-top: 0.75rem; padding: 0.65rem; background: #FAF6F0; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); font-size: 0.8rem;">
                        <strong>Catatan:</strong> <?= htmlspecialchars($pesanan['catatan']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer Nota Struk -->
        <div style="text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 2px dashed var(--border-color); font-size: 0.82rem; color: var(--text-muted);">
            <div>Terima kasih atas kunjungan Anda di Warung Makan Hanisa!</div>
            <div>Semoga hidangan kami berkenan di hati Anda. Selamat menikmati.</div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #receiptPrintArea, #receiptPrintArea * {
        visibility: visible;
    }
    #receiptPrintArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
    }
    .app-sidebar, .app-header, .btn {
        display: none !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
