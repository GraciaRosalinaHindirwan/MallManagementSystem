<?php
require_once __DIR__ . '/../../public/auth/checkSession.php';
require_once 'event_data.php';

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

$page_title = 'Vendor Event';
$page       = 'event_vendor';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'add_vendor') {
        addVendor($_POST['id_booking'], $_POST['nama_vendor'], $_POST['kategori'], $_POST['kontak']);
        $msg = 'vendor_added';
    } elseif ($act === 'delete_vendor') {
        deleteVendor((int)$_POST['vendor_id']);
        $msg = 'vendor_deleted';
    }

    header("Location: event_vendor.php?tab=$tab&msg=$msg");
    exit;
}

$msg = $_GET['msg'] ?? '';

$vendors     = getAllVendors();
$approved    = array_filter(getBookings('approved'), fn($b) => true);

$toastMap = [
    'vendor_added'    => ['Vendor berhasil ditambahkan.',   'var(--success)'],
    'vendor_deleted'  => ['Vendor berhasil dihapus.',       'var(--danger)'],
];

ob_start();
?>
<div class="row g-3">
        <div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:12px">
            <div style="padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.08)">
                <span style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:.08em">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Vendor
                </span>
            </div>
            <div style="padding:1.5rem">
                <form method="POST">
                    <input type="hidden" name="action"      value="add_vendor">
                    <input type="hidden" name="current_tab" value="vendor">
                    <div class="mb-3">
                        <label style="font-size:13px;font-weight:500">Event (Approved)</label>
                        <select name="id_booking" required class="form-select mt-1"
                                style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px">
                            <option value="">-- Pilih Event --</option>
                            <?php foreach ($approved as $a): ?>
                            <option value="<?= $a['id_booking'] ?>">#<?= $a['id_booking'] ?> — <?= htmlspecialchars($a['nama_event']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label style="font-size:13px;font-weight:500">Nama Vendor</label>
                        <input type="text" name="nama_vendor" required class="form-control mt-1"
                               style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px"
                               placeholder="Soundmax Pro">
                    </div>
                    <div class="mb-3">
                        <label style="font-size:13px;font-weight:500">Kategori</label>
                        <select name="kategori" class="form-select mt-1"
                                style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px">
                            <?php foreach (['Sound System','Dekorasi','Lighting','Catering Sementara','Booth / Backdrop','Keamanan Event','Lainnya'] as $k): ?>
                            <option><?= $k ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label style="font-size:13px;font-weight:500">Kontak</label>
                        <input type="text" name="kontak" required class="form-control mt-1"
                               style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px"
                               placeholder="0812-xxxx-xxxx">
                    </div>
                    <button style="background:var(--accent);color:#021F42;font-weight:600;border:none;
                                   border-radius:8px;padding:.6rem 1rem;width:100%;cursor:pointer">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Vendor
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
        <div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:12px;overflow:hidden">
            <div style="padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.08)">
                <span style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:.08em">
                    <i class="bi bi-grid me-1"></i>Database Vendor
                </span>
            </div>
            <?php if (empty($vendors)): ?>
            <div style="text-align:center;padding:2rem;opacity:.45;font-size:13px">
                <i class="bi bi-truck" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>Belum ada vendor.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0" style="color:var(--text)">
                    <thead style="background:var(--primary-dark)">
                        <tr style="font-size:12px;font-weight:600;opacity:.7">
                            <th class="ps-3">#</th><th>Nama Vendor</th><th>Kategori</th>
                            <th>Kontak</th><th>Event</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($vendors as $v): ?>
                    <tr style="border-color:rgba(255,255,255,.06);font-size:13px;vertical-align:middle">
                        <td class="ps-3" style="opacity:.4"><?= $v['id'] ?></td>
                        <td><strong><?= htmlspecialchars($v['nama_vendor']) ?></strong></td>
                        <td>
                            <span style="background:rgba(22,126,128,.2);color:#67e8f9;border:1px solid rgba(22,126,128,.35);
                                         border-radius:20px;font-size:11px;padding:2px 10px">
                                <?= htmlspecialchars($v['kategori'] ?? '-') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($v['kontak'] ?? '-') ?></td>
                        <td style="font-size:12px;opacity:.6"><?= htmlspecialchars($v['nama_event'] ?? '-') ?></td>
                        <td>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('Hapus vendor ini?')">
                                <input type="hidden" name="action"      value="delete_vendor">
                                <input type="hidden" name="vendor_id"   value="<?= $v['id'] ?>">
                                <input type="hidden" name="current_tab" value="vendor">
                                <button style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);
                                               color:#fca5a5;border-radius:6px;padding:3px 10px;font-size:11px;cursor:pointer">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
<div>


<?php
$content = ob_get_clean();
require_once dirname(__DIR__, 2) . '../includes/navbarM04_EM.php';