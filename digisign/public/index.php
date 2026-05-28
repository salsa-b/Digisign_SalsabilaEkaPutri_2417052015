<?php
require_once '../config/session.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';

$allowed_pages = ['home', 'setup_otp', 'reset_otp', 'request_id', 'set_signature', 'sign_document', 'signing_history', 'login', 'register', 'logout', 'admin/dashboard', 'admin/approve'];
$page = $_GET['page'] ?? 'home';
$page = preg_replace('/[^a-zA-Z0-9_\/]/', '', $page);
if (!in_array($page, $allowed_pages, true)) $page = 'home';

if (in_array($page, ['login', 'register'])) {
    require_once "../pages/{$page}.php";
    exit;
}

if ($page === 'logout') {
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

if (str_starts_with($page, 'admin/') && ($_SESSION['role'] ?? '') !== 'admin') {
    die('Unauthorized');
}

$page_file = "../pages/{$page}.php";
require_once '../includes/header.php';
if (file_exists($page_file)) {
    require_once $page_file;
} else {
    echo "<div class='p-6'>Halaman tidak ditemukan</div>";
}
require_once '../includes/footer.php';