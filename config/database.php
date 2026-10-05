<?php
/**
 * Konfigurasi Database & Helper Aplikasi
 * Warung Makan Hanisa
 */

date_default_timezone_set('Asia/Jakarta');

$db_host = 'localhost';
$db_name = 'warung_hanisa';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . htmlspecialchars($e->getMessage()));
}

// Deteksi BASE_URL secara dinamis
if (!defined('BASE_URL')) {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($script, '/pemweb-obe') === 0 || strpos($uri, '/pemweb-obe') !== false) {
        define('BASE_URL', '/pemweb-obe/');
    } elseif (strpos($script, '/warung-hanisa') === 0 || strpos($uri, '/warung-hanisa') !== false) {
        define('BASE_URL', '/warung-hanisa/');
    } else {
        define('BASE_URL', '/pemweb-obe/');
    }
}

// Helper formatting mata uang Rupiah
if (!function_exists('format_rupiah')) {
    function format_rupiah($nominal) {
        return 'Rp' . number_format((float)$nominal, 0, ',', '.');
    }
}

// Helper sanitasi input
if (!function_exists('clean_input')) {
    function clean_input($data) {
        return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
    }
}

// Helper mengambil data pengaturan
if (!function_exists('get_app_settings')) {
    function get_app_settings($db = null) {
        global $pdo;
        $conn = $db ?: $pdo;
        static $settings = null;
        if ($settings === null && $conn) {
            $stmt = $conn->query("SELECT * FROM pengaturan LIMIT 1");
            $settings = $stmt->fetch();
            if (!$settings) {
                $settings = [
                    'nama_warung' => 'Warung Makan Hanisa',
                    'slogan' => 'Sistem Informasi Manajemen Penjualan dan Sistem Kasir',
                    'alamat' => 'Jl. Kaliurang KM 5, Yogyakarta',
                    'telepon' => '0812-3456-7890',
                    'logo' => 'logo.png',
                    'qris_image' => 'qris.png'
                ];
            }
        }
        return $settings;
    }
}
