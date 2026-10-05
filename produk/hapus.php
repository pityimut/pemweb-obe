<?php
/**
 * Hapus / Toggle Status Produk - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? 'toggle';

if ($id <= 0) {
    set_flash('error', 'Produk tidak valid.');
    header('Location: ' . BASE_URL . 'produk/index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM produk WHERE id_produk = ?");
$stmt->execute([$id]);
$produk = $stmt->fetch();

if (!$produk) {
    set_flash('error', 'Produk tidak ditemukan.');
    header('Location: ' . BASE_URL . 'produk/index.php');
    exit;
}

// Cek apakah produk pernah dipesan dalam detail_pesanan
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM detail_pesanan WHERE id_produk = ?");
$stmtCount->execute([$id]);
$orderCount = $stmtCount->fetchColumn();

if ($action === 'delete') {
    if ($orderCount > 0) {
        set_flash('error', "Produk '{$produk['nama_produk']}' tidak dapat dihapus karena tercatat dalam {$orderCount} transaksi riwayat. Silakan nonaktifkan saja statusnya.");
    } else {
        $stmtDel = $pdo->prepare("DELETE FROM produk WHERE id_produk = ?");
        $stmtDel->execute([$id]);
        set_flash('success', "Produk '{$produk['nama_produk']}' berhasil dihapus secara permanen.");
    }
} elseif ($action === 'toggle') {
    $newStatus = ($produk['status'] === 'aktif') ? 'nonaktif' : 'aktif';
    $stmtUp = $pdo->prepare("UPDATE produk SET status = ? WHERE id_produk = ?");
    $stmtUp->execute([$newStatus, $id]);
    set_flash('success', "Status produk '{$produk['nama_produk']}' diubah menjadi " . ucfirst($newStatus) . ".");
}

header('Location: ' . BASE_URL . 'produk/index.php');
exit;
