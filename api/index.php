<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Metode HTTP tidak diizinkan. Hanya metode GET yang diperbolehkan.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(200);

echo json_encode([
    'status' => 'ok',
    'message' => 'Direktori API Warung Makan Hanisa aktif',
    'endpoints' => [
        'GET /api/index.php' => 'Informasi dan indeks endpoint API',
        'GET /api/health.php' => 'Pemeriksaan status kesehatan API',
        'GET /api/alats.php' => 'Mendapatkan daftar data alat',
        'POST /api/alats.php' => 'Menambahkan data alat baru',
        'GET /api/dashboard.php' => 'Mendapatkan data metrik dashboard kasir'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
