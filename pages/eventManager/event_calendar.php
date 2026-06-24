<?php
require_once 'event_data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id      = $_POST['pengajuan_id'] ?? '';
    $catatan = $_POST['catatan'] ?? '';
    switch ($_POST['action']) {
        case 'approve':  updateStatusPengajuan($id, 'approved', $catatan); break;
        case 'reject':   updateStatusPengajuan($id, 'rejected', $catatan); break;
        case 'revision': updateStatusPengajuan($id, 'revision', $catatan); break;
        case 'delete':   deletePengajuan($id); break;
    }
    header('Location: event_calendar.php' . ($_POST['action']==='delete' ? '?deleted=1' : ''));
    exit;
}

$semua   = getPengajuan();
$areas   = getAreas();
$deleted = isset($_GET['deleted']);

$byArea = [];
foreach ($areas as $a) $byArea[$a['id']] = ['area' => $a, 'events' => []];
foreach ($semua as $p) {
    if ($p['status'] !== 'rejected' && isset($byArea[$p['id_area']]))
        $byArea[$p['id_area']]['events'][] = $p;
}
$pending = array_filter($semua, fn($p) => $p['status'] === 'pending');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISFO MALL - Kalender Persetujuan Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../public/asset/css/designSystem.css" rel="stylesheet">
    <style>
        body { background: var(--background); color: var(--text); font-family: var(--font-family); }
        .page-header { background: linear-gradient(135deg, var(--secondary-dark) 0%, var(--secondary) 100%); padding: 1.75rem 2rem; border-radius: 12px; margin-bottom: 1.5rem; }
        .btn-back { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); color: var(--text); border-radius: 8px; padding: .4rem 1rem; font-size: var(--label); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: background .2s; }
        .btn-back:hover { background: rgba(255,255,255,.15); color: var(--text); }
        .card-section { background: var(--primary); border-radius: 12px; border: 1px solid rgba(255,255,255,.08); margin-bottom: 1.5rem; }
        .card-section-header { padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.08); }
        .card-section-body { padding: 1.5rem; }
        .section-label { font-size: var(--label); text-transform: uppercase; letter-spacing: .08em; color: var(--accent); font-weight: 600; }
        .area-row { background: var(--primary-dark); border-radius: 8px; padding: 1rem; margin-bottom: .75rem; }
        .ev-chip { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; margin: 2px; }
        .ev-chip.approved { background: rgba(34,197,94,.2);  color: #86efac; border: 1px solid rgba(34,197,94,.3); }
        .ev-chip.pending  { background: rgba(251,191,36,.2); color: #fde68a; border: 1px solid rgba(251,191,36,.3); }
        .ev-chip.revision { background: rgba(56,189,248,.2); color: #7dd3fc; border: 1px solid rgba(56,189,248,.3); }
        .table-dark-custom thead th { background: var(--primary-dark); color: var(--text); font-size: var(--label); font-weight: 600; border-color: rgba(255,255,255,.08); }
        .table-dark-custom td { border-color: rgba(255,255,255,.06); font-size: var(--label); vertical-align: middle; }
        .table-dark-custom tr:hover td { background: rgba(255,255,255,.02); }
        .btn-approve  { background: var(--success); color:#fff; border:none; }
        .btn-reject   { background: var(--danger);  color:#fff; border:none; }
        .btn-revision { background: var(--secondary); color:#fff; border:none; }
        .btn-del      { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
        .btn-approve:hover,.btn-reject:hover,.btn-revision:hover { opacity:.85; color:#fff; }
        .btn-del:hover { background: rgba(239,68,68,.3); color: #fca5a5; }
        .stat-pill { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,.07); border-radius: 20px; padding: 4px 14px; font-size: var(--caption); }
        .toast-done { position: fixed; top: 1.5rem; right: 1.5rem; padding: .6rem 1.2rem; border-radius: 8px; font-size: var(--label); z-index: 9999; display: none; }
    </style>
</head>
<body>
<div class="toast-done bg-success text-white" id="toastDone"></div>

<div class="container-fluid py-4 px-4">

    <div class="mb-3">
        <a href="index.php" class="btn-back"><i class="bi bi-arrow-left"></i> Dashboard Event</a>
    </div>

    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-calendar2-week me-2"></i>Kalender & Approval Event</h4>
            <small style="opacity:.8">SISFO MALL</small>
        </div>
        <div class="d-flex gap-2">
            <span class="stat-pill"><i class="bi bi-hourglass-split text-warning"></i><?= count($pending) ?> pending</span>
            <span class="stat-pill"><i class="bi bi-calendar-check text-success"></i><?= count(array_filter($semua, fn($p)=>$p['status']==='approved')) ?> approved</span>
        </div>
    </div>

    <div class="card-section">
        <div class="card-section-header"><span class="section-label"><i class="bi bi-map me-1"></i>Status Penggunaan Area</span></div>
        <div class="card-section-body">
            <?php foreach ($byArea as $row): ?>
            <div class="area-row">
                <div style="font-weight:600;font-size:var(--label);margin-bottom:.5rem">
                    <i class="bi bi-geo-alt-fill me-1" style="color:var(--accent)"></i><?= $row['area']['nama'] ?>
                    <span style="font-weight:400;opacity:.55;font-size:11px;margin-left:6px">
                        <?= number_format($row['area']['kapasitas']) ?> pax · Rp <?= number_format($row['area']['tarif_per_hari']) ?>/hari
                    </span>
                </div>
                <div>
                    <?php if (empty($row['events'])): ?>
                        <span style="font-size:var(--caption);opacity:.4"><i class="bi bi-calendar-x me-1"></i>Belum ada booking</span>
                    <?php else: ?>
                        <?php foreach ($row['events'] as $ev): ?>
                        <span class="ev-chip <?= $ev['status'] ?>">
                            <i class="bi bi-circle-fill" style="font-size:6px"></i>
                            <?= $ev['id'] ?> · <?= $ev['tipe_event'] ?>
                            (<?= date('d M', strtotime($ev['tanggal_mulai'])) ?>–<?= date('d M', strtotime($ev['tanggal_selesai'])) ?>)
                        </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card-section">
        <div class="card-section-header d-flex justify-content-between align-items-center">
            <span class="section-label"><i class="bi bi-ui-checks me-1"></i>Antrian Approval</span>
            <span class="badge" style="background:var(--text-accent);color:#021F42"><?= count($pending) ?> pending</span>
        </div>
        <div class="p-0">
            <?php if (empty($pending)): ?>
            <div style="text-align:center;padding:2rem;opacity:.5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Tidak ada yang menunggu review.</div>
            <?php else: ?>
            <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
                <thead><tr><th>ID</th><th>Pemohon</th><th>Event</th><th>Area</th><th>Tanggal</th><th>Est.</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($pending as $p): ?>
                <tr>
                    <td><strong style="color:var(--accent)"><?= $p['id'] ?></strong></td>
                    <td><?= $p['pemohon'] ?></td>
                    <td><?= $p['tipe_event'] ?></td>
                    <td><?= $p['nama_area'] ?></td>
                    <td style="font-size:12px">
                        <?= date('d M Y', strtotime($p['tanggal_mulai'])) ?>
                        <?php if ($p['tanggal_mulai']!==$p['tanggal_selesai']): ?><br><small style="opacity:.6">s/d <?= date('d M Y', strtotime($p['tanggal_selesai'])) ?></small><?php endif; ?>
                    </td>
                    <td><?= number_format($p['estimasi_pengunjung']) ?></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                            <button class="btn btn-sm btn-approve px-2 py-1" onclick="openAction('<?= $p['id'] ?>','approve')"><i class="bi bi-check-lg"></i></button>
                            <button class="btn btn-sm btn-revision px-2 py-1" onclick="openAction('<?= $p['id'] ?>','revision')"><i class="bi bi-arrow-repeat"></i></button>
                            <button class="btn btn-sm btn-reject px-2 py-1" onclick="openAction('<?= $p['id'] ?>','reject')"><i class="bi bi-x-lg"></i></button>
                            <button class="btn btn-sm btn-del px-2 py-1" onclick="openAction('<?= $p['id'] ?>','delete')"><i class="bi bi-trash3"></i></button>
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

    <div class="card-section">
        <div class="card-section-header"><span class="section-label"><i class="bi bi-list-ul me-1"></i>Semua Pengajuan</span></div>
        <div class="p-0">
        <div class="table-responsive">
        <table class="table table-dark-custom mb-0">
            <thead><tr><th>ID</th><th>Pemohon</th><th>Event</th><th>Area</th><th>Tanggal</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($semua as $p): ?>
            <tr>
                <td><strong style="color:var(--accent)"><?= $p['id'] ?></strong></td>
                <td><?= $p['pemohon'] ?></td>
                <td><?= $p['tipe_event'] ?></td>
                <td><?= $p['nama_area'] ?></td>
                <td style="font-size:12px"><?= date('d M Y', strtotime($p['tanggal_mulai'])) ?><?= ($p['tanggal_mulai']!==$p['tanggal_selesai'])?' – '.date('d M Y', strtotime($p['tanggal_selesai'])):'' ?></td>
                <td><?= statusBadge($p['status']) ?></td>
                <td style="font-size:12px;opacity:.65;max-width:180px"><?= $p['catatan_admin'] ?: '—' ?></td>
                <td>
                    <button class="btn btn-sm btn-del px-2 py-1" onclick="openAction('<?= $p['id'] ?>','delete')">
                        <i class="bi bi-trash3"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        </div>
    </div>
</div>

<div class="modal fade" id="actionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:var(--primary);color:var(--text);border:1px solid rgba(255,255,255,.1)">
      <div class="modal-header" style="border-color:rgba(255,255,255,.1)">
        <h5 class="modal-title" id="modalTitle"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
            <input type="hidden" name="pengajuan_id" id="modalPengajuanId">
            <input type="hidden" name="action" id="modalAction">
            <p id="modalDesc" style="font-size:var(--label)"></p>
            <div id="catatanWrap">
                <label class="form-label" style="font-size:var(--label)">Catatan (opsional)</label>
                <textarea name="catatan" class="form-control" rows="3" style="background:var(--primary-dark);color:var(--text);border:1px solid rgba(255,255,255,.15)" placeholder="Tulis catatan untuk pemohon..."></textarea>
            </div>
        </div>
        <div class="modal-footer" style="border-color:rgba(255,255,255,.1)">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-sm" id="modalSubmitBtn"></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openAction(id, action) {
    document.getElementById('modalPengajuanId').value = id;
    document.getElementById('modalAction').value = action;
    const cfg = {
        approve:  { title:'Setujui Pengajuan',  desc:`Setujui pengajuan <strong>${id}</strong>?`,            cls:'btn-approve',  label:'Setujui',    notes:true  },
        reject:   { title:'Tolak Pengajuan',    desc:`Tolak pengajuan <strong>${id}</strong>?`,               cls:'btn-reject',   label:'Tolak',      notes:true  },
        revision: { title:'Minta Revisi',       desc:`Kembalikan <strong>${id}</strong> untuk direvisi.`,     cls:'btn-revision', label:'Kirim',      notes:true  },
        delete:   { title:'Hapus Pengajuan',    desc:`Hapus <strong>${id}</strong> secara permanen?<br><small style="opacity:.7">Tiket & sponsorship terkait juga akan dihapus.</small>`, cls:'', label:'Ya, Hapus', notes:false },
    };
    const c = cfg[action];
    document.getElementById('modalTitle').textContent    = c.title;
    document.getElementById('modalDesc').innerHTML       = c.desc;
    document.getElementById('catatanWrap').style.display = c.notes ? '' : 'none';
    const btn = document.getElementById('modalSubmitBtn');
    btn.className = 'btn btn-sm ' + (action==='delete' ? '' : c.cls);
    if (action === 'delete') btn.style.cssText = 'background:var(--danger);color:#fff';
    else btn.style.cssText = '';
    btn.textContent = c.label;
    new bootstrap.Modal(document.getElementById('actionModal')).show();
}
<?php if ($deleted): ?>
const t = document.getElementById('toastDone');
t.innerHTML = '<i class="bi bi-check-circle me-2"></i>Pengajuan berhasil dihapus.';
t.style.display = 'block';
setTimeout(()=>t.style.display='none', 3000);
<?php endif; ?>
</script>
</body>
</html>