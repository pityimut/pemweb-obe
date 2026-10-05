<?php
/**
 * Hapus / Toggle Status Kategori - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? 'toggle';

if ($id <= 0) {
    set_flash('error', 'Kategori tidak valid.');
    header('Location: ' . BASE_URL . 'kategori/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM kategori WHERE id_kategori = ?");
$stmt->execute([$id]);
$kategori = $stmt->fetch();

if (!$kategori) {
    set_flash('error', 'Kategori tidak ditemukan.');
    header('Location: ' . BASE_URL . 'kategori/index.php');
    exit;
}

// Cek apakah ada produk yang terikat ke kategori ini
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM produk WHERE id_kategori = ?");
$stmtCount->execute([$id]);
$hasProducts = ($stmtCount->fetchColumn() > 0);

if ($action === 'delete') {
    if ($hasProducts) {
        set_flash('error', "Kategori '{$kategori['nama_kategori']}' tidak dapat dihapus karena masih memiliki produk terkait. Silakan nonaktifkan saja statusnya.");
    } else {
        $stmtDel = $pdo->prepare("DELETE FROM kategori WHERE id_kategori = ?");
        $stmtDel->execute([$id]);
        set_flash('success', "Kategori '{$kategori['nama_kategori']}' berhasil dihapus secara permanen.");
    }
} elseif ($action === 'toggle') {
    $newStatus = ($kategori['status'] === 'aktif') ? 'nonaktif' : 'aktif';
    $stmtUp = $pdo->prepare("UPDATE kategori SET status = ? WHERE id_kategori = ?");
    $stmtUp->execute([$newStatus, $id]);
    set_flash('success', "Status kategori '{$kategori['nama_kategori']}' diubah menjadi " . ucfirst($newStatus) . ".");
}

header('Location: ' . BASE_URL . 'kategori/index.php');
exit;
