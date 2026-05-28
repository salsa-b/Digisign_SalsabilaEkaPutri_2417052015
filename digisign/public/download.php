<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();

$id = $_GET['id'] ?? 0;

if (!$id) {
    die('Invalid request');
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM signing_requests WHERE id = ?");
    $stmt->execute([$id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        die('Data tidak ditemukan');
    }
    
    if ($request['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
        die('Unauthorized');
    }
    
    if ($request['status'] !== 'signed') {
        die('File belum ditandatangani');
    }
    
    $pdf_path_db = $request['pdf_path'];
    
    $base_dir = dirname(__DIR__);
    
    $file_path_full = $base_dir . '/assets/' . $pdf_path_db;
    
    $info = pathinfo($file_path_full);
    $signed_path = $info['dirname'] . '/' . $info['filename'] . '_signed.' . $info['extension'];
    
    if (file_exists($signed_path)) {
        $file_path_full = $signed_path;
        $download_filename = basename($signed_path);
    } elseif (file_exists($file_path_full)) {
        $download_filename = basename($file_path_full);
    } else {
        die('File PDF tidak ditemukan di: ' . $file_path_full);
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($download_filename) . '"');
    header('Content-Length: ' . filesize($file_path_full));
    header('Cache-Control: no-cache, must-revalidate');
    
    readfile($file_path_full);
    exit;
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>