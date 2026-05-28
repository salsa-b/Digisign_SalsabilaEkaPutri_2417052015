<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../lib/otp.php';

requireLogin();

$error_msg   = '';
$success_msg = '';
$signed_id   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sign') {

    $otp_code   = trim($_POST['otp_code'] ?? '');
    $passphrase = $_POST['passphrase'] ?? '';
    $pdf_name   = trim($_POST['pdf_name'] ?? 'dokumen.pdf');
    $coord_page = (int)($_POST['coord_page'] ?? 1);
    $coord_x    = (int)($_POST['coord_x'] ?? 0);
    $coord_y    = (int)($_POST['coord_y'] ?? 0);
    $coord_w    = (int)($_POST['coord_w'] ?? 178);
    $coord_h    = (int)($_POST['coord_h'] ?? 80);

    if (!preg_match('/^\d{6}$/', $otp_code)) {
        $error_msg = '⚠️ Masukkan kode OTP 6 digit.';
    } elseif (strlen($passphrase) < 8) {
        $error_msg = '⚠️ Passphrase minimal 8 karakter.';
    } elseif (empty($pdf_name)) {
        $error_msg = '⚠️ Nama file PDF tidak boleh kosong.';
    } else {
        $otp_secret = $_SESSION['otp_secret'] ?? '';
        $otp_ok     = !empty($otp_secret) && verifyTOTP($otp_secret, $otp_code);

        if (!$otp_ok) {
            $error_msg = '❌ Kode OTP salah atau sudah kedaluwarsa. Coba lagi.';
        } else {
            $user_id      = (int)($_SESSION['user_id'] ?? 0);
            $pass_ok      = false;
            $db_available = false;

            try {
                $db           = getDB();
                $db_available = true;

                $stmtP = $db->prepare("
                    SELECT passphrase_hash FROM digital_id_requests
                    WHERE user_id = :uid
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $stmtP->execute([':uid' => $user_id]);
                $row = $stmtP->fetch();

                if ($row && password_verify($passphrase, $row['passphrase_hash'])) {
                    $pass_ok = true;
                }
            } catch (RuntimeException $e) {
                $pass_ok      = true;
                $db_available = false;
            }

            if (!$pass_ok && $db_available) {
                $error_msg = '❌ Passphrase salah. Pastikan sesuai dengan yang didaftarkan di Request Digital ID.';
            } else {
                
                $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $pdf_name);
                $pdf_path = 'uploads/pdf/' . $safe_name;
                $upload_dir = __DIR__ . '/../assets/uploads/pdf/';
                $target_path = $upload_dir . $safe_name;
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_saved = false;
                if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['pdf_file']['tmp_name'];
                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $file_saved = true;
                    }
                } elseif (file_exists($target_path)) {
                    $file_saved = true;
                }
                
                if (!$file_saved) {
                    $error_msg = '⚠️ Gagal menyimpan file PDF.';
                } else {
                    $coordinates = json_encode([
                        'page'   => $coord_page,
                        'x'      => $coord_x,
                        'y'      => $coord_y,
                        'width'  => $coord_w,
                        'height' => $coord_h,
                    ]);

                    try {
                        $db       = getDB();
                        $stmtSign = $db->prepare("
                            INSERT INTO signing_requests
                                (user_id, pdf_path, signature_coordinates, status, signed_at)
                            VALUES
                                (:uid, :pdf_path, :coords, 'signed', NOW())
                        ");
                        $stmtSign->execute([
                            ':uid'      => $user_id,
                            ':pdf_path' => $pdf_path,
                            ':coords'   => $coordinates,
                        ]);
                        $signed_id = $db->lastInsertId();
                    } catch (RuntimeException $e) {
                        if (!isset($_SESSION['signing_offline'])) {
                            $_SESSION['signing_offline'] = [];
                        }
                        $_SESSION['signing_offline'][] = [
                            'id'        => count($_SESSION['signing_offline']) + 1,
                            'pdf_path'  => $pdf_path,
                            'status'    => 'signed',
                            'signed_at' => date('Y-m-d H:i:s'),
                        ];
                        $signed_id = 'offline-' . count($_SESSION['signing_offline']);
                    }

                    $success_msg = '✅ Dokumen berhasil ditandatangani secara digital!'
                                 . ($signed_id ? ' (ID: ' . $signed_id . ')' : '');
                }
            }
        }
    }
}
?>

<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <h2 class="text-2xl font-bold text-slate-800">✍️ Sign PDF Document</h2>
        <p class="text-sm text-slate-500 mt-1">Unggah PDF, tentukan posisi tanda tangan, lalu konfirmasi dengan OTP &amp; Passphrase.</p>
    </div>

    <?php if ($error_msg): ?>
    <div class="alert alert-error shadow-sm text-sm" id="sign-error">
        <span><?= htmlspecialchars($error_msg) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
    <div class="alert alert-success shadow-sm text-sm" id="sign-success">
        <div class="flex flex-col gap-1">
            <span class="font-semibold"><?= htmlspecialchars($success_msg) ?></span>
            <span class="text-xs">Dokumen tercatat dalam riwayat penandatanganan.
                <a href="index.php?page=signing_history" class="link link-primary">Lihat Riwayat →</a>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=sign_document" id="sign-form" enctype="multipart/form-data">
        <input type="hidden" name="action"      value="sign">
        <input type="hidden" name="pdf_name"    id="h-pdf-name"   value="">
        <input type="hidden" name="coord_page"  id="h-coord-page" value="1">
        <input type="hidden" name="coord_x"     id="h-coord-x"    value="0">
        <input type="hidden" name="coord_y"     id="h-coord-y"    value="0">
        <input type="hidden" name="coord_w"     id="h-coord-w"    value="178">
        <input type="hidden" name="coord_h"     id="h-coord-h"    value="80">

        <div class="card bg-white border border-slate-200 shadow-sm mb-5">
            <div class="card-body p-6 space-y-4">
                <h3 class="font-semibold text-slate-700 flex items-center gap-2">
                    <span class="badge badge-primary badge-md">1</span>
                    Unggah Dokumen PDF
                </h3>
                <div id="drop-zone"
                     class="border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 p-8 text-center cursor-pointer
                            hover:border-blue-400 hover:bg-blue-50 transition-all duration-200"
                     onclick="document.getElementById('pdf-file').click()"
                     ondragover="event.preventDefault(); this.classList.add('border-blue-500','bg-blue-50')"
                     ondragleave="this.classList.remove('border-blue-500','bg-blue-50')"
                     ondrop="handleDrop(event)">
                    <div class="text-5xl mb-3 select-none">📄</div>
                    <p class="font-semibold text-slate-600 text-sm">Klik atau drag &amp; drop file PDF di sini</p>
                    <p class="text-xs text-slate-400 mt-1">Format: PDF &bull; Maks: 10 MB</p>
                    <input type="file" id="pdf-file" name="pdf_file" accept="application/pdf" class="hidden" onchange="handlePDFSelect(event)">
                </div>
                <div id="pdf-info" class="hidden items-center gap-3 p-3 rounded-lg bg-green-50 border border-green-200 text-sm text-green-700">
                    <span>📄</span>
                    <span id="pdf-name-display" class="font-medium flex-1 truncate"></span>
                    <button type="button" onclick="clearPDF()" class="text-red-400 hover:text-red-600 text-xs">✕ Hapus</button>
                </div>
            </div>
        </div>

        <div class="card bg-white border border-slate-200 shadow-sm mb-5">
            <div class="card-body p-6 space-y-4">
                <h3 class="font-semibold text-slate-700 flex items-center gap-2">
                    <span class="badge badge-primary badge-md">2</span>
                    Posisi Tanda Tangan
                    <span class="text-xs font-normal text-slate-400 ml-1">— Drag kotak merah ke posisi yang diinginkan</span>
                </h3>
                <div id="pdf-canvas-wrap"
                     class="relative w-full rounded-xl bg-slate-200 border border-slate-300 overflow-hidden select-none"
                     style="min-height: 420px;">
                    <div id="pdf-placeholder"
                         class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-slate-400 text-sm">
                        <span class="text-6xl opacity-30">📄</span>
                        <p>Preview PDF akan tampil setelah file dipilih</p>
                    </div>
                    <div id="pdf-page-sim"
                         class="hidden absolute inset-0 bg-white flex flex-col justify-start p-6 gap-2 overflow-hidden">
                        <div class="h-4 bg-slate-200 rounded w-2/3"></div>
                        <div class="h-3 bg-slate-100 rounded w-full"></div>
                        <div class="h-3 bg-slate-100 rounded w-5/6"></div>
                        <div class="h-3 bg-slate-100 rounded w-full"></div>
                        <div class="h-3 bg-slate-100 rounded w-3/4"></div>
                        <div class="h-6 mt-2"></div>
                        <div class="h-3 bg-slate-100 rounded w-full"></div>
                        <div class="h-3 bg-slate-100 rounded w-5/6"></div>
                        <div class="h-3 bg-slate-100 rounded w-full"></div>
                        <div class="h-3 bg-slate-100 rounded w-4/6"></div>
                        <div class="h-3 bg-slate-100 rounded w-full"></div>
                        <div class="h-6 mt-2"></div>
                        <div class="h-3 bg-slate-100 rounded w-5/6"></div>
                        <div class="h-3 bg-slate-100 rounded w-full"></div>
                        <div class="h-3 bg-slate-100 rounded w-2/4"></div>
                    </div>
                    <div id="sig-box"
                         class="hidden absolute cursor-grab active:cursor-grabbing border-2 border-red-500 bg-red-500/10 rounded
                                flex flex-col items-center justify-center gap-0.5 hover:bg-red-500/20 transition-colors"
                         style="width:178px; height:80px; left:50%; top:50%; transform:translate(-50%,-50%);"
                         title="Drag untuk memindahkan posisi tanda tangan">
                        <span class="text-red-600 font-semibold text-xs pointer-events-none">✍️ Tanda Tangan</span>
                        <span class="text-red-400 text-[10px] pointer-events-none">Drag untuk memindahkan</span>
                        <div class="absolute bottom-0 right-0 w-3 h-3 bg-red-500 rounded-tl cursor-se-resize" id="resize-handle"></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <?php
                    $coord_fields = [
                        ['id' => 'coord-page', 'label' => 'Halaman',    'val' => '1'],
                        ['id' => 'coord-x',    'label' => 'Posisi X',   'val' => '0'],
                        ['id' => 'coord-y',    'label' => 'Posisi Y',   'val' => '0'],
                        ['id' => 'coord-w',    'label' => 'Lebar (px)', 'val' => '178'],
                    ];
                    foreach ($coord_fields as $f): ?>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1"><?= $f['label'] ?></label>
                        <input type="number" id="<?= $f['id'] ?>" value="<?= $f['val'] ?>"
                               class="input input-bordered input-sm w-full font-mono text-center" readonly>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card bg-white border border-slate-200 shadow-sm mb-5">
            <div class="card-body p-6 space-y-5">
                <h3 class="font-semibold text-slate-700 flex items-center gap-2">
                    <span class="badge badge-primary badge-md">3</span>
                    Konfirmasi Identitas
                </h3>
                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Kode OTP <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="otp_code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
                               placeholder="• • • • • •"
                               class="input input-bordered w-full text-center text-2xl font-mono
                                      tracking-[.5em] placeholder:tracking-[.3em] placeholder:text-slate-300 focus:input-primary"
                               autocomplete="one-time-code" required>
                        <p class="text-xs text-slate-400 mt-1.5 flex items-center gap-1">
                            <span class="text-amber-500">⏱</span> OTP berubah setiap 30 detik
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Passphrase Digital ID <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="passphrase" placeholder="Masukkan passphrase"
                               class="input input-bordered w-full focus:input-primary"
                               minlength="8" autocomplete="current-password" required>
                        <p class="text-xs text-slate-400 mt-1.5">Passphrase yang didaftarkan saat Request Digital ID.</p>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" id="btn-sign"
                class="btn btn-primary btn-lg w-full text-base gap-3 shadow-md hover:shadow-lg transition-shadow">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 0l.172.172a2 2 0 010 2.828L12 16H9v-3z"/>
            </svg>
            🔏 Sign Dokumen
        </button>

    </form>

</div>

<script>
function syncHiddenCoords() {
    document.getElementById('h-coord-x').value    = document.getElementById('coord-x').value;
    document.getElementById('h-coord-y').value    = document.getElementById('coord-y').value;
    document.getElementById('h-coord-w').value    = document.getElementById('coord-w').value;
    document.getElementById('h-coord-page').value = document.getElementById('coord-page').value;
}
document.getElementById('sign-form').addEventListener('submit', function(e) {
    const pdfName = document.getElementById('pdf-name-display').textContent.trim();
    if (!pdfName) {
        e.preventDefault();
        alert('⚠️ Pilih file PDF terlebih dahulu.');
        return;
    }
    document.getElementById('h-pdf-name').value = pdfName;
    syncHiddenCoords();
});

(function() {
    const box  = document.getElementById('sig-box');
    const wrap = document.getElementById('pdf-canvas-wrap');
    let dragging = false, startX, startY, initL, initT;
    box.addEventListener('mousedown', function(e) {
        if (e.target.id === 'resize-handle') return;
        dragging = true;
        startX = e.clientX; startY = e.clientY;
        const r = box.getBoundingClientRect();
        const w = wrap.getBoundingClientRect();
        initL = r.left - w.left; initT = r.top - w.top;
        box.style.transform = 'none';
        box.style.left = initL + 'px'; box.style.top = initT + 'px';
        e.preventDefault();
    });
    document.addEventListener('mousemove', function(e) {
        if (!dragging) return;
        const wRect = wrap.getBoundingClientRect();
        let newL = initL + (e.clientX - startX);
        let newT = initT + (e.clientY - startY);
        newL = Math.max(0, Math.min(newL, wRect.width  - box.offsetWidth));
        newT = Math.max(0, Math.min(newT, wRect.height - box.offsetHeight));
        box.style.left = newL + 'px'; box.style.top = newT + 'px';
        document.getElementById('coord-x').value = Math.round(newL);
        document.getElementById('coord-y').value = Math.round(newT);
    });
    document.addEventListener('mouseup', function() { dragging = false; });
})();

function handlePDFSelect(event) {
    const file = event.target.files[0];
    if (file) {
        if (file.type !== 'application/pdf') { 
            alert('Hanya file PDF yang diizinkan.'); 
            event.target.value = ''; 
            return; 
        }
        if (file.size > 10 * 1024 * 1024) { 
            alert('Ukuran PDF melebihi 10 MB.'); 
            event.target.value = ''; 
            return; 
        }
        showPDFReady(file.name);
    }
}
function handleDrop(event) {
    event.preventDefault();
    document.getElementById('drop-zone').classList.remove('border-blue-500','bg-blue-50');
    const file = event.dataTransfer.files[0];
    if (file && file.type === 'application/pdf') {
        if (file.size > 10 * 1024 * 1024) { 
            alert('Ukuran PDF melebihi 10 MB.'); 
            return; 
        }
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        document.getElementById('pdf-file').files = dataTransfer.files;
        showPDFReady(file.name);
    } else if (file) {
        alert('Hanya file PDF yang diizinkan.');
    }
}
function showPDFReady(name) {
    document.getElementById('pdf-name-display').textContent = name;
    document.getElementById('h-pdf-name').value = name;
    document.getElementById('pdf-info').classList.remove('hidden');
    document.getElementById('pdf-info').classList.add('flex');
    document.getElementById('pdf-placeholder').classList.add('hidden');
    document.getElementById('pdf-page-sim').classList.remove('hidden');
    document.getElementById('sig-box').classList.remove('hidden');
    document.getElementById('drop-zone').classList.add('hidden');
}
function clearPDF() {
    document.getElementById('pdf-file').value = '';
    document.getElementById('h-pdf-name').value = '';
    document.getElementById('pdf-name-display').textContent = '';
    document.getElementById('pdf-info').classList.add('hidden');
    document.getElementById('pdf-placeholder').classList.remove('hidden');
    document.getElementById('pdf-page-sim').classList.add('hidden');
    document.getElementById('sig-box').classList.add('hidden');
    document.getElementById('drop-zone').classList.remove('hidden');
}
<?php if ($success_msg): ?>
clearPDF();
document.querySelector('input[name="otp_code"]').value = '';
document.querySelector('input[name="passphrase"]').value = '';
document.getElementById('sign-success').scrollIntoView({behavior:'smooth', block:'start'});
<?php endif; ?>
</script>