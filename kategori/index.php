<?php
/**
 * Manajemen Kategori Menu - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Kategori Menu';
$page_subtitle = 'Kelola kategori makanan dan minuman warung';
$current_page = 'kategori';

// Ambil daftar kategori beserta jumlah produknya
$query = "
    SELECT k.*, COUNT(p.id_produk) AS total_produk 
    FROM kategori k
    LEFT JOIN produk p ON k.id_kategori = p.id_kategori
    GROUP BY k.id_kategori
    ORDER BY k.id_kategori ASC
";
$categories = $pdo->query($query)->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Kategori Menu</h3>
        <a href="<?= BASE_URL ?>kategori/tambah.php" class="btn btn-primary btn-sm">
            <span>➕ Tambah Kategori</span>
        </a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Produk</th>
                    <th>Status</th>
                    <th style="width: 180px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            Belum ada kategori yang terdaftar.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $index => $cat): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($cat['nama_kategori']) ?></strong>
                            </td>
                            <td>
                                <?= !empty($cat['deskripsi']) ? htmlspecialchars($cat['deskripsi']) : '<span style="color:var(--text-light);">-</span>' ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= $cat['total_produk'] ?> Produk</span>
                            </td>
                            <td>
                                <?php if ($cat['status'] === 'aktif'): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <a href="<?= BASE_URL ?>kategori/edit.php?id=<?= $cat['id_kategori'] ?>" class="btn btn-secondary btn-sm" title="Edit Kategori">
                                        ✏️ Edit
                                    </a>
                                    
                                    <a href="<?= BASE_URL ?>kategori/hapus.php?action=toggle&id=<?= $cat['id_kategori'] ?>" 
                                       class="btn btn-sm <?= ($cat['status'] === 'aktif') ? 'btn-danger' : 'btn-success' ?>" 
                                       title="<?= ($cat['status'] === 'aktif') ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                       onclick="return confirm('Apakah Anda yakin ingin mengubah status kategori ini?')">
                                        <?= ($cat['status'] === 'aktif') ? 'Off' : 'On' ?>
                                    </a>

                                    <?php if ($cat['total_produk'] == 0): ?>
                                        <a href="<?= BASE_URL ?>kategori/hapus.php?action=delete&id=<?= $cat['id_kategori'] ?>" 
                                           class="btn btn-outline-danger btn-sm" title="Hapus Permanen"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini secara permanen?')">
                                            🗑️
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
