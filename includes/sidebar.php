<?php
/**
 * Sidebar Component with Role-based Menus
 * Warung Makan Hanisa
 */
$role = $_SESSION['role'] ?? 'kasir';
$nama_user = $_SESSION['nama'] ?? 'Pengguna';
$current = $current_page ?? 'dashboard';
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
        <div class="brand-icon">
            🍜
        </div>
        <div class="brand-text">
            <h2>Warung Hanisa</h2>
            <span>Sistem Kasir POS</span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <div class="menu-section-title">Menu Utama</div>
        
        <a href="<?= BASE_URL ?>dashboard.php" class="sidebar-link <?= ($current === 'dashboard') ? 'active' : '' ?>">
            <span class="link-icon">📊</span>
            <span>Dashboard</span>
        </a>

        <a href="<?= BASE_URL ?>kasir/index.php" class="sidebar-link <?= ($current === 'kasir') ? 'active' : '' ?>">
            <span class="link-icon">🛒</span>
            <span>Kasir POS</span>
        </a>

        <a href="<?= BASE_URL ?>transaksi/index.php" class="sidebar-link <?= ($current === 'transaksi') ? 'active' : '' ?>">
            <span class="link-icon">🧾</span>
            <span>Riwayat Transaksi</span>
        </a>

        <div class="menu-section-title">Katalog & Menu</div>

        <a href="<?= BASE_URL ?>produk/index.php" class="sidebar-link <?= ($current === 'produk') ? 'active' : '' ?>">
            <span class="link-icon">🍱</span>
            <span>Produk Menu</span>
        </a>

        <?php if ($role === 'admin'): ?>
            <a href="<?= BASE_URL ?>kategori/index.php" class="sidebar-link <?= ($current === 'kategori') ? 'active' : '' ?>">
                <span class="link-icon">🏷️</span>
                <span>Kategori Menu</span>
            </a>

            <div class="menu-section-title">Keuangan</div>

            <a href="<?= BASE_URL ?>pemasukan/index.php" class="sidebar-link <?= ($current === 'pemasukan') ? 'active' : '' ?>">
                <span class="link-icon">💰</span>
                <span>Pemasukan</span>
            </a>

            <a href="<?= BASE_URL ?>pengeluaran/index.php" class="sidebar-link <?= ($current === 'pengeluaran') ? 'active' : '' ?>">
                <span class="link-icon">💸</span>
                <span>Pengeluaran</span>
            </a>

            <a href="<?= BASE_URL ?>laporan/index.php" class="sidebar-link <?= ($current === 'laporan') ? 'active' : '' ?>">
                <span class="link-icon">📈</span>
                <span>Laporan & Analitik</span>
            </a>

            <div class="menu-section-title">Pengaturan Sistem</div>

            <a href="<?= BASE_URL ?>pengguna/index.php" class="sidebar-link <?= ($current === 'pengguna') ? 'active' : '' ?>">
                <span class="link-icon">👥</span>
                <span>Manajemen Pengguna</span>
            </a>

            <a href="<?= BASE_URL ?>pengaturan/index.php" class="sidebar-link <?= ($current === 'pengaturan') ? 'active' : '' ?>">
                <span class="link-icon">⚙️</span>
                <span>Pengaturan Toko</span>
            </a>
        <?php endif; ?>

        <div style="margin-top: auto; padding-top: 1rem;">
            <a href="<?= BASE_URL ?>logout.php" class="sidebar-link" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')">
                <span class="link-icon">🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="user-badge">
            <div class="user-avatar">
                <?= strtoupper(substr($nama_user, 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($nama_user) ?></div>
                <span class="user-role"><?= htmlspecialchars(ucfirst($role)) ?></span>
            </div>
        </div>
    </div>
</aside>
