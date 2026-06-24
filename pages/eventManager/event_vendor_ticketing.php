<?php
// pages/eventManager/event_vendor_ticketing.php
// PBI-M04-03-03 — Kelola Vendor, Ticketing Digital & Sponsorship Event
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

$page_title = 'Vendor · Tiket · Sponsorship';
$page       = 'event_vendor_ticketing';

$activeTab = $_GET['tab'] ?? 'vendor';
$msg       = '';

// ── Handle POST ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $tab = $_POST['current_tab'] ?? 'vendor';

    if ($act === 'add_vendor') {
        addVendor($_POST['id_booking'], $_POST['nama_vendor'], $_POST['kategori'], $_POST['kontak']);
        $msg = 'vendor_added';
    } elseif ($act === 'delete_vendor') {
        deleteVendor((int)$_POST['vendor_id']);
        $msg = 'vendor_deleted';
    } elseif ($act === 'add_tiket') {
        addTiket($_POST['id_booking'], $_POST['tipe'], $_POST['kuota'], $_POST['harga']);
        $msg = 'tiket_added';
    } elseif ($act === 'delete_tiket') {
        deleteTiket($_POST['tiket_id']);
        $msg = 'tiket_deleted';
    } elseif ($act === 'add_sponsor') {
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

// Data
$vendors     = getAllVendors();
$tiket       = getAllTiket();
$sponsors    = getAllSponsors();
$approved    = array_filter(getBookings('approved'), fn($b) => true);

$toastMap = [
    'vendor_added'    => ['Vendor berhasil ditambahkan.',   'var(--success)'],
    'vendor_deleted'  => ['Vendor berhasil dihapus.',       'var(--danger)'],
    'tiket_added'     => ['Tiket berhasil dibuat.',         'var(--success)'],
    'tiket_deleted'   => ['Tiket berhasil dihapus.',        'var(--danger)'],
    'sponsor_added'   => ['Sponsor berhasil ditambahkan.',  'var(--success)'],
    'sponsor_deleted' => ['Sponsor berhasil dihapus.',      'var(--danger)'],
    'sponsor_settled' => ['Settlement berhasil dicatat.',   'var(--success)'],
];

ob_start();
?>

<!-- Toast -->
<?php if ($msg && isset($toastMap[$msg])): ?>
<div id="toastMsg" style="position:fixed;top:1.5rem;right:1.5rem;color:#fff;padding:.6rem 1.4rem;
     border-radius:8px;font-size:13px;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.3);
     background:<?= $toastMap[$msg][1] ?>">
    <i class="bi bi-check-circle me-2"></i><?= $toastMap[$msg][0] ?>
</div>
<script>setTimeout(()=>document.getElementById('toastMsg')?.remove(),3000)</script>
<?php endif; ?>

<!-- TABS -->
<div style="border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:1.5rem;display:flex;gap:.25rem">
    <?php
    $tabs = [
        'vendor'  => ['icon'=>'bi-truck',                'label'=>'Vendor',      'count'=>count($vendors)],
        'tiket'   => ['icon'=>'bi-ticket-perforated',    'label'=>'Ticketing',   'count'=>count($tiket)],
        'sponsor' => ['icon'=>'bi-award',                'label'=>'Sponsorship', 'count'=>count($sponsors)],
    ];
    foreach ($tabs as $key => $t): ?>
    <a href="?tab=<?= $key ?>"
       style="padding:.6rem 1.2rem;font-size:13px;font-weight:500;text-decoration:none;border-radius:8px 8px 0 0;
              <?= $activeTab === $key
                  ? 'color:var(--accent);border-bottom:2px solid var(--accent);background:transparent'
                  : 'color:rgba(245,247,250,.5)' ?>">
        <i class="bi <?= $t['icon'] ?> me-1"></i><?= $t['label'] ?>
        <span style="background:rgba(255,255,255,.1);font-size:11px;padding:1px 8px;border-radius:20px;margin-left:4px">
            <?= $t['count'] ?>
        </span>
    </a>
    <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════════════════════
     TAB: VENDOR
═══════════════════════════════════════════════════ -->
<?php if ($activeTab === 'vendor'): ?>
<div class="row g-3">

    <!-- Tabel Vendor -->
    <div class="col-lg-8">
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

    <!-- Form Tambah Vendor -->
    <div class="col-lg-4">
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

<!-- ═══════════════════════════════════════════════
     TAB: TICKETING
═══════════════════════════════════════════════════ -->
<?php elseif ($activeTab === 'tiket'): ?>
<div class="row g-3">

    <!-- List Tiket -->
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

    <!-- Form Tiket -->
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

<!-- ═══════════════════════════════════════════════
     TAB: SPONSORSHIP
═══════════════════════════════════════════════════ -->
<?php elseif ($activeTab === 'sponsor'): ?>
<?php
$totalSponsor = array_sum(array_column($sponsors, 'nilai'));
$totalLunas   = array_sum(array_map(fn($s) => $s['status_bayar'] === 'lunas' ? $s['nilai'] : 0, $sponsors));
?>
<div class="row g-3">

    <!-- List Sponsor -->
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

    <!-- Form Sponsor -->
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
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once '../../includes/navbar.php';