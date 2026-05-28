<?php
$filter_status = trim($_GET['status'] ?? '');
$filter_search = trim($_GET['search'] ?? '');
$filter_date   = trim($_GET['date'] ?? '');

$valid_statuses = ['signed', 'pending', 'failed'];
if (!in_array($filter_status, $valid_statuses, true)) {
    $filter_status = '';
}

$history      = [];
$total_count  = 0;
$db_available = false;
$user_id_q    = (int)($_SESSION['user_id'] ?? 0);
$counts_db    = ['signed' => 0, 'pending' => 0, 'failed' => 0];

try {
    $db           = getDB();
    $db_available = true;

    $where  = ['sr.user_id = :uid'];
    $params = [':uid' => $user_id_q];

    if ($filter_status !== '') {
        $where[]           = 'sr.status = :status';
        $params[':status'] = $filter_status;
    }
    if ($filter_search !== '') {
        $where[]           = 'sr.pdf_path LIKE :search';
        $params[':search'] = '%' . $filter_search . '%';
    }
    if ($filter_date !== '') {
        $where[]          = 'DATE(sr.created_at) = :fdate';
        $params[':fdate'] = $filter_date;
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    $stmtCnt = $db->prepare("SELECT status, COUNT(*) AS cnt FROM signing_requests WHERE user_id = :uid GROUP BY status");
    $stmtCnt->execute([':uid' => $user_id_q]);
    foreach ($stmtCnt->fetchAll() as $c) {
        if (isset($counts_db[$c['status']])) {
            $counts_db[$c['status']] = (int)$c['cnt'];
        }
    }

    $stmtH = $db->prepare("
        SELECT sr.id, sr.pdf_path, sr.status,
               DATE_FORMAT(sr.created_at, '%Y-%m-%d %H:%i') AS date_fmt,
               sr.signed_at, sr.signature_coordinates
        FROM signing_requests sr
        $whereSQL
        ORDER BY sr.created_at DESC
        LIMIT 50
    ");
    $stmtH->execute($params);

    foreach ($stmtH->fetchAll() as $i => $r) {
        $file_display = basename(str_replace('_', ' ', $r['pdf_path']));
        $history[] = [
            'no'     => $i + 1,
            'id'     => $r['id'],
            'file'   => $file_display ?: $r['pdf_path'],
            'date'   => $r['date_fmt'],
            'status' => $r['status'],
            'hash'   => $r['status'] === 'signed' ? substr(md5($r['pdf_path'] . $r['id']), 0, 6) : null,
        ];
    }
    $total_count = count($history);

} catch (RuntimeException $e) {
    $db_available = false;
}

if (!$db_available) {
    $dummy = [
        ['no'=>1,'id'=>'D1','file'=>'Surat Keterangan Aktif.pdf',    'date'=>'2026-05-28 09:14','status'=>'signed', 'hash'=>'a1b2c3'],
        ['no'=>2,'id'=>'D2','file'=>'Proposal Tugas Akhir.pdf',       'date'=>'2026-05-27 14:32','status'=>'signed', 'hash'=>'d4e5f6'],
        ['no'=>3,'id'=>'D3','file'=>'Berita Acara Ujian.pdf',          'date'=>'2026-05-26 11:00','status'=>'failed', 'hash'=>null],
        ['no'=>4,'id'=>'D4','file'=>'Surat Izin Penelitian.pdf',       'date'=>'2026-05-25 16:45','status'=>'signed', 'hash'=>'g7h8i9'],
        ['no'=>5,'id'=>'D5','file'=>'Laporan Kemajuan Q1.pdf',         'date'=>'2026-05-24 08:30','status'=>'pending','hash'=>null],
        ['no'=>6,'id'=>'D6','file'=>'Dokumen Perjanjian Kerjasama.pdf','date'=>'2026-05-22 13:20','status'=>'signed', 'hash'=>'j0k1l2'],
    ];

    foreach ($_SESSION['signing_offline'] ?? [] as $i => $o) {
        $dummy[] = [
            'no'     => count($dummy) + 1,
            'id'     => $o['id'],
            'file'   => basename($o['pdf_path']),
            'date'   => $o['signed_at'],
            'status' => $o['status'],
            'hash'   => substr(md5($o['pdf_path']), 0, 6),
        ];
    }

    $counts_db = ['signed' => 0, 'pending' => 0, 'failed' => 0];
    foreach ($dummy as $row) {
        if (isset($counts_db[$row['status']])) $counts_db[$row['status']]++;
    }

    $history = [];
    $no      = 1;
    foreach ($dummy as $row) {
        if ($filter_status && $row['status'] !== $filter_status) continue;
        if ($filter_search && stripos($row['file'], $filter_search) === false) continue;
        $row['no'] = $no++;
        $history[] = $row;
    }
    $total_count = count($history);
}

$badge_map = [
    'signed'  => ['class' => 'badge-success', 'icon' => '✔', 'label' => 'Signed'],
    'pending' => ['class' => 'badge-warning', 'icon' => '⏳', 'label' => 'Pending'],
    'failed'  => ['class' => 'badge-error',   'icon' => '✖', 'label' => 'Failed'],
];
?>

<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">📋 Signing History</h2>
            <p class="text-sm text-slate-500 mt-1">Riwayat seluruh permintaan penandatanganan dokumen PDF Anda.</p>
        </div>
        <?php if (!$db_available): ?>
        <div class="badge badge-warning badge-sm shrink-0 mt-2">⚠️ Mode Offline</div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <?php
        $stat_cfg = [
            ['key' => 'signed',  'label' => 'Berhasil', 'icon' => '✔', 'color' => 'text-green-600', 'bg' => 'bg-green-50 border-green-200'],
            ['key' => 'pending', 'label' => 'Menunggu', 'icon' => '⏳', 'color' => 'text-amber-600', 'bg' => 'bg-amber-50 border-amber-200'],
            ['key' => 'failed',  'label' => 'Gagal',    'icon' => '✖', 'color' => 'text-red-600',   'bg' => 'bg-red-50 border-red-200'],
        ];
        foreach ($stat_cfg as $sc): ?>
        <a href="index.php?page=signing_history&status=<?= $sc['key'] ?><?= $filter_search ? '&search=' . urlencode($filter_search) : '' ?>"
           class="rounded-xl border <?= $sc['bg'] ?> p-4 text-center hover:opacity-80 transition-opacity
                  <?= $filter_status === $sc['key'] ? 'ring-2 ring-offset-1 ring-current' : '' ?>">
            <p class="text-2xl font-bold <?= $sc['color'] ?>"><?= $counts_db[$sc['key']] ?></p>
            <p class="text-xs text-slate-500 mt-0.5"><?= $sc['icon'] ?> <?= $sc['label'] ?></p>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="card bg-white border border-slate-200 shadow-sm">
        <div class="card-body p-4">
            <form method="GET" action="index.php" class="flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="page" value="signing_history">
                <input type="text" name="search" value="<?= htmlspecialchars($filter_search) ?>"
                       placeholder="🔍 Cari nama file dokumen..."
                       class="input input-bordered input-sm flex-1 focus:input-primary">
                <select name="status" class="select select-bordered select-sm w-full sm:w-40 focus:select-primary">
                    <option value="">Semua Status</option>
                    <option value="signed"  <?= $filter_status === 'signed'  ? 'selected' : '' ?>>✔ Signed</option>
                    <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                    <option value="failed"  <?= $filter_status === 'failed'  ? 'selected' : '' ?>>✖ Failed</option>
                </select>
                <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>"
                       class="input input-bordered input-sm w-full sm:w-40">
                <button type="submit" class="btn btn-sm btn-primary gap-1">🔍 Filter</button>
                <a href="index.php?page=signing_history" class="btn btn-sm btn-ghost text-slate-400">Reset</a>
            </form>
        </div>
    </div>

    <div class="card bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 uppercase text-xs tracking-wide">
                    <tr>
                        <th class="w-10 text-center">No</th>
                        <th>Nama File</th>
                        <th class="w-44">Tanggal</th>
                        <th class="w-28 text-center">Status</th>
                        <th class="w-36 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-12 text-slate-400">
                            <span class="text-3xl block mb-2">📭</span>
                            <?= $filter_status || $filter_search ? 'Tidak ada hasil yang cocok dengan filter.' : 'Belum ada riwayat penandatanganan.' ?>
                            <?php if ($filter_status || $filter_search): ?>
                            <br><a href="index.php?page=signing_history" class="text-xs text-blue-500 link mt-1 inline-block">Reset filter</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($history as $row):
                        $b = $badge_map[$row['status']] ?? $badge_map['pending']; ?>
                    <tr class="hover">
                        <td class="text-center text-slate-400 font-mono text-xs"><?= (int)$row['no'] ?></td>
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="text-red-400 shrink-0">📄</span>
                                <span class="font-medium text-slate-700 truncate max-w-[240px]"
                                      title="<?= htmlspecialchars($row['file']) ?>">
                                    <?= htmlspecialchars($row['file']) ?>
                                </span>
                            </div>
                            <?php if (!empty($row['hash'])): ?>
                            <p class="text-[10px] text-slate-400 font-mono ml-6 mt-0.5">Hash: <?= htmlspecialchars($row['hash']) ?>…</p>
                            <?php endif; ?>
                        </td>
                        <td class="text-slate-500 text-xs whitespace-nowrap"><?= htmlspecialchars($row['date']) ?></td>
                        <td class="text-center">
                            <span class="badge <?= $b['class'] ?> badge-sm gap-1"><?= $b['icon'] ?> <?= $b['label'] ?></span>
                        </td>
                        <td class="text-center">
                            <div class="flex justify-center gap-1">
                                <?php if ($row['status'] === 'signed'): ?>
                                <a href="../download.php?id=<?= $row['id'] ?>"
                                   class="btn btn-xs btn-outline btn-primary gap-1" title="Unduh dokumen bertanda tangan">
                                    ⬇️ Unduh
                                </a>
                                <button class="btn btn-xs btn-ghost text-slate-400" title="Lihat detail koordinat"
                                        onclick="alert('ID #<?= $row['id'] ?>\nFile: <?= addslashes($row['file']) ?>\nTanggal: <?= $row['date'] ?>');">
                                    🔍
                                </button>
                                <?php elseif ($row['status'] === 'failed'): ?>
                                <a href="index.php?page=sign_document" class="btn btn-xs btn-outline btn-error gap-1">🔄 Coba Lagi</a>
                                <?php else: ?>
                                <span class="text-xs text-slate-400 italic">Menunggu…</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>
                Menampilkan <strong><?= $total_count ?></strong> entri
                <?php if ($filter_status || $filter_search || $filter_date): ?>
                <span class="text-blue-500">(difilter)</span>
                <?php endif; ?>
            </span>
            <a href="index.php?page=sign_document" class="btn btn-xs btn-primary gap-1">✍️ Sign Dokumen Baru</a>
        </div>
    </div>

</div>
