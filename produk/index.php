<?php
/**
 * Katalog & Manajemen Produk - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$page_title = 'Produk Menu';
$page_subtitle = 'Kelola daftar menu makanan dan minuman Warung Hanisa';
$current_page = 'produk';

$isAdmin = is_admin();

// Filter kategori & search jika ada
$search = trim($_GET['search'] ?? '');
$kategori_filter = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;

$sql = "
    SELECT p.*, k.nama_kategori,
           (SELECT COUNT(*) FROM detail_pesanan dp WHERE dp.id_produk = p.id_produk) AS total_terjual
    FROM produk p
    JOIN kategori k ON p.id_kategori = k.id_kategori
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($kategori_filter > 0) {
    $sql .= " AND p.id_kategori = ?";
    $params[] = $kategori_filter;
}

$sql .= " ORDER BY p.id_kategori ASC, p.nama_produk ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Ambil semua kategori untuk filter dropdown
$categories = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Menu Produk (<?= count($products) ?> Menu)</h3>
        <?php if ($isAdmin): ?>
            <a href="<?= BASE_URL ?>produk/tambah.php" class="btn btn-primary btn-sm">
                <span>➕ Tambah Produk</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Filter & Pencarian Bar -->
    <form method="GET" action="" style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <div style="flex: 1; min-width: 200px;">
            <input type="text" name="search" class="form-control" 
                   placeholder="Cari nama menu..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div style="width: 200px;">
            <select name="kategori" class="form-select" onchange="this.form.submit()">
                <option value="0">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id_kategori'] ?>" <?= ($kategori_filter == $cat['id_kategori']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Cari</button>
        <?php if (!empty($search) || $kategori_filter > 0): ?>
            <a href="<?= BASE_URL ?>produk/index.php" class="btn btn-outline-danger">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th style="width: 80px;">Gambar</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Pilihan Suhu</th>
                    <th>Status</th>
                    <?php if ($isAdmin): ?>
                        <th style="width: 170px; text-align: center;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="<?= $isAdmin ? 9 : 8 ?>" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            Tidak ada produk yang sesuai dengan kriteria pencarian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $idx => $p): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <?php
                                $imgSrc = !empty($p['gambar']) ? BASE_URL . 'assets/uploads/produk/' . htmlspecialchars($p['gambar']) : BASE_URL . 'assets/images/logo.png';
                                ?>
                                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>" 
                                     style="width: 55px; height: 55px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);"
                                     onerror="this.src='<?= BASE_URL ?>assets/uploads/produk/mie-ayam-biasa.jpg'">
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($p['nama_produk']) ?></strong>
                                <?php if (!empty($p['deskripsi'])): ?>
                                    <div style="font-size: 0.78rem; color: var(--text-muted); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($p['deskripsi']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?= htmlspecialchars($p['nama_kategori']) ?></span>
                            </td>
                            <td>
                                <strong style="color: var(--accent-orange); font-family: 'Outfit', sans-serif;">
                                    <?= format_rupiah($p['harga']) ?>
                                </strong>
                            </td>
                            <td>
                                <?php if ($p['stok'] > 10): ?>
                                    <span class="badge badge-success"><?= $p['stok'] ?> porsi</span>
                                <?php elseif ($p['stok'] > 0): ?>
                                    <span class="badge badge-warning">Sisa <?= $p['stok'] ?></span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Habis (0)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['opsi_suhu'] === 'panas_dingin'): ?>
                                    <span class="badge badge-warning">🔥 Panas / ❄ Dingin</span>
                                <?php elseif ($p['opsi_suhu'] === 'dingin'): ?>
                                    <span class="badge badge-info">❄ Khusus Dingin</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Tidak Berlaku</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['status'] === 'aktif'): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($isAdmin): ?>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 0.35rem;">
                                        <a href="<?= BASE_URL ?>produk/edit.php?id=<?= $p['id_produk'] ?>" 
                                           class="btn btn-secondary btn-sm" title="Edit Produk">
                                            ✏️ Edit
                                        </a>

                                        <a href="<?= BASE_URL ?>produk/hapus.php?action=toggle&id=<?= $p['id_produk'] ?>" 
                                           class="btn btn-sm <?= ($p['status'] === 'aktif') ? 'btn-danger' : 'btn-success' ?>" 
                                           title="<?= ($p['status'] === 'aktif') ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                           onclick="return confirm('Ubah status aktifasi produk ini?')">
                                            <?= ($p['status'] === 'aktif') ? 'Off' : 'On' ?>
                                        </a>

                                        <?php if ($p['total_terjual'] == 0): ?>
                                            <a href="<?= BASE_URL ?>produk/hapus.php?action=delete&id=<?= $p['id_produk'] ?>" 
                                               class="btn btn-outline-danger btn-sm" title="Hapus Produk"
                                               onclick="return confirm('Hapus permanen produk ini? Pastikan data sudah benar.')">
                                                🗑️
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
