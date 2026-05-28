<?php
ob_start();
require_once '../config/session.php';
require_once '../lib/otp.php';

if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) {
    echo '<script>window.location.href = "index.php?page=home";</script>';
    exit();
}

if (!isset($_SESSION['otp_secret'])) {
    $_SESSION['otp_secret'] = generateTOTPSecret();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code   = $_POST['otp_code'] ?? '';
    $secret = $_SESSION['otp_secret'] ?? '';

    if (verifyTOTP($secret, $code)) {
        $_SESSION['otp_verified']  = true;
        $_SESSION['last_activity'] = time();
        echo '<script>window.location.href = "index.php?page=home";</script>';
        exit();
    } else {
        $error = 'Kode OTP salah!';
    }
}

if (isset($_GET['reset'])) {
    unset($_SESSION['otp_secret']);
    $_SESSION['otp_secret'] = generateTOTPSecret();
    echo '<script>window.location.href = "index.php?page=setup_otp";</script>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup OTP - DigiSign</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50">
    <div class="p-6 max-w-2xl mx-auto">
        <div class="card bg-white shadow-lg">
            <div class="card-body">
                <h2 class="card-title text-xl font-bold mb-4">Setup Google OTP</h2>

                <?php if ($error): ?>
                    <div class="alert alert-error mb-4"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="alert alert-warning mb-4">
                    <p class="font-semibold">Anda belum memverifikasi OTP. Semua fitur terkunci sampai OTP diverifikasi.</p>
                </div>

                <div class="text-center mb-6">
                    <div class="inline-block p-4 bg-gray-100 rounded-lg mb-2">
                        <?php
                        $secret = $_SESSION['otp_secret'] ?? '';
                        $email  = $_SESSION['user_email'] ?? 'admin@digisign.local';
                        $qrUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode('otpauth://totp/DigiSign:' . $email . '?secret=' . $secret . '&issuer=DigiSign');
                        ?>
                        <img src="<?php echo $qrUrl; ?>" alt="QR Code OTP" class="w-48 h-48">
                    </div>
                    <?php if ($secret): ?>
                        <p class="mt-2 text-sm text-gray-600">Secret Key: <code class="bg-gray-100 px-2 py-1 rounded font-mono"><?php echo $secret; ?></code></p>
                    <?php endif; ?>
                </div>

                <form method="POST" class="space-y-4">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Masukkan Kode OTP (6 Digit)</span>
                        </label>
                        <input type="text" name="otp_code" maxlength="6" pattern="[0-9]{6}" class="input input-bordered text-center text-2xl tracking-widest" placeholder="000000" required>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="btn btn-primary flex-1">Verify OTP</button>
                        <a href="index.php?page=setup_otp&reset=1" class="btn btn-outline">Request Reset</a>
                    </div>
                </form>

                <div class="mt-6 text-sm text-gray-500 space-y-1">
                    <p>• Kode OTP berubah setiap 30 detik</p>
                    <p>• Verifikasi ulang diperlukan jika session > 15 menit</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
