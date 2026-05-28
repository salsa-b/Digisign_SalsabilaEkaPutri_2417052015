<?php
$error_msg    = '';
$success_msg  = '';
$preview_path = '';

define('SIG_TARGET_RATIO',    2.23);
define('SIG_RATIO_TOLERANCE', 0.05);
define('SIG_MAX_SIZE_BYTES',  2 * 1024 * 1024);
define('SIG_UPLOAD_DIR',      dirname(__DIR__) . '/assets/uploads/signatures/');
define('SIG_WEB_PATH',        '../assets/uploads/signatures/');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['signature'])) {
    $file = $_FILES['signature'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_msg = '⚠️ Upload gagal. Kode error: ' . $file['error'];
    } elseif ($file['size'] > SIG_MAX_SIZE_BYTES) {
        $error_msg = '⚠️ Ukuran file melebihi 2 MB. Silakan kompres terlebih dahulu.';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
            $error_msg = '⚠️ Format file tidak didukung. Gunakan PNG, JPG, atau JPEG.';
        } elseif (!in_array(mime_content_type($file['tmp_name']), ['image/png', 'image/jpeg', 'image/jpg'], true)) {
            $error_msg = '⚠️ File bukan gambar yang valid.';
        } else {
            $imgInfo = @getimagesize($file['tmp_name']);
            if (!$imgInfo || $imgInfo[0] === 0 || $imgInfo[1] === 0) {
                $error_msg = '⚠️ Gagal membaca dimensi gambar. Pastikan file tidak rusak.';
            } else {
                $img_w = $imgInfo[0];
                $img_h = $imgInfo[1];
                $ratio = $img_w / $img_h;

                if (abs($ratio - SIG_TARGET_RATIO) >= SIG_RATIO_TOLERANCE) {
                    $ratio_fmt = number_format($ratio, 2);
                    $error_msg = "⚠️ Rasio gambar tidak valid ({$img_w}×{$img_h}px, rasio={$ratio_fmt}). "
                               . "Rasio harus 1:2.23 (toleransi ±0.05). "
                               . "Contoh dimensi valid: 446×200, 892×400, 223×100 px.";
                } else {
                    if (!is_dir(SIG_UPLOAD_DIR)) {
                        mkdir(SIG_UPLOAD_DIR, 0755, true);
                    }

                    $unique_name  = 'sig_u' . ((int)($_SESSION['user_id'] ?? 0))
                                  . '_' . date('Ymd_His')
                                  . '_' . bin2hex(random_bytes(4))
                                  . '.png';
                    $dest_path    = SIG_UPLOAD_DIR . $unique_name;
                    $web_rel_path = 'assets/uploads/signatures/' . $unique_name;

                    $converted = false;
                    if ($ext === 'png') {
                        $converted = move_uploaded_file($file['tmp_name'], $dest_path);
                    } else {
                        $srcImg = imagecreatefromjpeg($file['tmp_name']);
                        if ($srcImg) {
                            imagepng($srcImg, $dest_path, 0);
                            imagedestroy($srcImg);
                            $converted = file_exists($dest_path);
                        }
                    }

                    if (!$converted) {
                        $error_msg = '⚠️ Gagal menyimpan file. Periksa permission folder uploads.';
                    } else {
                        $user_id    = (int)($_SESSION['user_id'] ?? 0);
                        $db_success = false;

                        try {
                            $db        = getDB();
                            $stmtDeact = $db->prepare("UPDATE signatures SET is_active = 0 WHERE user_id = :uid");
                            $stmtDeact->execute([':uid' => $user_id]);

                            $stmtIns = $db->prepare("
                                INSERT INTO signatures (user_id, image_path, aspect_ratio, is_active)
                                VALUES (:uid, :img_path, :ratio, 1)
                            ");
                            $stmtIns->execute([
                                ':uid'      => $user_id,
                                ':img_path' => $web_rel_path,
                                ':ratio'    => number_format($ratio, 4, '.', ''),
                            ]);
                            $db_success = true;
                        } catch (RuntimeException $e) {
                            $_SESSION['signature_path'] = $web_rel_path;
                        }

                        $preview_path = SIG_WEB_PATH . $unique_name;
                        $success_msg  = '✅ Spesimen tanda tangan berhasil diupload'
                                      . ($db_success ? ' dan disimpan ke database.' : ' (mode offline, DB tidak tersedia).');
                    }
                }
            }
        }
    }
}

$current_sig = null;
$user_id_q   = (int)($_SESSION['user_id'] ?? 0);

try {
    $db      = getDB();
    $stmtSig = $db->prepare("
        SELECT image_path, aspect_ratio, created_at
        FROM signatures
        WHERE user_id = :uid AND is_active = 1
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmtSig->execute([':uid' => $user_id_q]);
    $current_sig = $stmtSig->fetch();
} catch (RuntimeException $e) {
    if (!empty($_SESSION['signature_path'])) {
        $current_sig = ['image_path' => $_SESSION['signature_path'], 'aspect_ratio' => '2.2300', 'created_at' => '-'];
    }
}
?>

<div class="max-w-xl mx-auto space-y-6">

    <div>
        <h2 class="text-2xl font-bold text-slate-800">🖊️ Signature Specimen</h2>
        <p class="text-sm text-slate-500 mt-1">Unggah gambar spesimen tanda tangan yang akan digunakan pada dokumen.</p>
    </div>

    <?php if ($error_msg): ?>
    <div class="alert alert-error shadow-sm text-sm"><span><?= htmlspecialchars($error_msg) ?></span></div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
    <div class="alert alert-success shadow-sm text-sm"><span><?= $success_msg ?></span></div>
    <?php endif; ?>

    <div class="card bg-white border border-slate-200 shadow-sm">
        <div class="card-body p-7 space-y-6">

            <div class="flex gap-3 rounded-xl bg-red-50 border border-red-200 p-4 text-red-800">
                <span class="text-xl shrink-0 mt-0.5">📐</span>
                <div class="text-sm leading-relaxed">
                    <p class="font-bold mb-0.5">Rasio Gambar Wajib 1 : 2.23</p>
                    <p>Contoh dimensi yang valid: <strong>446 × 200 px</strong>, 892 × 400 px, 223 × 100 px.
                    Latar belakang <strong>transparan (PNG)</strong> sangat direkomendasikan.</p>
                </div>
            </div>

            <div>
                <p class="text-sm font-semibold text-slate-700 mb-2">
                    Preview Spesimen <span class="text-xs font-normal text-slate-400 ml-1">(rasio 1 : 2.23)</span>
                </p>
                <div id="sig-preview-wrap"
                     style="aspect-ratio: 2.23 / 1;"
                     class="w-full rounded-xl bg-slate-100 border-2 border-dashed border-slate-300
                            flex flex-col items-center justify-center gap-2 overflow-hidden transition-all duration-200">

                    <?php if ($preview_path): ?>
                        <img src="<?= htmlspecialchars($preview_path) ?>" alt="Signature preview" class="w-full h-full object-contain">
                    <?php elseif ($current_sig): ?>
                        <img src="../<?= htmlspecialchars($current_sig['image_path']) ?>" alt="Signature aktif"
                             class="w-full h-full object-contain" onerror="this.style.display='none'">
                    <?php else: ?>
                        <span class="text-3xl opacity-30 select-none">🖊️</span>
                        <p id="sig-placeholder" class="text-sm text-slate-400">Gambar spesimen akan tampil di sini</p>
                    <?php endif; ?>

                    <img id="sig-img-js" src="" alt="Signature preview JS" class="hidden w-full h-full object-contain">
                </div>

                <?php if ($current_sig && !$preview_path): ?>
                <p class="text-xs text-green-600 mt-1.5">
                    ✅ Signature aktif — rasio <?= htmlspecialchars($current_sig['aspect_ratio']) ?>
                    | Upload: <?= htmlspecialchars($current_sig['created_at']) ?>
                </p>
                <?php else: ?>
                <p class="text-xs text-slate-400 mt-1.5">Area di atas mencerminkan proporsi nyata tanda tangan pada dokumen.</p>
                <?php endif; ?>
            </div>

            <form method="POST" action="index.php?page=set_signature" enctype="multipart/form-data">

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Pilih File Gambar <span class="text-red-500">*</span>
                    </label>
                    <input type="file" id="sig-file" name="signature" accept=".png,.jpg,.jpeg"
                           class="file-input file-input-bordered w-full focus:file-input-primary"
                           onchange="previewSig(event)" required>
                    <p class="text-xs text-slate-400 mt-1.5">
                        Format: <strong>PNG</strong> (direkomendasikan) atau JPG.
                        Ukuran maksimal: <strong>2 MB</strong>.
                        Rasio wajib <strong>1:2.23</strong> (±0.05).
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-3 text-center text-xs text-slate-500 mb-5">
                    <?php foreach ([['223','100','Minimal'],['446','200','Standar'],['892','400','HD']] as [$w,$h,$lbl]): ?>
                    <div class="rounded-lg bg-slate-50 border border-slate-200 py-2.5 px-2">
                        <p class="font-mono font-bold text-slate-700 text-sm"><?= $w ?> × <?= $h ?></p>
                        <p class="text-[10px] uppercase tracking-wide text-slate-400 mt-0.5"><?= $lbl ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary w-full text-base gap-2">💾 Upload Spesimen</button>

            </form>
        </div>
    </div>

</div>

<script>
function previewSig(event) {
    const file = event.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        alert('Ukuran file melebihi 2 MB. Silakan pilih file yang lebih kecil.');
        event.target.value = '';
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        const jsImg = document.getElementById('sig-img-js');
        const wrap  = document.getElementById('sig-preview-wrap');
        [...wrap.children].forEach(el => el.classList.add('hidden'));
        jsImg.src = e.target.result;
        jsImg.classList.remove('hidden');
        wrap.classList.remove('border-dashed', 'border-slate-300', 'bg-slate-100');
        wrap.classList.add('border-solid', 'border-blue-300', 'bg-white');
        const tmpImg = new Image();
        tmpImg.onload = function() {
            const ratio = (tmpImg.width / tmpImg.height).toFixed(2);
            const ok    = Math.abs(ratio - 2.23) < 0.05;
            const info  = document.querySelector('.text-xs.text-slate-400.mt-1\\.5');
            if (info) {
                info.textContent = `${tmpImg.width} × ${tmpImg.height} px | rasio ${ratio} ${ok ? '✅ Valid' : '⚠️ Rasio tidak tepat!'}`;
                info.className   = `text-xs mt-1.5 ${ok ? 'text-green-600' : 'text-red-500'}`;
            }
        };
        tmpImg.src = e.target.result;
    };
    reader.readAsDataURL(file);
}
</script>
