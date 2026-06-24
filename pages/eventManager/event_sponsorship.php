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

$page_title = 'Sponsorship Event';
$page       = 'event_sponsorship';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'add_sponsor') {
        addSponsor($_POST['id_booking'], $_POST['sponsor'], $_POST['paket'], $_POST['nilai']);
        $msg = 'sponsor_added';
    } elseif ($act === 'settle_sponsor') {
        settleSponsor($_POST['sponsor_id']);
        $msg = 'sponsor_settled';
    } elseif ($act === 'delete_sponsor') {
        deleteSponsor($_POST['sponsor_id']);
        $msg = 'sponsor_deleted';
    }

    header("Location: event_vendor_ticketing.php?tab=$tab&msg=$msg");
    exit;
}

$msg = $_GET['msg'] ?? '';

$sponsors    = getAllSponsors();
$approved    = array_filter(getBookings('approved'), fn($b) => true);

$toastMap = [
     'sponsor_added'   => ['Sponsor berhasil ditambahkan.',  'var(--success)'],
    'sponsor_deleted' => ['Sponsor berhasil dihapus.',      'var(--danger)'],
    'sponsor_settled' => ['Settlement berhasil dicatat.',   'var(--success)'],
];

$totalSponsor = array_sum(array_column($sponsors, 'nilai'));
$totalLunas   = array_sum(array_map(fn($s) => $s['status_bayar'] === 'lunas' ? $s['nilai'] : 0, $sponsors));

ob_start();
?>

<div class="col-lg-4">
        <div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:12px">
            <div style="padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.08)">
                <span style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:.08em">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Sponsor
                </span>
            </div>
            <div style="padding:1.5rem">
                <form method="POST">
                    <input type="hidden" name="action"      value="add_sponsor">
                    <input type="hidden" name="current_tab" value="sponsor">
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
                        <label style="font-size:13px;font-weight:500">Nama Sponsor</label>
                        <input type="text" name="sponsor" required placeholder="Brand X"
                               class="form-control mt-1"
                               style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px">
                    </div>
                    <div class="mb-3">
                        <label style="font-size:13px;font-weight:500">Paket</label>
                        <select name="paket" class="form-select mt-1"
                                style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px">
                            <?php foreach (['Platinum','Gold','Silver','Media Partner'] as $pk): ?>
                            <option><?= $pk ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label style="font-size:13px;font-weight:500">Nilai (Rp)</label>
                        <input type="number" name="nilai" required min="0" placeholder="10000000"
                               class="form-control mt-1"
                               style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px">
                    </div>
                    <button style="background:var(--accent);color:#021F42;font-weight:600;border:none;
                                   border-radius:8px;padding:.6rem 1rem;width:100%;cursor:pointer">
                        <i class="bi bi-award me-1"></i>Catat Sponsor
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:12px">
            <div style="padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.08);
                        display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:.08em">
                    <i class="bi bi-award me-1"></i>Data Sponsorship
                </span>
                <div style="font-size:12px">
                    <span style="color:var(--success)">Lunas: Rp <?= number_format($totalLunas) ?></span>
                    <span style="opacity:.4;margin-left:1rem">Total: Rp <?= number_format($totalSponsor) ?></span>
                </div>
            </div>
            <div style="padding:1.25rem 1.5rem">
            <?php if (empty($sponsors)): ?>
            <div style="text-align:center;opacity:.45;font-size:13px;padding:1.5rem">
                <i class="bi bi-award" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>Belum ada data sponsorship.
            </div>
            <?php else: ?>
            <?php foreach ($sponsors as $s): ?>
            <div style="background:var(--primary-dark);border-radius:8px;padding:.85rem 1rem;margin-bottom:.6rem;
                        display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                <div>
                    <strong style="font-size:13px"><?= htmlspecialchars($s['sponsor']) ?></strong>
                    <div style="font-size:12px;opacity:.5">
                        #<?= $s['id_booking'] ?> · <?= htmlspecialchars($s['nama_event'] ?? '') ?>
                        · Paket: <strong><?= htmlspecialchars($s['paket']) ?></strong>
                        · <span style="opacity:.6"><?= $s['id_sponsor'] ?></span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span style="color:var(--text-accent);font-weight:600;font-size:13px">
                        Rp <?= number_format($s['nilai']) ?>
                    </span>
                    <?php if ($s['status_bayar'] === 'lunas'): ?>
                    <span style="background:rgba(34,197,94,.2);color:#86efac;border:1px solid rgba(34,197,94,.3);
                                 border-radius:20px;font-size:11px;padding:2px 10px">
                        <i class="bi bi-check-circle me-1"></i>Lunas
                    </span>
                    <?php else: ?>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action"      value="settle_sponsor">
                        <input type="hidden" name="sponsor_id"  value="<?= htmlspecialchars($s['id_sponsor']) ?>">
                        <input type="hidden" name="current_tab" value="sponsor">
                        <button style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);
                                       color:#fca5a5;border-radius:20px;font-size:11px;padding:2px 10px;cursor:pointer">
                            <i class="bi bi-clock me-1"></i>Belum — Settle
                        </button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Hapus sponsor ini?')">
                        <input type="hidden" name="action"      value="delete_sponsor">
                        <input type="hidden" name="sponsor_id"  value="<?= htmlspecialchars($s['id_sponsor']) ?>">
                        <input type="hidden" name="current_tab" value="sponsor">
                        <button style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);
                                       color:#fca5a5;border-radius:6px;padding:2px 8px;font-size:11px;cursor:pointer">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>
    
<?php
$content = ob_get_clean();
require_once dirname(__DIR__, 2) . '../includes/navbarM04_EM.php';