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

$page_title = 'Ticketing Event';
$page       = 'event_ticketing';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if  ($act === 'add_tiket') {
        addTiket($_POST['id_booking'], $_POST['tipe'], $_POST['kuota'], $_POST['harga']);
        $msg = 'tiket_added';
    } elseif ($act === 'delete_tiket') {
        deleteTiket($_POST['tiket_id']);
        $msg = 'tiket_deleted';
    }

    header("Location: event_ticketing.php?tab=$tab&msg=$msg");
    exit;
}

$msg = $_GET['msg'] ?? '';

$tiket       = getAllTiket();
$approved    = array_filter(getBookings('approved'), fn($b) => true);

$toastMap = [
    'tiket_added'     => ['Tiket berhasil dibuat.',         'var(--success)'],
    'tiket_deleted'   => ['Tiket berhasil dihapus.',        'var(--danger)'],
];

ob_start();
?>

<div class="col-lg-4">
        <div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:12px">
            <div style="padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.08)">
                <span style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:.08em">
                    <i class="bi bi-plus-circle me-1"></i>Buat Tiket Baru
                </span>
            </div>
            <div style="padding:1.5rem">
                <form method="POST">
                    <input type="hidden" name="action"      value="add_tiket">
                    <input type="hidden" name="current_tab" value="tiket">
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
                        <label style="font-size:13px;font-weight:500">Tipe Tiket</label>
                        <select name="tipe" class="form-select mt-1"
                                style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px">
                            <?php foreach (['Gratis','Regular','Early Bird','VIP','VVIP'] as $tp): ?>
                            <option><?= $tp ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label style="font-size:13px;font-weight:500">Kuota</label>
                        <input type="number" name="kuota" required min="1" placeholder="500"
                               class="form-control mt-1"
                               style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px">
                    </div>
                    <div class="mb-3">
                        <label style="font-size:13px;font-weight:500">Harga (0 = Gratis)</label>
                        <input type="number" name="harga" required min="0" placeholder="0"
                               class="form-control mt-1"
                               style="background:var(--primary-dark);border:1px solid rgba(255,255,255,.15);color:var(--text);border-radius:8px">
                    </div>
                    <button style="background:var(--accent);color:#021F42;font-weight:600;border:none;
                                   border-radius:8px;padding:.6rem 1rem;width:100%;cursor:pointer">
                        <i class="bi bi-ticket-perforated me-1"></i>Buat Tiket
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div style="background:var(--primary);border:1px solid rgba(255,255,255,.08);border-radius:12px">
            <div style="padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.08)">
                <span style="font-size:12px;color:var(--accent);font-weight:600;text-transform:uppercase;letter-spacing:.08em">
                    <i class="bi bi-ticket-perforated me-1"></i>Setup Tiket per Event
                </span>
            </div>
            <div style="padding:1.5rem">
            <?php if (empty($tiket)): ?>
            <div style="text-align:center;opacity:.45;font-size:13px;padding:1.5rem">
                <i class="bi bi-ticket" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>Belum ada tiket.
            </div>
            <?php else:
                // Group by event
                $tiketByEvent = [];
                foreach ($tiket as $t) $tiketByEvent[$t['id_booking']][] = $t;
                foreach ($tiketByEvent as $ev_id => $tickets):
                    $ev = $tickets[0]; // ambil nama event dari join
            ?>
            <div style="margin-bottom:1.5rem">
                <div style="font-size:13px;font-weight:600;color:var(--accent);margin-bottom:.75rem">
                    <i class="bi bi-calendar-event me-1"></i>
                    #<?= $ev_id ?> — <?= htmlspecialchars($ev['nama_event'] ?? 'Event') ?>
                </div>
                <?php foreach ($tickets as $t):
                    $pct = $t['kuota'] > 0 ? round($t['terjual'] / $t['kuota'] * 100) : 0;
                ?>
                <div style="background:var(--primary-dark);border-radius:10px;padding:1rem;margin-bottom:.75rem">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-weight:600;font-size:13px"><?= htmlspecialchars($t['tipe']) ?></span>
                        <div class="d-flex align-items-center gap-2">
                            <span style="color:var(--text-accent);font-weight:600;font-size:13px">
                                <?= $t['harga'] > 0 ? 'Rp ' . number_format($t['harga']) : 'GRATIS' ?>
                            </span>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus tiket ini?')">
                                <input type="hidden" name="action"      value="delete_tiket">
                                <input type="hidden" name="tiket_id"   value="<?= htmlspecialchars($t['id_tiket']) ?>">
                                <input type="hidden" name="current_tab" value="tiket">
                                <button style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);
                                               color:#fca5a5;border-radius:6px;padding:2px 8px;font-size:11px;cursor:pointer">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size:12px;opacity:.65;margin-bottom:6px">
                        <span>Terjual: <?= number_format($t['terjual']) ?> / <?= number_format($t['kuota']) ?></span>
                        <span><?= $pct ?>%</span>
                    </div>
                    <div style="height:6px;background:rgba(255,255,255,.1);border-radius:3px;overflow:hidden">
                        <div style="height:100%;background:var(--accent);width:<?= $pct ?>%;border-radius:3px;
                                    transition:width .3s"></div>
                    </div>
                    <?php if ($t['pendapatan'] > 0): ?>
                    <div style="font-size:12px;color:var(--success);margin-top:.4rem">
                        <i class="bi bi-currency-dollar me-1"></i>Pendapatan: Rp <?= number_format($t['pendapatan']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__, 2) . '../includes/navbarM04_EM.php';