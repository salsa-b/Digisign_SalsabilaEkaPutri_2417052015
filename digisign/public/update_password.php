<?php
require_once '../config/database.php';
$db = getDB();

$admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
$user_hash = password_hash('password123', PASSWORD_DEFAULT);

try {
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$admin_hash, 'admin123']);
    $stmt->execute([$user_hash, 'testuser']);

    echo "<h1>✅ Password berhasil diupdate!</h1><br>";
    echo "<h3>Login dengan:</h3>";
    echo "👑 Admin: admin123 / admin123<br>";
    echo "👤 User: testuser / password123<br><br>";
    echo "<a href='index.php?page=login' style='background:blue;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Login Sekarang</a>";
} catch (Exception $e) {
    echo " Error: " . $e->getMessage();
}
?>