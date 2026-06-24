<?php
require_once __DIR__ . '/../../public/auth/checkSession.php';
require_once '../eventManager/event_data.php';

if (!defined('BASE_URL')) {
    $project_root = realpath(__DIR__ . '/../..');
    $doc_root     = realpath($_SERVER['DOCUMENT_ROOT']);
    $base = '';
    if ($doc_root && $project_root && strpos($project_root, $doc_root) === 0) {
        $base = substr($project_root, strlen($doc_root));
    }
    $base = str_replace('\\', '/', $base);
    define('BASE_URL', $base);
}

$department_name = 'Event Management';
$menu_items = [
    [
        'icon'        => 'fa-solid fa-house',
        'label'       => 'Dashboard',
        'link'        => BASE_URL . '/pages/eventManager/index.php',
        'active_page' => 'index',
    ],
    [
        'icon'        => 'fa-solid fa-calendar-plus',
        'label'       => 'Form Booking',
        'link'        => BASE_URL . '/pages/eventOrganizer/event_booking_form.php',
        'active_page' => 'event_booking_form',
    ],
    [
        'icon'        => 'fa-solid fa-list-check',
        'label'       => 'Status Pengajuan',
        'link'        => BASE_URL . '/pages/eventOrganizer/event_booking_status.php',
        'active_page' => 'event_booking_status',
    ],
    [
        'icon'        => 'fa-solid fa-calendar-week',
        'label'       => 'Kalender & Approval',
        'link'        => BASE_URL . '/pages/eventManager/event_calendar.php',
        'active_page' => 'event_calendar',
    ],
    [
        'icon'        => 'fa-solid fa-people-group',
        'label'       => 'Vendor & Tiket',
        'link'        => BASE_URL . '/pages/eventManager/event_vendor_ticketing.php',
        'active_page' => 'event_vendor_ticketing',
    ],
    [
        'icon'        => 'fa-solid fa-chart-line',
        'label'       => 'Analytics',
        'link'        => BASE_URL . '/pages/eventManager/event_analytics.php',
        'active_page' => 'event_analytics',
    ],
];

$page_title = 'Form Pengajuan Booking Event';
$page       = 'event_booking_form';

$success_id   = null;
$errors       = [];
$konflik_info = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    deleteBooking((int)$_POST['delete_id']);
    header('Location: event_booking_form.php?deleted=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_event'])) {
    $required = ['nama_event','tipe_event','id_area','tanggal_mulai','tanggal_selesai','estimasi_pengunjung'];
    foreach ($required as $f) {
        if (empty($_POST[$f])) $errors[] = "Field <strong>$f</strong> wajib diisi.";
    }
    if (empty($errors) && $_POST['tanggal_selesai'] < $_POST['tanggal_mulai']) {
        $errors[] = "Tanggal selesai tidak boleh lebih awal dari tanggal mulai.";
    }
    if (empty($errors)) {
        $konflik = checkConflict($_POST['id_area'], $_POST['tanggal_mulai'], $_POST['tanggal_selesai']);
        if (!empty($konflik)) {
            $konflik_info = $konflik;
            $errors[] = "Area sudah terpesan pada rentang tanggal tersebut.";
        } else {
            $data = $_POST;
            $data['id_user'] = $_SESSION['user_id'] ?? 1;
            $success_id = addBooking($data);
        }
    }
}

$areas     = getAreas();
$pengajuan = getBookings();
$deleted   = isset($_GET['deleted']);

ob_start();
?>

<?php if ($deleted): ?>
<div id="toastDeleted" style="position:fixed;top:1.5rem;right:1.5rem;background:var(--success);
     color:#fff;padding:.6rem 1.2rem;border-radius:8px;font-size:13px;z-index:9999">
    <i class="bi bi-check-circle me-2"></i>Pengajuan berhasil dihapus.
</div>
<script>setTimeout(()=>document.getElementById('toastDeleted').remove(),3000)</script>
<?php endif; ?>

<div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:12px;
            overflow:hidden;margin-bottom:1.5rem">
    <div style="padding:.85rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.08);
                display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:13px;font-weight:600;color:var(--accent);text-transform:uppercase;letter-spacing:.07em">
            <i class="bi bi-list-ul me-1"></i>Daftar Pengajuan
        </span>
        <span style="background:rgba(255,255,255,.1);color:var(--text);padding:2px 10px;
                     border-radius:20px;font-size:12px"><?= count($pengajuan) ?> pengajuan</span>
    </div>
    <?php if (empty($pengajuan)): ?>
    <div style="text-align:center;padding:2rem;opacity:.45;font-size:13px">
        <i class="bi bi-inbox d-block" style="font-size:2rem;margin-bottom:.5rem"></i>Belum ada pengajuan.
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table mb-0" style="color:var(--text)">
            <thead style="background:var(--primary-dark)">
                <tr style="font-size:12px;font-weight:600;opacity:.7">
                    <th class="ps-3">ID</th><th>Nama Event</th><th>Tipe</th><th>Area</th>
                    <th>Tanggal</th><th>Status</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pengajuan as $p): ?>
            <tr style="border-color:rgba(255,255,255,.06);font-size:13px;vertical-align:middle">
                <td class="ps-3"><strong style="color:var(--accent)">#<?= $p['id_booking'] ?></strong></td>
                <td><?= htmlspecialchars($p['nama_event']) ?></td>
                <td><span style="background:rgba(22,126,128,.2);color:#67e8f9;border:1px solid rgba(22,126,128,.3);
                           border-radius:20px;font-size:11px;padding:2px 10px">
                    <?= htmlspecialchars($p['tipe_event']) ?></span></td>
                <td><?= htmlspecialchars($p['nama_area']) ?></td>
                <td style="font-size:12px">
                    <?= date('d M Y', strtotime($p['tanggal_mulai'])) ?>
                    <?php if ($p['tanggal_mulai'] !== $p['tanggal_selesai']): ?>
                        <br><small style="opacity:.5">s/d <?= date('d M Y', strtotime($p['tanggal_selesai'])) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= statusBadge($p['status']) ?></td>
                <td>
                    <button onclick="confirmDelete(<?= $p['id_booking'] ?>, '<?= addslashes(htmlspecialchars($p['nama_event'])) ?>')"
                            style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);
                                   color:#fca5a5;border-radius:6px;padding:3px 10px;font-size:11px;cursor:pointer">
                        <i class="bi bi-trash3"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<form method="POST" id="deleteForm" style="display:none">
    <input type="hidden" name="delete_id" id="deleteId">
</form>

<?php if ($success_id): ?>
<div style="background:rgba(34,197,94,.12);border:1px solid var(--success);border-radius:12px;
            padding:2rem;text-align:center">
    <i class="bi bi-check-circle-fill text-success" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
    <h4 class="fw-bold">Pengajuan Berhasil Dikirim!</h4>
    <p class="mb-1">ID Pengajuan: <strong style="color:var(--accent)">#<?= $success_id ?></strong></p>
    <p style="opacity:.6;font-size:13px">Tim Admin Event akan mereview dalam 1–2 hari kerja.</p>
    <div class="d-flex gap-2 justify-content-center mt-3">
        <a href="event_booking_form.php"
           style="background:linear-gradient(135deg,var(--accent),var(--secondary));color:#021F42;
                  font-weight:600;border:none;padding:.6rem 1.5rem;border-radius:8px;text-decoration:none">
            + Ajukan Event Lain
        </a>
        <a href="event_booking_status.php"
           class="btn btn-outline-light btn-sm">Pantau Status</a>
    </div>
</div>

<?php else: ?>

<?php if (!empty($errors)): ?>
<div style="background:rgba(239,68,68,.12);border:1px solid var(--danger);border-radius:10px;
            padding:1rem;margin-bottom:1rem">
    <div class="d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill text-danger mt-1"></i>
        <div>
            <strong>Tidak dapat mengirim pengajuan:</strong>
            <ul class="mb-0 mt-1" style="font-size:13px">
                <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
            </ul>
            <?php if (!empty($konflik_info)): ?>
            <div class="mt-2" style="font-size:13px">
                <strong>Konflik dengan:</strong>
                <?php foreach ($konflik_info as $k): ?>
                <div class="mt-1"><i class="bi bi-dot"></i>
                    <strong><?= htmlspecialchars($k['nama_event']) ?></strong>
                    · <?= date('d M', strtotime($k['tanggal_mulai'])) ?>
                    s/d <?= date('d M Y', strtotime($k['tanggal_selesai'])) ?>
                    · <?= statusBadge($k['status']) ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div style="background:var(--primary);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:1.5rem">
    <div style="font-size:13px;font-weight:600;color:var(--accent);text-transform:uppercase;
                letter-spacing:.08em;margin-bottom:1.25rem">
        <i class="bi bi-plus-circle me-1"></i>Form Pengajuan Baru
    </div>
    <form method="POST">

        <div style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;
                    letter-spacing:.08em;margin-bottom:.75rem">
            <i class="bi bi-info-circle me-1"></i>Detail Event
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label style="font-size:13px;font-weight:500">Nama Event <span class="text-danger">*</span></label>
                <input type="text" name="nama_event" class="form-control mt-1"
                       style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px"
                       placeholder="Nama event..." value="<?= htmlspecialchars($_POST['nama_event'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label style="font-size:13px;font-weight:500">Tipe Event <span class="text-danger">*</span></label>
                <select name="tipe_event" class="form-select mt-1"
                        style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px">
                    <option value="">-- Pilih Tipe --</option>
                    <?php foreach (['Bazar / Pameran','Launching Produk','Konser / Hiburan','Job Fair','Aktivasi Brand / Sponsor','Event Internal Mall','Wedding Expo','Lainnya'] as $t): ?>
                    <option value="<?= $t ?>" <?= (($_POST['tipe_event'] ?? '') === $t) ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;
                    letter-spacing:.08em;margin-bottom:.75rem">
            <i class="bi bi-geo-alt me-1"></i>Pilih Area
        </div>
        <?php if (empty($areas)): ?>
        <div style="opacity:.5;font-size:13px;margin-bottom:1.5rem">Tidak ada area aktif tersedia.</div>
        <?php else: ?>
        <div class="row g-3 mb-4">
            <?php foreach ($areas as $area): ?>
            <div class="col-md-6 col-lg-4">
                <label style="cursor:pointer;display:block">
                    <input type="radio" name="id_area" value="<?= $area['id_area'] ?>" class="d-none area-radio"
                           <?= (($_POST['id_area'] ?? '') == $area['id_area']) ? 'checked' : '' ?>>
                    <div class="area-card <?= (($_POST['id_area'] ?? '') == $area['id_area']) ? 'selected' : '' ?>"
                         style="background:var(--primary-dark);border:2px solid transparent;border-radius:10px;
                                padding:1rem;transition:all .2s">
                        <div class="d-flex justify-content-between align-items-start">
                            <strong style="font-size:13px"><?= htmlspecialchars($area['nama_area']) ?></strong>
                            <span style="background:var(--secondary);color:var(--text);font-size:11px;
                                         padding:2px 8px;border-radius:20px">
                                <?= number_format($area['kapasitas']) ?> pax
                            </span>
                        </div>
                        <?php if (!empty($area['floor_number'])): ?>
                        <div style="font-size:11px;opacity:.5;margin-top:3px">
                            <?= htmlspecialchars($area['building_name'] ?? '') ?> — Lt. <?= $area['floor_number'] ?>
                        </div>
                        <?php endif; ?>
                        <div style="font-size:11px;opacity:.6;margin-top:5px">
                            <i class="bi bi-check2-circle me-1"></i><?= htmlspecialchars($area['fasilitas'] ?? '-') ?>
                        </div>
                    </div>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;
                    letter-spacing:.08em;margin-bottom:.75rem">
            <i class="bi bi-calendar3 me-1"></i>Jadwal & Kebutuhan
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label style="font-size:13px;font-weight:500">Tanggal Mulai <span class="text-danger">*</span></label>
                <input type="datetime-local" name="tanggal_mulai" id="tglMulai" class="form-control mt-1"
                       style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px"
                       value="<?= $_POST['tanggal_mulai'] ?? '' ?>"
                       min="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="col-md-4">
                <label style="font-size:13px;font-weight:500">Tanggal Selesai <span class="text-danger">*</span></label>
                <input type="datetime-local" name="tanggal_selesai" id="tglSelesai" class="form-control mt-1"
                       style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px"
                       value="<?= $_POST['tanggal_selesai'] ?? '' ?>">
            </div>
            <div class="col-md-4">
                <label style="font-size:13px;font-weight:500">Estimasi Pengunjung <span class="text-danger">*</span></label>
                <input type="number" name="estimasi_pengunjung" class="form-control mt-1"
                       style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px"
                       placeholder="500" min="1" value="<?= $_POST['estimasi_pengunjung'] ?? '' ?>">
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center pt-3"
             style="border-top:1px solid rgba(255,255,255,.1)">
            <small style="opacity:.45;font-size:11px"><i class="bi bi-info-circle me-1"></i>Pastikan data yang dimasukkan benar</small>
            <button type="submit"
                    style="background:linear-gradient(135deg,var(--accent),var(--secondary));color:#021F42;
                           font-weight:600;border:none;padding:.65rem 2rem;border-radius:8px;cursor:pointer">
                <i class="bi bi-send me-2"></i>Kirim Pengajuan
            </button>
        </div>
    </form>
</div>

<div class="mt-3 text-center">
    <a href="event_booking_status.php" style="color:var(--accent);font-size:13px">
        <i class="bi bi-list-check me-1"></i>Lihat Halaman Status Pengajuan
    </a>
</div>
<?php endif; ?>

<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content" style="background:var(--primary);color:var(--text);border:1px solid rgba(255,255,255,.1)">
      <div class="modal-body text-center py-4">
        <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
        <p class="mb-1 fw-bold">Hapus Pengajuan?</p>
        <p id="deleteDesc" style="font-size:12px;opacity:.7" class="mb-3"></p>
        <div class="d-flex gap-2 justify-content-center">
          <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-sm" style="background:var(--danger);color:#fff"
                  onclick="document.getElementById('deleteForm').submit()">Ya, Hapus</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$extra_scripts = <<<JS
<script>
// area card select
document.querySelectorAll('.area-radio').forEach(r => {
    r.addEventListener('change', () => {
        document.querySelectorAll('.area-card').forEach(c => {
            c.style.borderColor = 'transparent';
            c.style.background  = 'var(--primary-dark)';
        });
        if (r.checked) {
            const card = r.closest('label').querySelector('.area-card');
            card.style.borderColor = 'var(--accent)';
            card.style.background  = 'rgba(0,212,216,.08)';
        }
    });
});
// tanggal min
document.getElementById('tglMulai')?.addEventListener('change', function() {
    document.getElementById('tglSelesai').min = this.value;
});
// delete confirm
function confirmDelete(id, nama) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteDesc').textContent = '"' + nama + '" (#' + id + ') akan dihapus permanen.';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
// init selected areas
document.querySelectorAll('.area-radio:checked').forEach(r => {
    const card = r.closest('label').querySelector('.area-card');
    if(card){ card.style.borderColor='var(--accent)'; card.style.background='rgba(0,212,216,.08)'; }
});
</script>
JS;

$content = ob_get_clean();
require_once '../../includes/navbar.php';