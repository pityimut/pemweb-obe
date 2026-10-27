<?php
header('Content-Type: application/json; charset=utf-8');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Pemeriksaan metode HTTP yang didukung (hanya GET dan POST)
if ($method !== 'GET' && $method !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Metode HTTP tidak diizinkan. Hanya metode GET dan POST yang diperbolehkan.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function requireJsonContentType()
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

    if ($contentType === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $val) {
            if (strcasecmp($key, 'Content-Type') === 0) {
                $contentType = $val;
                break;
            }
        }
    }

    $mediaType = strtolower(trim(explode(';', $contentType, 2)[0]));

    if ($mediaType === 'application/json') {
        return;
    }

    http_response_code(415);
    echo json_encode([
        'status' => 'error',
        'message' => 'Content-Type harus application/json.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Tangani Request GET
if ($method === 'GET') {
    http_response_code(200);
    echo json_encode([
        'data' => [
            [
                'id' => 1,
                'nama' => 'Router',
                'jumlah' => 4
            ],
            [
                'id' => 2,
                'nama' => 'Multimeter',
                'jumlah' => 6
            ]
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Tangani Request POST
if ($method === 'POST') {
    requireJsonContentType();

    $rawInput = file_get_contents('php://input');
    // Cek jika body kosong
    if ($rawInput === false || trim($rawInput) === '') {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Format JSON tidak valid atau body request kosong'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    $inputObject = json_decode($rawInput);

    // Cek sintaks JSON rusak
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Format JSON tidak valid atau rusak'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Pastikan root JSON adalah object
    if (!is_object($inputObject)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Body request harus berupa format JSON object'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $errors = [];

    // Validasi field 'nama'
    if (!property_exists($inputObject, 'nama') || $inputObject->nama === null) {
        $errors[] = 'Field nama wajib diisi.';
    } elseif (!is_string($inputObject->nama)) {
        $errors[] = 'Field nama harus berupa tipe string.';
    } elseif (trim($inputObject->nama) === '') {
        $errors[] = 'Field nama tidak boleh kosong.';
    } elseif (mb_strlen(trim($inputObject->nama)) > 100) {
        $errors[] = 'Field nama tidak boleh lebih dari 100 karakter (maksimal 100 karakter).';
    }

    // Validasi field 'jumlah'
    if (!property_exists($inputObject, 'jumlah') || $inputObject->jumlah === null) {
        $errors[] = 'Field jumlah wajib diisi.';
    } elseif (!is_int($inputObject->jumlah)) {
        $errors[] = 'Field jumlah harus berupa bilangan bulat (integer), bukan string atau desimal.';
    } elseif ($inputObject->jumlah < 0) {
        $errors[] = 'Field jumlah tidak boleh bernilai negatif.';
    }

    // Jika aturan bisnis dilanggar -> 422
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode([
            'status' => 'error',
            'message' => 'Validasi gagal',
            'errors' => $errors
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Normalisasi data valid
    $nama = trim($inputObject->nama);
    $jumlah = (int)$inputObject->jumlah;

    // Sukses disimpan (sementara) -> 201
    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'message' => 'Data diterima',
        'data' => [
            'nama' => $nama,
            'jumlah' => $jumlah
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
