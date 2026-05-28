<?php
$error_msg   = '';
$success_msg = '';
$form_role   = '';

function statusBadge(int $val): string {
    return $val
        ? '<span class="badge badge-success badge-sm gap-1">🟢 1</span>'
        : '<span class="badge badge-ghost badge-sm text-slate-400">⚪ 0</span>';
}

function roleIcon(string $role): string {
    $icons = ['dosen' => '👨‍🏫', 'mahasiswa' => '🎓', 'tendik' => '🏛️'];
    return ($icons[strtolower($role)] ?? '👤') . ' ' . ucfirst($role);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_role  = trim($_POST['role'] ?? '');
    $passphrase = $_POST['passphrase'] ?? '';
    $confirm    = $_POST['confirm_passphrase'] ?? '';

    $valid_roles = ['dosen', 'tendik', 'mahasiswa'];

    if (!in_array($form_role, $valid_roles, true)) {
        $error_msg = '⚠️ Pilih role yang valid (Dosen / Tendik / Mahasiswa).';
    } elseif (strlen($passphrase) < 8) {
        $error_msg = '⚠️ Passphrase minimal 8 karakter.';
    } elseif ($passphrase !== $confirm) {
        $error_msg = '⚠️ Konfirmasi passphrase tidak cocok.';
    } else {
        $passphrase_hash = password_hash($passphrase, PASSWORD_BCRYPT);
        $user_id         = (int)$_SESSION['user_id'];

        try {
            $db   = getDB();
            $stmt = $db->prepare("
                INSERT INTO digital_id_requests
                    (user_id, role, passphrase_hash, is_approved, is_ready, is_sent)
                VALUES
                    (:user_id, :role, :passphrase_hash, 0, 0, 0)
            ");
            $stmt->execute([
                ':user_id'         => $user_id,
                ':role'            => $form_role,
                ':passphrase_hash' => $passphrase_hash,
            ]);
            $success_msg = '✅ Permohonan berhasil dikirim! Menunggu persetujuan admin.';
            $form_role   = '';
        } catch (RuntimeException $e) {
            if (!isset($_SESSION['id_requests_offline'])) {
                $_SESSION['id_requests_offline'] = [];
            }
            $_SESSION['id_requests_offline'][] = [
                'id'          => count($_SESSION['id_requests_offline']) + 1,
                'role'        => $form_role,
                'created_at'  => date('Y-m-d H:i:s'),
                'is_approved' => 0,
                'is_ready'    => 0,
                'is_sent'     => 0,
            ];
            $success_msg = '✅ Permohonan berhasil dicatat (mode offline). Menunggu persetujuan admin.';
            $form_role   = '';
        }
    }
}

$requests  = [];
$db_error  = false;
$user_id_q = $_SESSION['user_id'] ?? 1;

try {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT id, role, is_approved, is_ready, is_sent,
               DATE_FORMAT(created_at, '%Y-%m-%d') AS submitted
        FROM digital_id_requests
        WHERE user_id = :uid
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([':uid' => $user_id_q]);
    $requests = $stmt->fetchAll();
} catch (RuntimeException $e) {
    $db_error = true;
    $requests = $_SESSION['id_requests_offline'] ?? [];
}
?>

<div class="max-w-2xl mx-auto space-y-6">

    <div>
        <h2 class="text-2xl font-bold text-slate-800">🪪 Request Digital ID</h2>
        <p class="text-sm text-slate-500 mt-1">Ajukan permohonan sertifikat tanda tangan digital sesuai peran Anda.</p>
    </div>

    <?php if ($error_msg): ?>
    <div class="alert alert-error shadow-sm text-sm">
        <span><?= htmlspecialchars($error_msg) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
    <div class="alert alert-success shadow-sm text-sm">
        <span><?= $success_msg ?></span>
    </div>
    <?php endif; ?>

    <?php if ($db_error): ?>
    <div class="alert alert-warning shadow-sm text-xs">
        <span>⚠️ Database tidak tersedia. Data disimpan sementara di sesi.</span>
    </div>
    <?php endif; ?>

    <div class="card bg-white border border-slate-200 shadow-sm">
        <div class="card-body p-7 space-y-5">
            <h3 class="font-semibold text-slate-700 text-base border-b border-slate-100 pb-3">Formulir Permohonan</h3>

            <form method="POST" action="index.php?page=request_id">

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Peran / Role <span class="text-red-500">*</span>
                    </label>
                    <select name="role" class="select select-bordered w-full focus:select-primary" required>
                        <option value="" disabled <?= $form_role === '' ? 'selected' : '' ?>>— Pilih peran Anda —</option>
                        <option value="dosen"     <?= $form_role === 'dosen'     ? 'selected' : '' ?>>Dosen</option>
                        <option value="tendik"    <?= $form_role === 'tendik'    ? 'selected' : '' ?>>Tenaga Kependidikan (Tendik)</option>
                        <option value="mahasiswa" <?= $form_role === 'mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Passphrase <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="passphrase" placeholder="Minimal 8 karakter"
                           class="input input-bordered w-full focus:input-primary"
                           minlength="8" required autocomplete="new-password">
                    <p class="text-xs text-slate-400 mt-1">Digunakan untuk melindungi kunci privat sertifikat Anda. Jangan sampai lupa.</p>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Konfirmasi Passphrase <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="confirm_passphrase" placeholder="Ulangi passphrase"
                           class="input input-bordered w-full focus:input-primary"
                           minlength="8" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary w-full text-base gap-2">
                    🪪 Request Digital ID
                </button>

            </form>
        </div>
    </div>

    <div class="card bg-white border border-slate-200 shadow-sm">
        <div class="card-body p-7">
            <h3 class="font-semibold text-slate-700 text-base border-b border-slate-100 pb-3 mb-4">📊 Status Permohonan</h3>

            <?php if (empty($requests)): ?>
            <div class="text-center py-8 text-slate-400 text-sm">
                <span class="text-3xl block mb-2">📭</span>
                Belum ada permohonan. Isi formulir di atas untuk memulai.
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <tr>
                            <th class="w-10">#</th>
                            <th>Role</th>
                            <th>Tgl Submit</th>
                            <th class="text-center">Approved</th>
                            <th class="text-center">Ready</th>
                            <th class="text-center">Sent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                        <tr>
                            <td class="text-slate-400"><?= (int)$r['id'] ?></td>
                            <td class="font-medium text-slate-700"><?= roleIcon($r['role']) ?></td>
                            <td class="text-slate-500"><?= htmlspecialchars($r['submitted'] ?? '-') ?></td>
                            <td class="text-center"><?= statusBadge((int)$r['is_approved']) ?></td>
                            <td class="text-center"><?= statusBadge((int)$r['is_ready']) ?></td>
                            <td class="text-center"><?= statusBadge((int)$r['is_sent']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-500">
                <span class="flex items-center gap-1.5"><span class="badge badge-success badge-xs"></span> Selesai / Disetujui</span>
                <span class="flex items-center gap-1.5"><span class="badge badge-ghost badge-xs"></span> Menunggu / Belum</span>
                <span class="ml-auto italic">Pipeline: Approved → Ready → Sent</span>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
