<?php
/**
 * REST API Endpoint: Dashboard Data Warung Makan Hanisa
 * Menyediakan data agregat, metrik penjualan, stok produk, dan transaksi terkini.
 * 
 * Metode: GET
 * Parameter Query Opsional:
 * - search       : Filter pencarian nama menu (contoh: ?search=ayam)
 * - kategori     : Filter berdasarkan ID atau nama kategori (contoh: ?kategori=1 atau ?kategori=Makanan)
 * - status_stok  : Filter berdasarkan kondisi stok ('semua', 'aman', 'menipis', 'habis')
 * - simulate_error: Set '1' untuk simulasi kegagalan server guna pengujian error state (HTTP 500)
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Dukungan pengujian: Skenario simulasi kegagalan request (Latihan 3)
if (isset($_GET['simulate_error']) && $_GET['simulate_error'] === '1') {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Simulasi kesalahan server (HTTP 500): Gagal mengambil data dari database.',
        'data' => null
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../config/database.php';

    // 1. Parameter Filter
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
    $statusStok = isset($_GET['status_stok']) ? trim($_GET['status_stok']) : '';

    // 2. Data Ringkasan (Metrics)
    $stmtPenjualan = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM pesanan WHERE status != 'batal'");
    $totalPenjualan = (float)$stmtPenjualan->fetchColumn();

    $stmtPenjualanHariIni = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM pesanan WHERE DATE(tanggal) = CURDATE() AND status != 'batal'");
    $penjualanHariIni = (float)$stmtPenjualanHariIni->fetchColumn();

    $stmtTrx = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status != 'batal'");
    $totalTransaksi = (int)$stmtTrx->fetchColumn();

    $stmtTrxHariIni = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE DATE(tanggal) = CURDATE() AND status != 'batal'");
    $transaksiHariIni = (int)$stmtTrxHariIni->fetchColumn();

    $stmtProdukCount = $pdo->query("SELECT COUNT(*) FROM produk WHERE status = 'aktif'");
    $totalProduk = (int)$stmtProdukCount->fetchColumn();

    $stmtStokTotal = $pdo->query("SELECT COALESCE(SUM(stok), 0) FROM produk WHERE status = 'aktif'");
    $totalStok = (int)$stmtStokTotal->fetchColumn();

    $stmtMenipis = $pdo->query("SELECT COUNT(*) FROM produk WHERE status = 'aktif' AND stok > 0 AND stok <= 10");
    $produkMenipis = (int)$stmtMenipis->fetchColumn();

    $stmtHabis = $pdo->query("SELECT COUNT(*) FROM produk WHERE status = 'aktif' AND stok <= 0");
    $produkHabis = (int)$stmtHabis->fetchColumn();

    // 3. Daftar Kategori Aktif beserta jumlah produk
    $stmtKategori = $pdo->query("
        SELECT k.id_kategori, k.nama_kategori, COUNT(p.id_produk) AS jumlah_produk
        FROM kategori k
        LEFT JOIN produk p ON k.id_kategori = p.id_kategori AND p.status = 'aktif'
        WHERE k.status = 'aktif'
        GROUP BY k.id_kategori, k.nama_kategori
        ORDER BY k.id_kategori ASC
    ");
    $kategoriList = $stmtKategori->fetchAll(PDO::FETCH_ASSOC);

    // 4. Query Produk dengan Filter Dinamis
    $queryProduk = "
        SELECT p.id_produk, p.nama_produk, p.id_kategori, k.nama_kategori,
               p.harga, p.gambar, p.deskripsi, p.stok, p.opsi_suhu, p.status
        FROM produk p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        WHERE p.status = 'aktif'
    ";
    $params = [];

    if ($search !== '') {
        $queryProduk .= " AND (p.nama_produk LIKE :search_nama OR k.nama_kategori LIKE :search_kat)";
        $params[':search_nama'] = '%' . $search . '%';
        $params[':search_kat'] = '%' . $search . '%';
    }

    if ($kategori !== '' && $kategori !== 'all' && $kategori !== 'Semua') {
        if (is_numeric($kategori)) {
            $queryProduk .= " AND p.id_kategori = :kategori";
            $params[':kategori'] = (int)$kategori;
        } else {
            $queryProduk .= " AND k.nama_kategori = :kategori";
            $params[':kategori'] = $kategori;
        }
    }

    if ($statusStok === 'menipis') {
        $queryProduk .= " AND p.stok > 0 AND p.stok <= 10";
    } elseif ($statusStok === 'habis') {
        $queryProduk .= " AND p.stok <= 0";
    } elseif ($statusStok === 'aman') {
        $queryProduk .= " AND p.stok > 10";
    }

    $queryProduk .= " ORDER BY p.id_kategori ASC, p.nama_produk ASC";

    $stmtProduk = $pdo->prepare($queryProduk);
    $stmtProduk->execute($params);
    $rawProduk = $stmtProduk->fetchAll(PDO::FETCH_ASSOC);

    $produkList = [];
    foreach ($rawProduk as $row) {
        $stokVal = (int)$row['stok'];
        $statusLabel = 'aman';
        if ($stokVal <= 0) {
            $statusLabel = 'habis';
        } elseif ($stokVal <= 10) {
            $statusLabel = 'menipis';
        }

        $produkList[] = [
            'id_produk'     => (int)$row['id_produk'],
            'nama_produk'   => $row['nama_produk'],
            'id_kategori'   => (int)$row['id_kategori'],
            'nama_kategori' => $row['nama_kategori'],
            'harga'         => (float)$row['harga'],
            'stok'          => $stokVal,
            'status_stok'   => $statusLabel,
            'opsi_suhu'     => $row['opsi_suhu'],
            'gambar'        => $row['gambar'] ? $row['gambar'] : 'default.jpg'
        ];
    }

    // 5. Transaksi Terbaru (5 data terakhir)
    $stmtRecent = $pdo->query("
        SELECT p.id_pesanan, p.nomor_pesanan, p.tanggal, p.total, p.status,
               u.nama AS nama_kasir,
               COALESCE(pm.metode_pembayaran, 'cash') AS metode_pembayaran
        FROM pesanan p
        JOIN users u ON p.id_user = u.id_user
        LEFT JOIN pembayaran pm ON p.id_pesanan = pm.id_pesanan
        ORDER BY p.id_pesanan DESC
        LIMIT 5
    ");
    $recentOrders = [];
    foreach ($stmtRecent->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $recentOrders[] = [
            'id_pesanan'        => (int)$r['id_pesanan'],
            'nomor_pesanan'     => $r['nomor_pesanan'],
            'tanggal'           => $r['tanggal'],
            'total'             => (float)$r['total'],
            'status'            => $r['status'],
            'nama_kasir'        => $r['nama_kasir'],
            'metode_pembayaran' => $r['metode_pembayaran']
        ];
    }

    // Susun response JSON
    $response = [
        'status'    => 'success',
        'message'   => 'Data dashboard berhasil dimuat',
        'timestamp' => date('Y-m-d H:i:s'),
        'data'      => [
            'ringkasan' => [
                'total_penjualan'          => $totalPenjualan,
                'penjualan_hari_ini'       => $penjualanHariIni,
                'total_transaksi'          => $totalTransaksi,
                'transaksi_hari_ini'       => $transaksiHariIni,
                'total_produk'             => $totalProduk,
                'total_stok'               => $totalStok,
                'produk_stok_menipis'      => $produkMenipis,
                'produk_habis'             => $produkHabis,
                'total_kategori'           => count($kategoriList)
            ],
            'kategori'          => $kategoriList,
            'produk'            => $produkList,
            'transaksi_terbaru' => $recentOrders
        ]
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal mengambil data dashboard: ' . $e->getMessage(),
        'data'    => null
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
