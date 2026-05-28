<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

$db = getDB();

$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as pending FROM digital_id_requests WHERE status = 'pending'");
$pending_count = $stmt->fetch()['pending'];

$stmt = $db->query("SELECT COUNT(*) as approved FROM digital_id_requests WHERE status = 'approved'");
$approved_count = $stmt->fetch()['approved'];

$stmt = $db->query("SELECT COUNT(*) as rejected FROM digital_id_requests WHERE status = 'rejected'");
$rejected_count = $stmt->fetch()['rejected'];

$stmt = $db->prepare("
    SELECT r.id, r.role, r.created_at, u.username, u.email 
    FROM digital_id_requests r 
    JOIN users u ON u.id = r.user_id 
    WHERE r.status = 'pending' 
    ORDER BY r.created_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll();
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-0">
    <!-- Header -->
    <h1 class="text-3xl font-bold text-slate-800 mb-8">Admin Dashboard</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <p class="text-sm text-slate-500 font-medium mb-1">Total User</p>
            <p class="text-3xl font-bold text-blue-600"><?= $total_users ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <p class="text-sm text-slate-500 font-medium mb-1">Pending</p>
            <p class="text-3xl font-bold text-yellow-600"><?= $pending_count ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <p class="text-sm text-slate-500 font-medium mb-1">Approved</p>
            <p class="text-3xl font-bold text-green-600"><?= $approved_count ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <p class="text-sm text-slate-500 font-medium mb-1">Rejected</p>
            <p class="text-3xl font-bold text-red-600"><?= $rejected_count ?></p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-xl font-bold text-slate-800">Permintaan Digital ID</h2>
        </div>
        
        <?php if (empty($requests)): ?>
            <div class="p-12 text-center text-slate-400">
                <p class="text-lg">Tidak ada permintaan pending saat ini.</p>
            </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 text-slate-500 text-sm uppercase tracking-wide">
                    <tr>
                        <th class="p-5 border-b border-slate-100 font-semibold">User</th>
                        <th class="p-5 border-b border-slate-100 font-semibold">Role</th>
                        <th class="p-5 border-b border-slate-100 font-semibold">Tanggal Request</th>
                        <th class="p-5 border-b border-slate-100 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($requests as $req): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-5 align-middle">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs">
                                    <?= strtoupper(substr($req['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-700"><?= htmlspecialchars($req['username']) ?></p>
                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($req['email']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="p-5 align-middle">
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase bg-purple-100 text-purple-700">
                                <?= htmlspecialchars($req['role']) ?>
                            </span>
                        </td>
                        <td class="p-5 align-middle text-sm text-slate-600">
                            <?= date('d M Y, H:i', strtotime($req['created_at'])) ?>
                        </td>
                        <td class="p-5 align-middle">
                            <div class="flex items-center justify-center gap-2">
                                <form action="index.php?page=admin/approve" method="POST" class="inline-block">
                                    <input type="hidden" name="req_id" value="<?= $req['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="px-4 py-1.5 bg-green-500 hover:bg-green-600 text-white text-sm font-semibold rounded-lg transition shadow-sm">
                                        ✔ Approve
                                    </button>
                                    <button type="submit" name="action" value="reject" class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-lg transition shadow-sm">
                                        ✖ Reject
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>