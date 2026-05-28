<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (isLoggedIn()) {
    header('Location: index.php?page=home');
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $error_msg = '⚠️ Invalid request. Silakan coba lagi.';
    } else {
        $credential = trim($_POST['credential'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (empty($credential) || empty($password)) {
            $error_msg = '⚠️ Username/email dan password wajib diisi.';
        } else {
            try {
                $db = getDB();
                $sql = "SELECT id, username, email, password, role, otp_verified FROM users WHERE username = ? OR email = ? LIMIT 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([$credential, $credential]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id']  = (int)$user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email']    = $user['email'];
                    $_SESSION['role']     = $user['role'];
                    $_SESSION['otp_verified'] = (int)$user['otp_verified'];

                    if ((int)$user['otp_verified'] === 0) {
                        header('Location: index.php?page=setup_otp');
                    } else {
                        $redirect = $_GET['redirect'] ?? '';
                        if ($user['role'] === 'admin') {
                            header('Location: index.php?page=admin/dashboard');
                        } elseif (!empty($redirect)) {
                            header('Location: ' . urldecode($redirect));
                        } else {
                            header('Location: index.php?page=home');
                        }
                    }
                    exit;
                } else {
                    $error_msg = '❌ Username/email atau password salah.';
                }
            } catch (Exception $e) {
                $error_msg = '🐞 ERROR: ' . $e->getMessage();
            }
        }
    }
}

$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – DigiSign</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

<div class="card w-full max-w-md bg-white shadow-xl border border-slate-200">
    <div class="card-body p-8">

        <div class="text-center mb-6">
            <span class="text-5xl">🔏</span>
            <h1 class="text-2xl font-bold text-slate-800 mt-2">DigiSign</h1>
            <p class="text-slate-500 text-sm">Digital Signature System</p>
        </div>

        <h2 class="text-lg font-semibold text-slate-700 mb-4">Masuk ke Akun</h2>

        <?php if ($error_msg): ?>
        <div class="alert alert-error mb-4 py-3 text-sm">
            <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($_GET['registered'])): ?>
        <div class="alert alert-success mb-4 py-3 text-sm">
            <span>✅ Registrasi berhasil! Silakan login.</span>
        </div>
        <?php endif; ?>

        <?php if (!empty($_GET['timeout'])): ?>
        <div class="alert alert-warning mb-4 py-3 text-sm">
            <span>️ Sesi Anda habis. Silakan login kembali.</span>
        </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=login" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <div class="form-control">
                <label class="label pb-1">
                    <span class="label-text font-medium">Username atau Email</span>
                </label>
                <input type="text"
                       name="credential"
                       class="input input-bordered w-full"
                       placeholder="Masukkan username atau email"
                       value="<?= htmlspecialchars($_POST['credential'] ?? '') ?>"
                       required autofocus>
            </div>

            <div class="form-control">
                <label class="label pb-1">
                    <span class="label-text font-medium">Password</span>
                </label>
                <input type="password"
                       name="password"
                       class="input input-bordered w-full"
                       placeholder="Masukkan password"
                       required>
            </div>

            <button type="submit" class="btn btn-primary w-full mt-2">
                🔑 Masuk
            </button>
        </form>

        <div class="divider text-slate-400 text-xs">atau</div>

        <p class="text-center text-sm text-slate-600">
            Belum punya akun?
            <a href="index.php?page=register" class="text-blue-600 hover:underline font-medium">Daftar di sini</a>
        </p>
    </div>
</div>

</body>
</html>