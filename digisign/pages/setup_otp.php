<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../lib/otp.php';

requireLogin();

// Jika sudah verified, tendang ke home (Pakai JS karena header HTML sudah dikirim index.php)
if (!empty($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === 1) {
    echo '<script>window.location.href="index.php?page=home";</script>';
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT otp_secret FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$otp_secret = $user['otp_secret'] ?? '';

if (empty($otp_secret)) {
    $otp_secret = generateOtpSecret();
    $stmt = $db->prepare("UPDATE users SET otp_secret = ? WHERE id = ?");
    $stmt->execute([$otp_secret, $user_id]);
}

$qr_code_url = getQrCodeUrl($otp_secret, $_SESSION['username'] . '@digisign');

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = trim($_POST['otp_code'] ?? '');
    if (empty($otp_input)) {
        $message = 'Kode OTP tidak boleh kosong';
        $message_type = 'error';
    } else {
        if (verifyOtp($otp_secret, $otp_input)) {
            $stmt = $db->prepare("UPDATE users SET otp_verified = 1 WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['otp_verified'] = 1;
            
            // Redirect ke dashboard setelah sukses (Pakai JS)
            echo '<script>window.location.href="index.php?page=home";</script>';
            exit;
        } else {
            $message = 'Kode OTP salah atau kadaluarsa';
            $message_type = 'error';
        }
    }
}
?>

<div class="p-6 max-w-lg mx-auto">
    <div class="card bg-white shadow-lg">
        <div class="card-body">
            <h2 class="card-title text-xl font-bold mb-4">Setup Google Authenticator</h2>
            <?php if ($message): ?>
            <div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?> mb-4">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>
            <div class="alert alert-warning mb-4">
                <p>Verifikasi OTP wajib dilakukan sebelum mengakses fitur lainnya.</p>
            </div>
            <div class="text-center mb-6">
                <div class="inline-block p-4 bg-gray-100 rounded-lg mb-2">
                    <img src="<?= htmlspecialchars($qr_code_url) ?>" alt="QR Code OTP" class="w-48 h-48">
                </div>
                <?php if ($otp_secret): ?>
                <p class="mt-2 text-sm text-gray-600">Secret Key: <code class="bg-gray-100 px-2 py-1 rounded font-mono"><?= htmlspecialchars($otp_secret) ?></code></p>
                <?php endif; ?>
            </div>
            <form method="POST" class="space-y-4">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Masukkan Kode OTP (6 Digit)</span>
                    </label>
                    <input type="text" name="otp_code" maxlength="6" pattern="[0-9]{6}" class="input input-bordered text-center text-2xl tracking-widest" placeholder="000000" required>
                </div>
                <button type="submit" class="btn btn-primary w-full">Verifikasi & Lanjutkan</button>
            </form>
            <div class="mt-6 text-sm text-gray-500">
                <p>• Kode OTP berubah setiap 30 detik</p>
                <p>• Gunakan aplikasi Google Authenticator di HP Anda</p>
            </div>
        </div>
    </div>
</div>