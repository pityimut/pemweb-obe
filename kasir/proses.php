<?php
/**
 * Backend Transaksi Kasir POS - Warung Makan Hanisa
 * Menangani validasi stok, database transaction (ACID), pembuatan nomor pesanan,
 * detail pesanan, pencatatan pembayaran & pemasukan.
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sesi login telah habis. Silakan login kembali.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode HTTP tidak diizinkan.']);
    exit;
}

// Ambil input JSON
$inputRaw = file_get_contents('php://input');
$payload = json_decode($inputRaw, true);

if (!$payload || !isset($payload['items']) || !is_array($payload['items']) || empty($payload['items'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Keranjang pesanan kosong. Pilih menu terlebih dahulu.']);
    exit;
}

$metode = in_array($payload['metode_pembayaran'] ?? '', ['cash', 'qris']) ? $payload['metode_pembayaran'] : 'cash';
$nominal_diterima = (float)($payload['nominal_diterima'] ?? 0);
$nama_pemesan = trim($payload['nama_pemesan'] ?? '');
$kontak_pemesan = trim($payload['kontak_pemesan'] ?? '');
$catatan_raw = trim($payload['catatan'] ?? '');

$catatan_parts = [];
if ($nama_pemesan !== '') {
    $pemesan_str = "Pemesan: " . $nama_pemesan;
    if ($kontak_pemesan !== '') {
        $pemesan_str .= " (" . $kontak_pemesan . ")";
    }
    $catatan_parts[] = $pemesan_str;
}
if ($catatan_raw !== '') {
    $catatan_parts[] = $catatan_raw;
}
$catatan = implode(' | ', $catatan_parts);
$id_user = (int)$_SESSION['user_id'];

// Mulai Database Transaction (ACID)
try {
    $pdo->beginTransaction();

    $total_recalculated = 0.0;
    $validated_items = [];

    // Validasi dan hitung ulang total murni dari database
    foreach ($payload['items'] as $item) {
        $id_produk = (int)($item['id_produk'] ?? 0);
        $qty = (int)($item['jumlah'] ?? 0);
        $suhu = $item['suhu'] ?? 'tidak_berlaku';

        if ($qty <= 0) {
            throw new Exception("Jumlah pesanan harus minimal 1.");
        }

        // Kunci baris dengan FOR UPDATE untuk mencegah race condition pengurangan stok
        $stmtProd = $pdo->prepare("SELECT * FROM produk WHERE id_produk = ? FOR UPDATE");
        $stmtProd->execute([$id_produk]);
        $prod = $stmtProd->fetch();

        if (!$prod || $prod['status'] !== 'aktif') {
            throw new Exception("Produk tidak ditemukan atau sedang dinonaktifkan.");
        }

        if ($prod['stok'] < $qty) {
            throw new Exception("Stok {$prod['nama_produk']} tidak mencukupi (Tersedia: {$prod['stok']}, Dipesan: {$qty}).");
        }

        // Validasi kesesuaian opsi suhu
        if ($prod['opsi_suhu'] === 'panas_dingin') {
            if (!in_array($suhu, ['panas', 'dingin'])) {
                $suhu = 'dingin'; // fallback
            }
        } elseif ($prod['opsi_suhu'] === 'dingin') {
            $suhu = 'dingin';
        } else {
            $suhu = 'tidak_berlaku';
        }

        $harga = (float)$prod['harga'];
        $subtotal = $harga * $qty;
        $total_recalculated += $subtotal;

        $validated_items[] = [
            'id_produk' => $id_produk,
            'nama_produk' => $prod['nama_produk'],
            'jumlah' => $qty,
            'harga' => $harga,
            'suhu' => $suhu,
            'subtotal' => $subtotal
        ];
    }

    if ($total_recalculated <= 0) {
        throw new Exception("Total pesanan tidak valid.");
    }

    // Validasi pembayaran
    $kembalian = 0.0;
    if ($metode === 'cash') {
        if ($nominal_diterima < $total_recalculated) {
            $selisih = $total_recalculated - $nominal_diterima;
            throw new Exception("Nominal pembayaran kurang " . format_rupiah($selisih));
        }
        $kembalian = $nominal_diterima - $total_recalculated;
    } else { // QRIS
        $nominal_diterima = $total_recalculated;
        $kembalian = 0.0;
    }

    // 1. Generate Nomor Pesanan Otomatis: TRX-YYYYMMDD-XXX
    $todayPrefix = 'TRX-' . date('Ymd') . '-';
    $stmtNum = $pdo->prepare("SELECT nomor_pesanan FROM pesanan WHERE nomor_pesanan LIKE ? ORDER BY id_pesanan DESC LIMIT 1");
    $stmtNum->execute([$todayPrefix . '%']);
    $lastOrder = $stmtNum->fetch();

    $nextNumber = 1;
    if ($lastOrder && preg_match('/(\d+)$/', $lastOrder['nomor_pesanan'], $matches)) {
        $nextNumber = ((int)$matches[1]) + 1;
    }
    $nomor_pesanan = $todayPrefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    // 2. Insert tabel pesanan
    $stmtPesanan = $pdo->prepare("
        INSERT INTO pesanan (id_user, nomor_pesanan, tanggal, total, status, catatan)
        VALUES (?, ?, NOW(), ?, 'selesai', ?)
    ");
    $stmtPesanan->execute([$id_user, $nomor_pesanan, $total_recalculated, $catatan]);
    $id_pesanan = (int)$pdo->lastInsertId();

    // 3. Insert tabel detail_pesanan & Update stok produk
    $stmtDetail = $pdo->prepare("
        INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga, suhu, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmtDeductStock = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");

    foreach ($validated_items as $vItem) {
        $stmtDetail->execute([
            $id_pesanan,
            $vItem['id_produk'],
            $vItem['jumlah'],
            $vItem['harga'],
            $vItem['suhu'],
            $vItem['subtotal']
        ]);

        $stmtDeductStock->execute([$vItem['jumlah'], $vItem['id_produk']]);
    }

    // 4. Insert tabel pembayaran
    $stmtBayar = $pdo->prepare("
        INSERT INTO pembayaran (id_pesanan, tanggal_pembayaran, metode_pembayaran, nominal_diterima, kembalian, status_pembayaran)
        VALUES (?, NOW(), ?, ?, ?, 'berhasil')
    ");
    $stmtBayar->execute([$id_pesanan, $metode, $nominal_diterima, $kembalian]);

    // 5. Insert tabel pemasukan
    $stmtPemasukan = $pdo->prepare("
        INSERT INTO pemasukan (id_pesanan, tanggal, jumlah, keterangan)
        VALUES (?, CURDATE(), ?, ?)
    ");
    $stmtPemasukan->execute([
        $id_pesanan,
        $total_recalculated,
        "Penjualan Kasir - " . $nomor_pesanan
    ]);

    // Komit seluruh transaksi
    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Transaksi berhasil disimpan.',
        'data' => [
            'id_pesanan' => $id_pesanan,
            'nomor_pesanan' => $nomor_pesanan,
            'nama_pemesan' => $nama_pemesan ?: 'Pelanggan Umum',
            'tanggal' => date('d/m/Y H:i'),
            'total' => $total_recalculated,
            'total_formatted' => format_rupiah($total_recalculated),
            'metode_pembayaran' => strtoupper($metode),
            'nominal_diterima' => $nominal_diterima,
            'diterima_formatted' => format_rupiah($nominal_diterima),
            'kembalian' => $kembalian,
            'kembalian_formatted' => format_rupiah($kembalian),
            'kasir' => $_SESSION['nama'] ?? 'Kasir'
        ]
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
