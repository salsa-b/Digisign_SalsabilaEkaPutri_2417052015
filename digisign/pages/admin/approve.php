<?php
require_once __DIR__ . '/../../config/database.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['req_id'], $_POST['action'])) {
    $status = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $stmt = $db->prepare("UPDATE digital_id_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $_POST['req_id']]);
}

header('Location: index.php?page=admin/dashboard');
exit;