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
$form      = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $error_msg = '⚠️ Invalid request. Silakan coba lagi.';
    } else {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';

        $form = ['username' => $username, 'email' => $email];

        if (empty($username) || empty($email) || empty($password)) {
            $error_msg = '⚠️ Semua field wajib diisi.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $error_msg = '⚠️ Username hanya boleh huruf, angka, underscore (3–30 karakter).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = '⚠️ Format email tidak valid.';
        } elseif (strlen($password) < 8) {
            $error_msg = '⚠️ Password minimal 8 karakter.';
        } elseif ($password !== $confirm) {
            $error_msg = '⚠️ Konfirmasi password tidak cocok.';
        } else {
            try {
                $db = getDB();

                $chk = $db->prepare("SELECT id FROM users WHERE username=:u OR email=:e LIMIT 1");
                $chk->execute([':u' => $username, ':e' => $email]);
                if ($chk->fetch()) {
                    $error_msg = '⚠️ Username atau email sudah terdaftar.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $ins  = $db->prepare(
                        "INSERT INTO users (username, email, password, role, created_at)
                         VALUES (:username, :email, :password, 'user', NOW())"
                    );
                    $ins->execute([
                        ':username' => $username,
                        ':email'    => $email,
                        ':password' => $hash,
                    ]);
                    header('Location: index.php?page=login&registered=1');
                    exit;
                }
            } catch (RuntimeException $e) {
                $error_msg = '⚠️ Terjadi kesalahan. Silakan coba lagi.';
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
    <title>Register – DigiSign</title>
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

        <h2 class="text-lg font-semibold text-slate-700 mb-4">Buat Akun Baru</h2>

        <?php if ($error_msg): ?>
        <div class="alert alert-error mb-4 py-3 text-sm">
            <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=register" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <div class="form-control">
                <label class="label pb-1">
                    <span class="label-text font-medium">Username</span>
                </label>
                <input type="text"
                       name="username"
                       class="input input-bordered w-full"
                       placeholder="Contoh: budi_santoso"
                       value="<?= htmlspecialchars($form['username']) ?>"
                       pattern="[a-zA-Z0-9_]{3,30}"
                       required autofocus>
                <label class="label pt-1">
                    <span class="label-text-alt text-slate-400">Huruf, angka, underscore (3–30 karakter)</span>
                </label>
            </div>

            <div class="form-control">
                <label class="label pb-1">
                    <span class="label-text font-medium">Email</span>
                </label>
                <input type="email"
                       name="email"
                       class="input input-bordered w-full"
                       placeholder="Contoh: budi@example.com"
                       value="<?= htmlspecialchars($form['email']) ?>"
                       required>
            </div>

            <div class="form-control">
                <label class="label pb-1">
                    <span class="label-text font-medium">Password</span>
                </label>
                <input type="password"
                       name="password"
                       class="input input-bordered w-full"
                       placeholder="Minimal 8 karakter"
                       minlength="8"
                       required>
            </div>

            <div class="form-control">
                <label class="label pb-1">
                    <span class="label-text font-medium">Konfirmasi Password</span>
                </label>
                <input type="password"
                       name="confirm_password"
                       class="input input-bordered w-full"
                       placeholder="Ulangi password"
                       minlength="8"
                       required>
            </div>

            <button type="submit" class="btn btn-primary w-full mt-2">
                📝 Daftar Sekarang
            </button>
        </form>

        <div class="divider text-slate-400 text-xs">atau</div>

        <p class="text-center text-sm text-slate-600">
            Sudah punya akun?
            <a href="index.php?page=login" class="text-blue-600 hover:underline font-medium">Login di sini</a>
        </p>
    </div>
</div>

</body>
</html>