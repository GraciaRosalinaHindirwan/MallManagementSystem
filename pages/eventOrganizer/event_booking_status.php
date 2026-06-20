<?php
require_once '../eventManager/event_data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    deletePengajuan($_POST['delete_id']);
    header('Location: event_booking_status.php?deleted=1');
    exit;
}

$semua   = getPengajuan();
$deleted = isset($_GET['deleted']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISFO MALL - Status Pengajuan Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../public/asset/css/designSystem.css" rel="stylesheet">
    <style>
        body { background: var(--background); color: var(--text); font-family: var(--font-family); }
        .page-header { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 1.75rem 2rem; border-radius: 12px; margin-bottom: 1.5rem; }
        .btn-back { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); color: var(--text); border-radius: 8px; padding: .4rem 1rem; font-size: var(--label); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: background .2s; }
        .btn-back:hover { background: rgba(255,255,255,.15); color: var(--text); }
        .status-card { background: var(--primary); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; margin-bottom: 1rem; transition: border-color .2s; }
        .status-card:hover { border-color: rgba(0,212,216,.3); }
        .status-card .card-body { padding: 1.25rem 1.5rem; }
        .status-card.approved { border-left: 4px solid var(--success); }
        .status-card.pending  { border-left: 4px solid #f59e0b; }
        .status-card.rejected { border-left: 4px solid var(--danger); }
        .status-card.revision { border-left: 4px solid var(--secondary); }
        .meta-label { font-size: 11px; opacity: .55; text-transform: uppercase; letter-spacing: .06em; }
        .meta-value { font-size: var(--label); font-weight: 500; }
        .timeline-steps { display: flex; margin-top: 1rem; }
        .t-step { flex: 1; text-align: center; position: relative; }
        .t-step::before { content: ''; position: absolute; top: 14px; left: 50%; right: -50%; height: 2px; background: rgba(255,255,255,.1); z-index: 0; }
        .t-step:last-child::before { display: none; }
        .t-dot { width: 28px; height: 28px; border-radius: 50%; border: 2px solid rgba(255,255,255,.15); background: var(--primary-dark); display: flex; align-items: center; justify-content: center; margin: 0 auto; position: relative; z-index: 1; font-size: 12px; }
        .t-dot.done   { background: var(--success); border-color: var(--success); color: #fff; }
        .t-dot.active { background: #f59e0b; border-color: #f59e0b; color: #fff; }
        .t-dot.fail   { background: var(--danger); border-color: var(--danger); color: #fff; }
        .t-label { font-size: 10px; margin-top: 5px; opacity: .6; }
        .empty-state { text-align: center; padding: 3rem; opacity: .5; }
        .filter-btn { background: var(--primary); border: 1px solid rgba(255,255,255,.15); color: var(--text); border-radius: 20px; padding: 4px 14px; font-size: var(--caption); cursor: pointer; }
        .filter-btn.active { background: var(--accent); color: #021F42; border-color: var(--accent); font-weight: 600; }
        .btn-delete-sm { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.25); color: #fca5a5; border-radius: 6px; padding: 3px 10px; font-size: 11px; cursor: pointer; transition: background .2s; }
        .btn-delete-sm:hover { background: rgba(239,68,68,.28); }
        .toast-deleted { position: fixed; top: 1.5rem; right: 1.5rem; background: var(--success); color: #fff; padding: .6rem 1.2rem; border-radius: 8px; font-size: var(--label); z-index: 9999; display: none; }
    </style>
</head>
<body>
<div class="toast-deleted" id="toastDeleted"><i class="bi bi-check-circle me-2"></i>Pengajuan berhasil dihapus.</div>

<div class="container py-4">

    <div class="mb-3">
        <a href="event_booking_form.php" class="btn-back me-2"><i class="bi bi-arrow-left"></i> Form Pengajuan</a>
        <a href="../eventManager/index.php" class="btn-back"><i class="bi bi-house"></i> Dashboard</a>
    </div>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-list-check fs-2"></i>
            <div>
                <h4 class="mb-0 fw-bold">Status Pengajuan Event</h4>
                <small style="opacity:.8">SISFO MALL</small>
            </div>
        </div>
        <a href="event_booking_form.php" class="btn btn-sm" style="background:var(--accent);color:#021F42;font-weight:600;border-radius:8px;padding:.45rem 1.1rem">
            <i class="bi bi-plus-lg me-1"></i>Ajukan Baru
        </a>
    </div>

    <div class="d-flex gap-2 flex-wrap mb-3">
        <button class="filter-btn active" onclick="filterStatus('all',this)">Semua (<?= count($semua) ?>)</button>
        <?php
        $counts  = ['pending'=>0,'approved'=>0,'revision'=>0,'rejected'=>0];
        foreach ($semua as $p) if (isset($counts[$p['status']])) $counts[$p['status']]++;
        $lbls    = ['pending'=>'Pending','approved'=>'Approved','revision'=>'Perlu Revisi','rejected'=>'Ditolak'];
        foreach ($lbls as $k=>$v): ?>
        <button class="filter-btn" onclick="filterStatus('<?= $k ?>',this)"><?= $v ?> (<?= $counts[$k] ?>)</button>
        <?php endforeach; ?>
    </div>

    <?php if (empty($semua)): ?>
    <div class="empty-state">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        Belum ada pengajuan. <a href="event_booking_form.php" style="color:var(--accent)">Buat sekarang</a>.
    </div>
    <?php else: ?>

    <?php foreach ($semua as $p):
        $steps = [
            ['label'=>'Diajukan',    'status'=>'done'],
            ['label'=>'Review Admin','status'=>in_array($p['status'],['approved','rejected','revision'])?'done':'active'],
            ['label'=>'Persetujuan', 'status'=>$p['status']==='approved'?'done':($p['status']==='rejected'?'fail':($p['status']==='revision'?'active':''))],
            ['label'=>'Kontrak & DP','status'=>$p['status']==='approved'?'active':''],
        ];
    ?>
    <div class="status-card <?= $p['status'] ?>" data-status="<?= $p['status'] ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <span style="font-size:var(--caption);color:var(--accent);font-weight:600"><?= $p['id'] ?></span>
                    <h6 class="mb-0 mt-1 fw-bold"><?= $p['tipe_event'] ?></h6>
                    <small style="opacity:.6"><?= $p['pemohon'] ?></small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?= statusBadge($p['status']) ?>
                    <button class="btn-delete-sm" onclick="confirmDelete('<?= $p['id'] ?>','<?= addslashes($p['tipe_event']) ?>')">
                        <i class="bi bi-trash3 me-1"></i>Hapus
                    </button>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-6 col-md-3"><div class="meta-label">Area</div><div class="meta-value"><?= $p['nama_area'] ?></div></div>
                <div class="col-6 col-md-3"><div class="meta-label">Tanggal</div><div class="meta-value" style="font-size:13px"><?= date('d M Y', strtotime($p['tanggal_mulai'])) ?><?= $p['tanggal_mulai']!==$p['tanggal_selesai']?' – '.date('d M Y', strtotime($p['tanggal_selesai'])):'' ?></div></div>
                <div class="col-6 col-md-3"><div class="meta-label">Est. Pengunjung</div><div class="meta-value"><?= number_format($p['estimasi_pengunjung']) ?> pax</div></div>
                <div class="col-6 col-md-3"><div class="meta-label">Diajukan</div><div class="meta-value"><?= date('d M Y', strtotime($p['created_at'])) ?></div></div>
            </div>

            <?php if (!empty($p['catatan_admin'])): ?>
            <div class="mt-2 p-2 rounded" style="background:rgba(255,255,255,.04);font-size:var(--caption);border-left:3px solid var(--secondary)">
                <i class="bi bi-chat-left-text me-1" style="color:var(--accent)"></i>
                <strong>Catatan Admin:</strong> <?= $p['catatan_admin'] ?>
            </div>
            <?php endif; ?>

            <div class="timeline-steps mt-3">
                <?php foreach ($steps as $st): ?>
                <div class="t-step">
                    <div class="t-dot <?= $st['status'] ?>">
                        <?php if ($st['status']==='done'): ?><i class="bi bi-check-lg"></i>
                        <?php elseif ($st['status']==='fail'): ?><i class="bi bi-x-lg"></i>
                        <?php elseif ($st['status']==='active'): ?><i class="bi bi-clock"></i>
                        <?php else: ?><i class="bi bi-circle"></i><?php endif; ?>
                    </div>
                    <div class="t-label"><?= $st['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<form method="POST" id="deleteForm" style="display:none">
    <input type="hidden" name="delete_id" id="deleteId">
</form>

<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content" style="background:var(--primary);color:var(--text);border:1px solid rgba(255,255,255,.1)">
      <div class="modal-body text-center py-4">
        <i class="bi bi-exclamation-triangle-fill text-warning fs-2 mb-2 d-block"></i>
        <p class="mb-1 fw-bold">Hapus Pengajuan?</p>
        <p id="deleteDesc" style="font-size:var(--caption);opacity:.7" class="mb-3"></p>
        <div class="d-flex gap-2 justify-content-center">
          <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-sm" style="background:var(--danger);color:#fff" onclick="document.getElementById('deleteForm').submit()">Ya, Hapus</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterStatus(status, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.status-card').forEach(c => {
        c.style.display = (status==='all' || c.dataset.status===status) ? '' : 'none';
    });
}
function confirmDelete(id, nama) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteDesc').textContent = `"${nama}" (${id}) akan dihapus permanen.`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
<?php if ($deleted): ?>
const t = document.getElementById('toastDeleted');
t.style.display = 'block';
setTimeout(() => t.style.display = 'none', 3000);
<?php endif; ?>
</script>
</body>
</html>