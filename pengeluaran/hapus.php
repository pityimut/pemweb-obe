<?php
/**
 * Hapus Pengeluaran - Warung Makan Hanisa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    set_flash('error', 'Catatan pengeluaran tidak valid.');
    header('Location: ' . BASE_URL . 'pengeluaran/index.php');
    exit;
}

$stmt = $pdo->prepare("DELETE FROM pengeluaran WHERE id_pengeluaran = ?");
$stmt->execute([$id]);

set_flash('success', 'Catatan pengeluaran berhasil dihapus.');
header('Location: ' . BASE_URL . 'pengeluaran/index.php');
exit;
