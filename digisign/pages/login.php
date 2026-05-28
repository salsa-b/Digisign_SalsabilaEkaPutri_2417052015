<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

if (isLoggedIn()) {
    if (!empty($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === 1) {
        if (($_SESSION['role'] ?? '') === 'admin') {
            header('Location: index.php?page=admin/dashboard');
        } else {
            header('Location: index.php?page=home');
        }
    } else {
        header('Location: index.php?page=setup_otp');
    }
    exit;
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $credential = trim($_POST['credential'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($credential) || empty($password)) {
        $error_msg = 'Username/email dan password wajib diisi';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, username, email, password, role, otp_verified, otp_secret FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$credential, $credential]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['otp_verified'] = (int)$user['otp_verified'];
                $_SESSION['otp_secret'] = $user['otp_secret'];
                $_SESSION['otp_login_verified'] = 0;
                
                if ((int)$user['otp_verified'] === 0 || empty($user['otp_secret'])) {
                    header('Location: index.php?page=setup_otp');
                } else {
                    header('Location: index.php?page=verify_otp_login');
                }
                exit;
            } else {
                $error_msg = 'Username/email atau password salah';
            }
        } catch (Exception $e) {
            $error_msg = 'Terjadi kesalahan sistem';
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
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-control">
                <label class="label pb-1">
                    <span class="label-text font-medium">Username atau Email</span>
                </label>
                <input type="text" name="credential" class="input input-bordered w-full" placeholder="Masukkan username atau email" value="<?= htmlspecialchars($_POST['credential'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-control">
                <label class="label pb-1">
                    <span class="label-text font-medium">Password</span>
                </label>
                <input type="password" name="password" class="input input-bordered w-full" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn btn-primary w-full mt-2">Masuk</button>
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