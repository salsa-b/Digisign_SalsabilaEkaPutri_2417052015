<?php
require_once __DIR__ . '/../../config/database.php';
$db = getDB();

$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];
$stmt = $db->query("SELECT COUNT(*) as pending FROM digital_id_requests WHERE status = 'pending'");
$pending_count = $stmt->fetch()['pending'];
$stmt = $db->query("SELECT COUNT(*) as approved FROM digital_id_requests WHERE status = 'approved'");
$approved_count = $stmt->fetch()['approved'];
$stmt = $db->query("SELECT COUNT(*) as rejected FROM digital_id_requests WHERE status = 'rejected'");
$rejected_count = $stmt->fetch()['rejected'];

$stmt = $db->prepare("SELECT r.id, r.role, r.created_at, u.username, u.email FROM digital_id_requests r JOIN users u ON u.id = r.user_id WHERE r.status = 'pending' ORDER BY r.created_at DESC");
$stmt->execute();
$requests = $stmt->fetchAll();
?>

<div class="max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold mb-4">Admin Dashboard</h1>
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="p-4 bg-blue-50 rounded"><div class="text-sm">Total User</div><div class="text-2xl font-bold"><?= $total_users ?></div></div>
        <div class="p-4 bg-yellow-50 rounded"><div class="text-sm">Pending</div><div class="text-2xl font-bold"><?= $pending_count ?></div></div>
        <div class="p-4 bg-green-50 rounded"><div class="text-sm">Approved</div><div class="text-2xl font-bold"><?= $approved_count ?></div></div>
        <div class="p-4 bg-red-50 rounded"><div class="text-sm">Rejected</div><div class="text-2xl font-bold"><?= $rejected_count ?></div></div>
    </div>

    <div class="bg-white rounded shadow p-6">
        <h2 class="text-lg font-bold mb-4">Permintaan Digital ID</h2>
        <?php if (empty($requests)): ?>
            <p class="text-gray-500">Tidak ada permintaan pending.</p>
        <?php else: ?>
            <table class="w-full">
                <thead><tr class="border-b"><th class="text-left p-2">User</th><th>Role</th><th>Tanggal</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($requests as $req): ?>
                    <tr class="border-b">
                        <td class="p-2"><?= htmlspecialchars($req['username']) ?></td>
                        <td><?= htmlspecialchars($req['role']) ?></td>
                        <td><?= date('d M Y H:i', strtotime($req['created_at'])) ?></td>
                        <td>
                            <form action="index.php?page=admin/approve" method="POST" class="inline">
                                <input type="hidden" name="req_id" value="<?= $req['id'] ?>">
                                <button type="submit" name="action" value="approve" class="bg-green-500 text-white px-3 py-1 rounded text-sm">Approve</button>
                                <button type="submit" name="action" value="reject" class="bg-red-500 text-white px-3 py-1 rounded text-sm">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>