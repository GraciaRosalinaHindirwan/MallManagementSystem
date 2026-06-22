<?php
require_once '../eventManager/event_data.php';

$success_id  = null;
$errors      = [];
$konflik_info = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        deletePengajuan($_POST['delete_id']);
        header('Location: event_booking_form.php?deleted=1');
        exit;
    }

    $required = ['pemohon','tipe_event','id_area','tanggal_mulai','tanggal_selesai','estimasi_pengunjung'];
    foreach ($required as $f) {
        if (empty($_POST[$f])) $errors[] = "Field <strong>$f</strong> wajib diisi.";
    }
    if (empty($errors)) {
        $konflik = checkConflict($_POST['id_area'], $_POST['tanggal_mulai'], $_POST['tanggal_selesai']);
        if (!empty($konflik)) {
            $konflik_info = $konflik;
            $errors[] = "Area sudah terpesan pada rentang tanggal tersebut.";
        } else {
            $success_id = addPengajuan($_POST);
        }
    }
}

$areas     = getAreas();
$pengajuan = getPengajuan();
$deleted   = isset($_GET['deleted']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISFO MALL - Pengajuan Booking Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../public/asset/css/designSystem.css" rel="stylesheet">
    <style>
        body { background: var(--background); color: var(--text); font-family: var(--font-family); }
        .page-header { background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 1.75rem 2rem; border-radius: 12px; margin-bottom: 1.5rem; }
        .btn-back { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); color: var(--text); border-radius: 8px; padding: .4rem 1rem; font-size: var(--label); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: background .2s; }
        .btn-back:hover { background: rgba(255,255,255,.15); color: var(--text); }
        .card-form { background: var(--primary); border: 1px solid rgba(255,255,255,.1); border-radius: 12px; }
        .form-label { font-size: var(--label); font-weight: 500; }
        .form-control, .form-select { background: var(--primary-dark); border: 1px solid rgba(255,255,255,.15); color: var(--text); border-radius: 8px; }
        .form-control:focus, .form-select:focus { background: var(--primary-dark); border-color: var(--accent); color: var(--text); box-shadow: 0 0 0 3px rgba(0,212,216,.15); }
        .form-control::placeholder { color: rgba(245,247,250,.4); }
        .form-select option { background: var(--primary-dark); }
        .btn-submit { background: linear-gradient(135deg, var(--accent), var(--secondary)); color: #021F42; font-weight: 600; border: none; padding: .7rem 2rem; border-radius: 8px; }
        .btn-submit:hover { opacity: .9; color: #021F42; }
        .area-card { background: var(--primary-dark); border: 2px solid transparent; border-radius: 10px; padding: 1rem; cursor: pointer; transition: all .2s; }
        .area-card:hover, .area-card.selected { border-color: var(--accent); background: rgba(0,212,216,.08); }
        .area-card .tarif { color: var(--text-accent); font-weight: 600; }
        .conflict-alert { background: rgba(239,68,68,.12); border: 1px solid var(--danger); border-radius: 10px; padding: 1rem; }
        .success-card { background: rgba(34,197,94,.12); border: 1px solid var(--success); border-radius: 12px; padding: 2rem; text-align: center; }
        .section-title { font-size: var(--label); text-transform: uppercase; letter-spacing: .1em; color: var(--accent); margin-bottom: 1rem; font-weight: 600; }

        /* Daftar pengajuan */
        .list-card { background: var(--primary); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; overflow: hidden; margin-bottom: 1.5rem; }
        .list-card-header { padding: .85rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,.08); display: flex; justify-content: space-between; align-items: center; }
        .table-dark-custom thead th { background: var(--primary-dark); color: var(--text); font-size: var(--label); font-weight: 600; border-color: rgba(255,255,255,.08); }
        .table-dark-custom td { border-color: rgba(255,255,255,.06); font-size: var(--label); vertical-align: middle; }
        .btn-delete { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; border-radius: 6px; padding: 3px 10px; font-size: 12px; cursor: pointer; transition: background .2s; }
        .btn-delete:hover { background: rgba(239,68,68,.3); }
        .toast-deleted { position: fixed; top: 1.5rem; right: 1.5rem; background: var(--success); color: #fff; padding: .6rem 1.2rem; border-radius: 8px; font-size: var(--label); z-index: 9999; display: none; }
    </style>
</head>
<body>
<div class="toast-deleted" id="toastDeleted"><i class="bi bi-check-circle me-2"></i>Pengajuan berhasil dihapus.</div>

<div class="container py-4">

    <div class="mb-3">
        <a href="../eventManager/index.php" class="btn-back"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
    </div>
    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-calendar-event fs-2"></i>
            <div>
                <h4 class="mb-0 fw-bold">Pengajuan Booking Area Event</h4>
                <small style="opacity:.8">SISFO MALL</small>
            </div>
        </div>
    </div>

    <div class="list-card">
        <div class="list-card-header">
            <span style="font-size:var(--label);font-weight:600;color:var(--accent);text-transform:uppercase;letter-spacing:.07em">
                <i class="bi bi-list-ul me-1"></i>Pengajuan Saya
            </span>
            <span class="badge" style="background:rgba(255,255,255,.1)"><?= count($pengajuan) ?> pengajuan</span>
        </div>
        <?php if (empty($pengajuan)): ?>
        <div style="text-align:center;padding:2rem;opacity:.45;font-size:var(--label)">
            <i class="bi bi-inbox d-block fs-2 mb-2"></i>Belum ada pengajuan.
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-dark-custom mb-0">
            <thead><tr><th>ID</th><th>Pemohon</th><th>Event</th><th>Area</th><th>Tanggal</th><th>Status</th><th style="width:80px">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($pengajuan as $p): ?>
            <tr>
                <td><strong style="color:var(--accent)"><?= $p['id'] ?></strong></td>
                <td><?= $p['pemohon'] ?></td>
                <td><?= $p['tipe_event'] ?></td>
                <td><?= $p['nama_area'] ?></td>
                <td style="font-size:12px"><?= date('d M Y', strtotime($p['tanggal_mulai'])) ?><?= $p['tanggal_mulai']!==$p['tanggal_selesai'] ? ' – '.date('d M Y', strtotime($p['tanggal_selesai'])) : '' ?></td>
                <td><?= statusBadge($p['status']) ?></td>
                <td>
                    <button class="btn-delete" onclick="confirmDelete('<?= $p['id'] ?>', '<?= addslashes($p['tipe_event']) ?>')">
                        <i class="bi bi-trash3"></i> Hapus
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Delete confirm form (hidden) -->
    <form method="POST" id="deleteForm" style="display:none">
        <input type="hidden" name="delete_id" id="deleteId">
    </form>

    <?php if ($success_id): ?>
    <div class="success-card">
        <i class="bi bi-check-circle-fill text-success fs-1 mb-3 d-block"></i>
        <h4 class="fw-bold">Pengajuan Berhasil Dikirim!</h4>
        <p class="mb-1">ID Pengajuan: <strong style="color:var(--accent)"><?= $success_id ?></strong></p>
        <p style="opacity:.7;font-size:var(--label)">Tim Admin Event akan mereview dalam 1–2 hari kerja.</p>
        <div class="d-flex gap-2 justify-content-center mt-3">
            <a href="event_booking_form.php" class="btn btn-submit">+ Ajukan Event Lain</a>
            <a href="event_booking_status.php" class="btn btn-outline-light">Pantau Status</a>
        </div>
    </div>
    <?php else: ?>

    <?php if (!empty($errors)): ?>
    <div class="conflict-alert mb-3">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill text-danger mt-1"></i>
            <div>
                <strong>Tidak dapat mengirim pengajuan:</strong>
                <ul class="mb-0 mt-1" style="font-size:var(--label)"><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
                <?php if (!empty($konflik_info)): ?>
                <div class="mt-2">
                    <strong>Konflik dengan:</strong>
                    <?php foreach ($konflik_info as $k): ?>
                    <div class="mt-1" style="font-size:var(--label)">
                        <i class="bi bi-dot"></i> <strong><?= $k['id'] ?></strong> — <?= $k['tipe_event'] ?> · <?= $k['tanggal_mulai'] ?> s/d <?= $k['tanggal_selesai'] ?> · <?= statusBadge($k['status']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card-form p-4">
        <div class="section-title mb-3"><i class="bi bi-plus-circle me-1"></i>Form Pengajuan Baru</div>
        <form method="POST">
            <div class="section-title"><i class="bi bi-person-badge me-1"></i>Identitas Pemohon</div>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Nama / Perusahaan <span class="text-danger">*</span></label>
                    <input type="text" name="pemohon" class="form-control" placeholder="PT / CV / Tenant: ..." value="<?= htmlspecialchars($_POST['pemohon'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tipe Event <span class="text-danger">*</span></label>
                    <select name="tipe_event" class="form-select">
                        <option value="">-- Pilih Tipe --</option>
                        <?php foreach (['Bazar / Pameran','Launching Produk','Konser / Hiburan','Job Fair','Aktivasi Brand / Sponsor','Event Internal Mall','Lainnya'] as $t): ?>
                        <option value="<?= $t ?>" <?= (($_POST['tipe_event']??'')===$t)?'selected':'' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="section-title"><i class="bi bi-geo-alt me-1"></i>Pilih Area</div>
            <div class="row g-3 mb-4">
                <?php foreach ($areas as $area): ?>
                <div class="col-md-6 col-lg-4">
                    <label style="cursor:pointer;display:block">
                        <input type="radio" name="id_area" value="<?= $area['id'] ?>" class="d-none area-radio" <?= (($_POST['id_area']??'')==$area['id'])?'checked':'' ?>>
                        <div class="area-card <?= (($_POST['id_area']??'')==$area['id'])?'selected':'' ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <strong style="font-size:var(--label)"><?= $area['nama'] ?></strong>
                                <span class="badge" style="background:var(--secondary)"><?= number_format($area['kapasitas']) ?> pax</span>
                            </div>
                            <div class="tarif mt-1" style="font-size:var(--label)">Rp <?= number_format($area['tarif_per_hari']) ?>/hari</div>
                            <div style="font-size:var(--caption);opacity:.65;margin-top:3px"><i class="bi bi-check2-circle me-1"></i><?= $area['fasilitas'] ?></div>
                        </div>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="section-title"><i class="bi bi-calendar3 me-1"></i>Jadwal & Kebutuhan</div>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_mulai" class="form-control" value="<?= $_POST['tanggal_mulai'] ?? '' ?>" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_selesai" class="form-control" value="<?= $_POST['tanggal_selesai'] ?? '' ?>" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estimasi Pengunjung <span class="text-danger">*</span></label>
                    <input type="number" name="estimasi_pengunjung" class="form-control" placeholder="500" min="1" value="<?= $_POST['estimasi_pengunjung'] ?? '' ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Kebutuhan Fasilitas</label>
                    <textarea name="kebutuhan" class="form-control" rows="3" placeholder="Booth, sound, backdrop, dll..."><?= htmlspecialchars($_POST['kebutuhan'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="border-color:rgba(255,255,255,.1)!important">
                <small style="opacity:.45;font-size:var(--caption)"><i class="bi bi-info-circle me-1"></i>Pastikan data yang dimasukkan benar</small>
                <button type="submit" class="btn btn-submit"><i class="bi bi-send me-2"></i>Kirim Pengajuan</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div class="mt-3 text-center">
        <a href="event_booking_status.php" style="color:var(--accent);font-size:var(--label)">
            <i class="bi bi-list-check me-1"></i>Lihat Halaman Status Pengajuan
        </a>
    </div>
</div>

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
document.querySelectorAll('.area-radio').forEach(r => {
    r.addEventListener('change', () => {
        document.querySelectorAll('.area-card').forEach(c => c.classList.remove('selected'));
        if (r.checked) r.closest('.area-card').classList.add('selected');
    });
});
document.querySelector('[name=tanggal_mulai]')?.addEventListener('change', function() {
    document.querySelector('[name=tanggal_selesai]').min = this.value;
});
function confirmDelete(id, nama) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteDesc').textContent = `"${nama}" (${id}) akan dihapus permanen beserta tiket & sponsorship terkait.`;
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