<?php
/**
 * Auth Middleware & Session Handler
 * Warung Makan Hanisa
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Cek apakah user sedang login
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Mengambil data user yang sedang login
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'nama' => $_SESSION['nama'] ?? '',
        'username' => $_SESSION['username'] ?? '',
        'role' => $_SESSION['role'] ?? 'kasir'
    ];
}

// Cek apakah role saat ini adalah admin
function is_admin() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

// Cek apakah role saat ini adalah kasir
function is_kasir() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'kasir';
}

// Proteksi halaman: wajib login
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses sistem.';
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

// Proteksi halaman: wajib role admin
function require_admin() {
    require_login();
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Akses ditolak! Anda tidak memiliki hak akses Administrator.';
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}

// Helper Flash Message
function set_flash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

function get_flash($type) {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}
