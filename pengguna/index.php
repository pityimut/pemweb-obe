<?php
/**
 * Manajemen Pengguna (Admin & Kasir) - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$page_title = 'Manajemen Pengguna';
$page_subtitle = 'Kelola akun kasir dan administrator sistem';
$current_page = 'pengguna';

$error = '';

// Proses Tambah Pengguna Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['admin', 'kasir']) ? $_POST['role'] : 'kasir';
    $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif']) ? $_POST['status'] : 'aktif';

    if (empty($nama) || empty($username) || empty($password)) {
        $error = 'Nama, username, dan password wajib diisi.';
    } elseif (strlen($password) < 5) {
        $error = 'Password minimal 5 karakter.';
    } else {
        // Cek duplikasi username
        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmtC->execute([$username]);
        if ($stmtC->fetchColumn() > 0) {
            $error = 'Username tersebut sudah digunakan oleh akun lain.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nama, username, password, role, status) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nama, $username, $hashedPassword, $role, $status])) {
                set_flash('success', "Pengguna baru '{$nama}' ({$role}) berhasil didaftarkan.");
                header('Location: ' . BASE_URL . 'pengguna/index.php');
                exit;
            } else {
                $error = 'Gagal menyimpan data pengguna ke database.';
            }
        }
    }
}

// Proses Reset Password / Edit Cepat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $editId = (int)$_POST['id_user'];
    $nama = trim($_POST['nama'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['admin', 'kasir']) ? $_POST['role'] : 'kasir';
    $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif']) ? $_POST['status'] : 'aktif';
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($nama)) {
        $error = 'Nama lengkap wajib diisi.';
    } else {
        if (!empty($newPassword)) {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmtU = $pdo->prepare("UPDATE users SET nama = ?, role = ?, status = ?, password = ? WHERE id_user = ?");
            $stmtU->execute([$nama, $role, $status, $hash, $editId]);
        } else {
            $stmtU = $pdo->prepare("UPDATE users SET nama = ?, role = ?, status = ? WHERE id_user = ?");
            $stmtU->execute([$nama, $role, $status, $editId]);
        }
        set_flash('success', "Data pengguna '{$nama}' berhasil diperbarui.");
        header('Location: ' . BASE_URL . 'pengguna/index.php');
        exit;
    }
}

// Toggle Status Aktif / Nonaktif
if (isset($_GET['toggle'])) {
    $toggleId = (int)$_GET['toggle'];
    // Jangan izinkan admin menonaktifkan dirinya sendiri
    if ($toggleId === (int)$_SESSION['user_id']) {
        set_flash('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif digunakan.');
    } else {
        $stmtT = $pdo->prepare("SELECT status, nama FROM users WHERE id_user = ?");
        $stmtT->execute([$toggleId]);
        $u = $stmtT->fetch();
        if ($u) {
            $newStat = ($u['status'] === 'aktif') ? 'nonaktif' : 'aktif';
            $stmtUp = $pdo->prepare("UPDATE users SET status = ? WHERE id_user = ?");
            $stmtUp->execute([$newStat, $toggleId]);
            set_flash('success', "Status akun '{$u['nama']}' berhasil diubah menjadi " . ucfirst($newStat) . ".");
        }
    }
    header('Location: ' . BASE_URL . 'pengguna/index.php');
    exit;
}

// Ambil semua pengguna
$users = $pdo->query("SELECT * FROM users ORDER BY role ASC, id_user ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 360px; gap: 1.5rem; align-items: start;">
    <!-- Tabel Pengguna -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Pengguna Sistem (<?= count($users) ?> Akun)</h3>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Terdaftar</th>
                        <th style="width: 140px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($u['nama']) ?></strong>
                                <?php if ($u['id_user'] == $_SESSION['user_id']): ?>
                                    <span style="font-size: 0.72rem; color: var(--accent-orange); font-weight: 700;">(Anda)</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                            <td>
                                <span class="badge <?= ($u['role'] === 'admin') ? 'badge-warning' : 'badge-info' ?>">
                                    <?= ($u['role'] === 'admin') ? '🛡️ Admin' : '💼 Kasir' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['status'] === 'aktif'): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td><span style="font-size: 0.8rem; color: var(--text-muted);"><?= date('d/m/Y', strtotime($u['created_at'])) ?></span></td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <button type="button" class="btn btn-secondary btn-sm" 
                                            onclick='openEditUserModal(<?= json_encode($u) ?>)'>
                                        ✏️ Edit
                                    </button>

                                    <?php if ($u['id_user'] != $_SESSION['user_id']): ?>
                                        <a href="<?= BASE_URL ?>pengguna/index.php?toggle=<?= $u['id_user'] ?>" 
                                           class="btn btn-sm <?= ($u['status'] === 'aktif') ? 'btn-danger' : 'btn-success' ?>"
                                           title="<?= ($u['status'] === 'aktif') ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                           onclick="return confirm('Ubah status aktifasi akun ini?')">
                                            <?= ($u['status'] === 'aktif') ? 'Off' : 'On' ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form Tambah Pengguna Baru -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">➕ Tambah Pengguna</h3>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span>⚠️</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="tambah">
            
            <div class="form-group">
                <label class="form-label" for="nama">Nama Lengkap <span style="color:var(--status-danger)">*</span></label>
                <input type="text" id="nama" name="nama" class="form-control" placeholder="Misal: Budi Santoso" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Username <span style="color:var(--status-danger)">*</span></label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Misal: budi_kasir" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password <span style="color:var(--status-danger)">*</span></label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 5 karakter" minlength="5" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="role">Hak Akses (Role) <span style="color:var(--status-danger)">*</span></label>
                <select id="role" name="role" class="form-select" required>
                    <option value="kasir">Kasir (Transaksi & Menu)</option>
                    <option value="admin">Administrator (Semua Akses Toko)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Status Akun</label>
                <select id="status" name="status" class="form-select">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                Simpan Pengguna
            </button>
        </form>
    </div>
</div>

<!-- Modal Edit Pengguna -->
<div class="modal-overlay" id="modalEditUser">
    <div class="modal-box" style="max-width: 450px;">
        <div class="modal-header">
            <h3>Edit Akun Pengguna</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalEditUser')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id_user" id="editUserId">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Username (Tetap)</label>
                    <input type="text" id="editUsername" class="form-control" disabled style="background:#f5f5f5;">
                </div>

                <div class="form-group">
                    <label class="form-label" for="editNama">Nama Lengkap <span style="color:var(--status-danger)">*</span></label>
                    <input type="text" id="editNama" name="nama" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editRole">Hak Akses (Role)</label>
                    <select id="editRole" name="role" class="form-select" required>
                        <option value="kasir">Kasir</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editStatus">Status Akun</label>
                    <select id="editStatus" name="status" class="form-select">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="editNewPassword">Ganti Password (Opsional)</label>
                    <input type="password" id="editNewPassword" name="new_password" class="form-control" placeholder="Biarkan kosong jika tidak diganti">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditUser')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditUserModal(user) {
    document.getElementById('editUserId').value = user.id_user;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editNama').value = user.nama;
    document.getElementById('editRole').value = user.role;
    document.getElementById('editStatus').value = user.status;
    document.getElementById('editNewPassword').value = '';
    openModal('modalEditUser');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
