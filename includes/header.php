<?php
/**
 * Master Header Layout
 * Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

// Wajib login untuk semua halaman yang memanggil master header
require_login();

$user = current_user();
$settings = get_app_settings($pdo);

if (!isset($page_title)) {
    $page_title = 'Kasir & Manajemen';
}
if (!isset($current_page)) {
    $current_page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - <?= htmlspecialchars($settings['nama_warung']) ?></title>
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    
    <!-- Chart.js CDN for Analytics Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="app-container">
    <!-- Sidebar Navigation -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Shell -->
    <div class="app-main">
        <!-- Top Navbar -->
        <?php require_once __DIR__ . '/navbar.php'; ?>

        <!-- Main Body Content Area -->
        <main class="app-content">
            <?php 
            $f_success = get_flash('success');
            $f_error = get_flash('error');
            $f_warning = get_flash('warning');
            ?>
            <?php if ($f_success): ?>
                <div class="alert alert-success">
                    <span style="font-size: 1.2rem;">✓</span>
                    <div><?= htmlspecialchars($f_success) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($f_error): ?>
                <div class="alert alert-danger">
                    <span style="font-size: 1.2rem;">⚠️</span>
                    <div><?= htmlspecialchars($f_error) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($f_warning): ?>
                <div class="alert alert-warning">
                    <span style="font-size: 1.2rem;">ℹ️</span>
                    <div><?= htmlspecialchars($f_warning) ?></div>
                </div>
            <?php endif; ?>
