<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();

$show_form = true;
$redirect_to_setup = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();
        $new_secret = base32_encode(random_bytes(20));
        $stmt = $db->prepare("UPDATE users SET otp_secret = ?, otp_verified = 0 WHERE id = ?");
        $stmt->execute([$new_secret, $_SESSION['user_id']]);
        $_SESSION['otp_verified'] = 0;
        $_SESSION['otp_secret'] = $new_secret;
        $show_form = false;
        $redirect_to_setup = true;
    } catch (Exception $e) {
        $error = '⚠️ Gagal mereset OTP.';
    }
}

if ($redirect_to_setup) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv="refresh" content="0;url=index.php?page=setup_otp">
    </head>
    <body>
        <script>window.location.href="index.php?page=setup_otp";</script>
        Redirecting...
    </body>
    </html>
    <?php
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="w-full px-6 py-6">
    <div class="max-w-2xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">Reset OTP</h1>
            <p class="text-slate-500 text-sm">Ganti kunci autentikasi Anda.</p>
        </div>

        <div class="card bg-white shadow-sm border border-slate-200">
            <div class="card-body p-6">
                <div class="alert alert-warning mb-6">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">⚠️</span>
                        <div>
                            <h3 class="font-bold">Konfirmasi Reset OTP</h3>
                            <p class="text-sm mt-1">Reset akan menghapus OTP lama dan membuat yang baru.</p>
                        </div>
                    </div>
                </div>

                <?php if (!empty($error)): ?>
                <div class="alert alert-error mb-4"><span><?= htmlspecialchars($error) ?></span></div>
                <?php endif; ?>

                <div class="flex justify-end gap-3">
                    <a href="index.php?page=home" class="btn btn-ghost">Batal</a>
                    <form method="POST">
                        <button type="submit" class="btn btn-warning">Reset & Setup Ulang</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
function base32_encode($data) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $result = '';
    $buffer = 0;
    $bitsLeft = 0;
    for ($i = 0; $i < strlen($data); $i++) {
        $buffer = ($buffer << 8) | ord($data[$i]);
        $bitsLeft += 8;
        while ($bitsLeft >= 5) {
            $result .= $chars[($buffer >> ($bitsLeft - 5)) & 31];
            $bitsLeft -= 5;
        }
    }
    if ($bitsLeft > 0) {
        $result .= $chars[($buffer << (5 - $bitsLeft)) & 31];
    }
    return $result;
}
?>