<?php
/**
 * Root Index Router
 * Warung Makan Hanisa
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
} else {
    header('Location: ' . BASE_URL . 'login.php');
}
exit;
