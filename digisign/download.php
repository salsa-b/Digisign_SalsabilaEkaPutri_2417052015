<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(0);
ini_set('display_errors', 0);

require_once 'config/database.php';
require_once 'config/session.php';

if (!isset($_GET['id'])) {
    exit('ID tidak ditemukan');
}

if (!isset($_SESSION['user_id'])) {
    exit('Session user tidak ditemukan. Silakan login ulang.');
}

$id      = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $db   = getDB();
    $stmt = $db->prepare("SELECT pdf_path FROM signing_requests WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $file = $stmt->fetch();

    if (!$file || empty($file['pdf_path'])) {
        exit('File tidak ditemukan di database');
    }

    $filePath = __DIR__ . '/' . $file['pdf_path'];

    if (!file_exists($filePath)) {
        $filePath = __DIR__ . '/assets/' . $file['pdf_path'];
    }

    if (!file_exists($filePath)) {
        exit('File fisik tidak ditemukan di server');
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    readfile($filePath);
    exit;

} catch (Exception $e) {
    exit('Error sistem');
}
