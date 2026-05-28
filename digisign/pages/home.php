<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();
requireOtpVerified();

$db = getDB();
$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM digital_id_requests WHERE user_id = ? AND status = 'approved'");
$stmt->execute([$user_id]);
$approved = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM signing_requests WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_signed = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM signatures WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_signatures = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM digital_id_requests WHERE user_id = ? GROUP BY status");
$stmt->execute([$user_id]);
$request_status = $stmt->fetchAll();

$pending = 0;
foreach ($request_status as $s) {
    if ($s['status'] === 'pending') $pending = $s['count'];
}
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-800">Dashboard</h1>
        <p class="text-slate-600 mt-1">Selamat datang kembali, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div><p class="text-blue-100 text-sm">Digital ID Approved</p><p class="text-4xl font-bold mt-2"><?= $approved ?></p></div>
                <div class="bg-white/20 p-3 rounded-xl">🛡️</div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div><p class="text-purple-100 text-sm">Dokumen Ditanda</p><p class="text-4xl font-bold mt-2"><?= $total_signed ?></p></div>
                <div class="bg-white/20 p-3 rounded-xl">📄</div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div><p class="text-green-100 text-sm">Spesimen TTD</p><p class="text-4xl font-bold mt-2"><?= $total_signatures ?></p></div>
                <div class="bg-white/20 p-3 rounded-xl">✍️</div>
            </div>
        </div>
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div><p class="text-yellow-100 text-sm">Menunggu Approval</p><p class="text-4xl font-bold mt-2"><?= $pending ?></p></div>
                <div class="bg-white/20 p-3 rounded-xl"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">📖 Panduan Memulai</h2>
            <div class="space-y-3">
                <div class="flex items-start gap-3 p-3 bg-blue-50 rounded-lg">
                    <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</span>
                    <div><p class="font-semibold text-slate-800">Setup OTP</p><a href="index.php?page=setup_otp" class="text-blue-600 text-sm hover:underline">Setup Sekarang →</a></div>
                </div>
                <div class="flex items-start gap-3 p-3 bg-purple-50 rounded-lg">
                    <span class="bg-purple-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</span>
                    <div><p class="font-semibold text-slate-800">Request Digital ID</p><a href="index.php?page=request_id" class="text-purple-600 text-sm hover:underline">Request ID →</a></div>
                </div>
                <div class="flex items-start gap-3 p-3 bg-green-50 rounded-lg">
                    <span class="bg-green-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</span>
                    <div><p class="font-semibold text-slate-800">Upload Signature</p><a href="index.php?page=set_signature" class="text-green-600 text-sm hover:underline">Upload →</a></div>
                </div>
                <div class="flex items-start gap-3 p-3 bg-orange-50 rounded-lg">
                    <span class="bg-orange-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">4</span>
                    <div><p class="font-semibold text-slate-800">Sign PDF</p><a href="index.php?page=sign_document" class="text-orange-600 text-sm hover:underline">Sign PDF →</a></div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">📋 Status Digital ID</h2>
            <?php
            $stmt = $db->prepare("SELECT * FROM digital_id_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmt->execute([$user_id]);
            $requests = $stmt->fetchAll();
            ?>
            <?php if (empty($requests)): ?>
            <div class="text-center py-12 text-slate-500">
                <p class="mb-4">Belum ada permintaan Digital ID</p>
                <a href="index.php?page=request_id" class="inline-block bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700">Request Digital ID</a>
            </div>
            <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($requests as $req): ?>
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                    <div>
                        <p class="font-semibold"><?= htmlspecialchars($req['role']) ?></p>
                        <p class="text-sm text-slate-500"><?= date('d M Y H:i', strtotime($req['created_at'])) ?></p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $req['status']==='approved'?'bg-green-100 text-green-700':'' ?> <?= $req['status']==='pending'?'bg-yellow-100 text-yellow-700':'' ?> <?= $req['status']==='rejected'?'bg-red-100 text-red-700':'' ?>">
                        <?= ucfirst($req['status']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>