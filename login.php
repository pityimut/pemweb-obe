<?php
/**
 * Login Page - Warung Makan Hanisa
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Jika sudah login, langsung ke dashboard
if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

$error = '';
$settings = get_app_settings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'aktif') {
                $error = 'Akun Anda dinonaktifkan. Silakan hubungi Administrator.';
            } else {
                // Regenerasi session ID untuk mencegah session fixation
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                set_flash('success', 'Selamat datang kembali, ' . htmlspecialchars($user['nama']) . '!');
                header('Location: ' . BASE_URL . 'dashboard.php');
                exit;
            }
        } else {
            $error = 'Username atau password salah.';
        }
    }
}

$flash_error = get_flash('error');
if ($flash_error && empty($error)) {
    $error = $flash_error;
}
$flash_success = get_flash('success');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($settings['nama_warung']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="icon" href="<?= BASE_URL ?>assets/uploads/produk/mie-ayam-biasa.jpg" type="image/jpeg">
</head>
<body class="login-page-body">

    <div class="login-card">
        <div class="login-header">
            <div class="login-logo-circle">
                🍜
            </div>
            <h2><?= htmlspecialchars($settings['nama_warung']) ?></h2>
            <p><?= htmlspecialchars($settings['slogan'] ?? 'Sistem Informasi Manajemen Penjualan & Kasir') ?></p>
        </div>

        <?php if (!empty($flash_success)): ?>
            <div class="alert alert-success">
                <span>✓</span>
                <div><?= htmlspecialchars($flash_success) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span>⚠️</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" 
                       placeholder="Masukkan username" required autofocus
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" 
                       placeholder="Masukkan password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 0.5rem;">
                Masuk ke Sistem Kasir
            </button>
        </form>

        <div class="login-credentials-helper">
            <div style="font-weight: 700; margin-bottom: 0.35rem; color: var(--primary-dark);">
                🔑 Akun Demo Pengujian:
            </div>
            <div>• <strong>Admin:</strong> admin / <code>admin123</code> (Semua Akses)</div>
            <div>• <strong>Kasir:</strong> kasir / <code>kasir123</code> (Kasir & Penjualan)</div>
        </div>
    </div>

</body>
</html>
