<?php
require_once 'event_data.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_vendor') {
        $_SESSION['event_vendors'][] = [
            'id'       => count($_SESSION['event_vendors']) + 1,
            'nama'     => htmlspecialchars($_POST['nama']),
            'kategori' => htmlspecialchars($_POST['kategori']),
            'kontak'   => htmlspecialchars($_POST['kontak']),
            'rating'   => 0,
            'riwayat'  => 0,
        ];
        $msg = 'vendor_added';
    }
    if ($action === 'delete_vendor') {
        deleteVendor($_POST['vendor_id']);
        $msg = 'vendor_deleted';
    }
    if ($action === 'add_tiket') {
        $max = 0;
        foreach ($_SESSION['event_tiket'] as $t) {
            $n = (int)substr($t['id'], 4);
            if ($n > $max) $max = $n;
        }
        $_SESSION['event_tiket'][] = [
            'id'         => 'TKT-' . str_pad($max+1, 3,'0',STR_PAD_LEFT),
            'id_event'   => $_POST['id_event'],
            'tipe'       => htmlspecialchars($_POST['tipe']),
            'kuota'      => (int)$_POST['kuota'],
            'terjual'    => 0,
            'harga'      => (int)$_POST['harga'],
            'pendapatan' => 0,
        ];
        $msg = 'tiket_added';
    }
    if ($action === 'delete_tiket') {
        deleteTiket($_POST['tiket_id']);
        $msg = 'tiket_deleted';
    }
    if ($action === 'add_sponsor') {
        if (!isset($_SESSION['event_sponsorship'])) $_SESSION['event_sponsorship'] = [];
        $max = 0;
        foreach ($_SESSION['event_sponsorship'] as $s) {
            $n = (int)substr($s['id'], 4);
            if ($n > $max) $max = $n;
        }
        $_SESSION['event_sponsorship'][] = [
            'id'          => 'SPO-' . str_pad($max+1, 3,'0',STR_PAD_LEFT),
            'id_event'    => $_POST['id_event'],
            'sponsor'     => htmlspecialchars($_POST['sponsor']),
            'paket'       => htmlspecialchars($_POST['paket']),
            'nilai'       => (int)str_replace(['.',',' ,' '],'',$_POST['nilai']),
            'status_bayar'=> 'belum',
        ];
        $msg = 'sponsor_added';
    }
    if ($action === 'settle_sponsor') {
        foreach ($_SESSION['event_sponsorship'] as &$s) {
            if ($s['id'] === $_POST['sponsor_id']) { $s['status_bayar'] = 'lunas'; break; }
        }
        $msg = 'sponsor_settled';
    }
    if ($action === 'delete_sponsor') {
        deleteSponsor($_POST['sponsor_id']);
        $msg = 'sponsor_deleted';
    }

    $tab = $_POST['current_tab'] ?? 'vendor';
    header("Location: event_vendor_ticketing.php?tab=$tab&msg=$msg");
    exit;
}

$vendors     = getVendors();
$tiket       = $_SESSION['event_tiket'] ?? [];
$sponsorship = $_SESSION['event_sponsorship'] ?? [];
$approved    = array_filter(getPengajuan(), fn($p) => $p['status'] === 'approved');
$activeTab   = $_GET['tab'] ?? 'vendor';
$msg         = $_GET['msg'] ?? '';

$toastMap = [
    'vendor_added'    => ['Vendor berhasil ditambahkan.',  'success'],
    'vendor_deleted'  => ['Vendor berhasil dihapus.',      'danger'],
    'tiket_added'     => ['Tiket berhasil dibuat.',        'success'],
    'tiket_deleted'   => ['Tiket berhasil dihapus.',       'danger'],
    'sponsor_added'   => ['Sponsor berhasil ditambahkan.', 'success'],
    'sponsor_deleted' => ['Sponsor berhasil dihapus.',     'danger'],
    'sponsor_settled' => ['Settlement berhasil dicatat.',  'success'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISFO MALL - Database Event</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../public/asset/css/designSystem.css" rel="stylesheet">
    <style>
        body { background: var(--background); color: var(--text); font-family: var(--font-family); }
        .page-header { background: linear-gradient(135deg, var(--secondary-dark), var(--secondary)); padding: 1.75rem 2rem; border-radius: 12px; margin-bottom: 1.5rem; }
        .btn-back { background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); color: var(--text); border-radius: 8px; padding: .4rem 1rem; font-size: var(--label); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: background .2s; }
        .btn-back:hover { background: rgba(255,255,255,.15); color: var(--text); }
        .nav-tabs-custom { border-bottom: 1px solid rgba(255,255,255,.1); margin-bottom: 1.5rem; }
        .nav-tabs-custom .nav-link { color: rgba(245,247,250,.6); border: none; padding: .6rem 1.2rem; font-size: var(--label); font-weight: 500; border-radius: 8px 8px 0 0; }
        .nav-tabs-custom .nav-link.active { color: var(--accent); border-bottom: 2px solid var(--accent); background: transparent; }
        .nav-tabs-custom .nav-link:hover { color: var(--text); background: rgba(255,255,255,.05); }
        .card-section { background: var(--primary); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; }
        .card-section-header { padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.08); }
        .card-section-body { padding: 1.5rem; }
        .section-label { font-size: var(--label); text-transform: uppercase; letter-spacing: .08em; color: var(--accent); font-weight: 600; }
        .form-control, .form-select { background: var(--primary-dark); border: 1px solid rgba(255,255,255,.15); color: var(--text); border-radius: 8px; }
        .form-control:focus, .form-select:focus { background: var(--primary-dark); color: var(--text); border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0,212,216,.12); }
        .form-control::placeholder { color: rgba(245,247,250,.35); }
        .form-select option { background: var(--primary-dark); }
        .form-label { font-size: var(--label); font-weight: 500; }
        .table-dark-custom thead th { background: var(--primary-dark); color: var(--text); font-size: var(--label); font-weight: 600; border-color: rgba(255,255,255,.08); }
        .table-dark-custom td { border-color: rgba(255,255,255,.06); font-size: var(--label); vertical-align: middle; }
        .btn-accent { background: var(--accent); color: #021F42; font-weight: 600; border: none; border-radius: 8px; }
        .btn-accent:hover { opacity: .9; color: #021F42; }
        .btn-del { background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; border-radius: 6px; padding: 3px 10px; font-size: 11px; cursor: pointer; }
        .btn-del:hover { background: rgba(239,68,68,.3); }
        .star-rating { color: #f59e0b; }
        .ticket-chip { background: var(--primary-dark); border: 1px solid rgba(255,255,255,.1); border-radius: 10px; padding: 1rem; margin-bottom: .75rem; }
        .progress-custom { height: 6px; border-radius: 3px; background: rgba(255,255,255,.1); }
        .progress-bar-custom { height: 6px; border-radius: 3px; background: var(--accent); }
        .sponsor-row { background: var(--primary-dark); border-radius: 8px; padding: .85rem 1rem; margin-bottom: .6rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem; }
        .lunas-badge { background: rgba(34,197,94,.2); color: #86efac; border: 1px solid rgba(34,197,94,.3); border-radius: 20px; font-size: 11px; padding: 2px 10px; }
        .belum-badge { background: rgba(239,68,68,.2); color: #fca5a5; border: 1px solid rgba(239,68,68,.3); border-radius: 20px; font-size: 11px; padding: 2px 10px; }
        .toast-app { position: fixed; top: 1.5rem; right: 1.5rem; padding: .6rem 1.2rem; border-radius: 8px; font-size: var(--label); z-index: 9999; display: none; color: #fff; }
    </style>
</head>
<body>
<div class="toast-app" id="toastApp"></div>

<div class="container-fluid py-4 px-4">

    <div class="mb-3">
        <a href="index.php" class="btn-back"><i class="bi bi-arrow-left"></i> Dashboard Event</a>
    </div>

    <div class="page-header">
        <h4 class="mb-0 fw-bold"><i class="bi bi-people-fill me-2"></i>Vendor, Ticketing & Sponsorship</h4>
        <small style="opacity:.8">SISFO MALL</small>
    </div>

    <ul class="nav nav-tabs-custom">
        <li class="nav-item"><a class="nav-link <?= $activeTab==='vendor'?'active':'' ?>" href="?tab=vendor"><i class="bi bi-truck me-1"></i>Vendor <span class="ms-1 badge" style="background:rgba(255,255,255,.1)"><?= count($vendors) ?></span></a></li>
        <li class="nav-item"><a class="nav-link <?= $activeTab==='tiket'?'active':'' ?>"  href="?tab=tiket"><i class="bi bi-ticket-perforated me-1"></i>Ticketing <span class="ms-1 badge" style="background:rgba(255,255,255,.1)"><?= count($tiket) ?></span></a></li>
        <li class="nav-item"><a class="nav-link <?= $activeTab==='sponsor'?'active':'' ?>" href="?tab=sponsor"><i class="bi bi-award me-1"></i>Sponsorship <span class="ms-1 badge" style="background:rgba(255,255,255,.1)"><?= count($sponsorship) ?></span></a></li>
    </ul>

    <?php if ($activeTab === 'vendor'): ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-section">
                <div class="card-section-header"><span class="section-label"><i class="bi bi-grid me-1"></i>Database Vendor</span></div>
                <div class="table-responsive">
                <table class="table table-dark-custom mb-0">
                    <thead><tr><th>#</th><th>Nama</th><th>Kategori</th><th>Kontak</th><th>Rating</th><th>Event</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($vendors as $v): ?>
                    <tr>
                        <td style="opacity:.4"><?= $v['id'] ?></td>
                        <td><strong><?= $v['nama'] ?></strong></td>
                        <td><span style="background:rgba(22,126,128,.2);color:#67e8f9;border:1px solid rgba(22,126,128,.35);border-radius:20px;font-size:11px;padding:2px 10px"><?= $v['kategori'] ?></span></td>
                        <td><?= $v['kontak'] ?></td>
                        <td><span class="star-rating"><?= $v['rating']>0 ? str_repeat('★',round($v['rating'])).str_repeat('☆',5-round($v['rating'])) : '—' ?></span><?= $v['rating']>0 ? ' <small style="opacity:.6">'.$v['rating'].'</small>' : '' ?></td>
                        <td><?= $v['riwayat'] ?> event</td>
                        <td>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus vendor <?= addslashes($v['nama']) ?>?')">
                                <input type="hidden" name="action" value="delete_vendor">
                                <input type="hidden" name="vendor_id" value="<?= $v['id'] ?>">
                                <input type="hidden" name="current_tab" value="vendor">
                                <button type="submit" class="btn-del"><i class="bi bi-trash3"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-section">
                <div class="card-section-header"><span class="section-label"><i class="bi bi-plus-circle me-1"></i>Tambah Vendor</span></div>
                <div class="card-section-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_vendor">
                    <input type="hidden" name="current_tab" value="vendor">
                    <div class="mb-3"><label class="form-label">Nama Vendor</label><input type="text" name="nama" class="form-control" placeholder="Soundmax Pro" required></div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-select">
                            <?php foreach (['Sound System','Dekorasi','Lighting','Catering Sementara','Booth / Backdrop','Keamanan Event','Lainnya'] as $k): ?>
                            <option><?= $k ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Kontak</label><input type="text" name="kontak" class="form-control" placeholder="0812-xxxx-xxxx" required></div>
                    <button type="submit" class="btn btn-accent w-100"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
                </form>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($activeTab === 'tiket'): ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-section">
                <div class="card-section-header"><span class="section-label"><i class="bi bi-ticket-perforated me-1"></i>Setup Tiket per Event</span></div>
                <div class="card-section-body">
                <?php if (empty($tiket)): ?>
                <div style="text-align:center;opacity:.5;padding:2rem"><i class="bi bi-ticket fs-2 d-block mb-2"></i>Belum ada tiket.</div>
                <?php else:
                $tiketByEvent = [];
                foreach ($tiket as $t) $tiketByEvent[$t['id_event']][] = $t;
                foreach ($tiketByEvent as $ev_id => $tickets):
                    $ev = getPengajuanById($ev_id); ?>
                <div class="mb-3">
                    <div class="mb-2" style="font-size:var(--label);font-weight:600;color:var(--accent)">
                        <i class="bi bi-calendar-event me-1"></i>
                        <?= $ev_id ?> — <?= $ev ? $ev['tipe_event'].' · '.$ev['nama_area'] : 'Event' ?>
                    </div>
                    <?php foreach ($tickets as $t):
                        $pct = $t['kuota']>0 ? round($t['terjual']/$t['kuota']*100) : 0; ?>
                    <div class="ticket-chip">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span style="font-weight:600"><?= $t['tipe'] ?></span>
                            <div class="d-flex align-items-center gap-2">
                                <span style="color:var(--text-accent);font-weight:600"><?= $t['harga']>0?'Rp '.number_format($t['harga']):'GRATIS' ?></span>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus tiket <?= addslashes($t['tipe']) ?>?')">
                                    <input type="hidden" name="action" value="delete_tiket">
                                    <input type="hidden" name="tiket_id" value="<?= $t['id'] ?>">
                                    <input type="hidden" name="current_tab" value="tiket">
                                    <button type="submit" class="btn-del"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size:var(--caption);opacity:.7;margin-bottom:6px">
                            <span>Terjual: <?= number_format($t['terjual']) ?> / <?= number_format($t['kuota']) ?></span>
                            <span><?= $pct ?>%</span>
                        </div>
                        <div class="progress-custom"><div class="progress-bar-custom" style="width:<?= $pct ?>%"></div></div>
                        <?php if ($t['pendapatan']>0): ?><div class="mt-2" style="font-size:var(--caption);color:var(--success)"><i class="bi bi-currency-dollar me-1"></i>Pendapatan: Rp <?= number_format($t['pendapatan']) ?></div><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-section">
                <div class="card-section-header"><span class="section-label"><i class="bi bi-plus-circle me-1"></i>Buat Tiket Baru</span></div>
                <div class="card-section-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_tiket">
                    <input type="hidden" name="current_tab" value="tiket">
                    <div class="mb-3">
                        <label class="form-label">Event (Approved)</label>
                        <select name="id_event" class="form-select" required>
                            <option value="">-- Pilih Event --</option>
                            <?php foreach ($approved as $a): ?><option value="<?= $a['id'] ?>"><?= $a['id'] ?> — <?= $a['tipe_event'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Tipe Tiket</label>
                        <select name="tipe" class="form-select">
                            <?php foreach (['Gratis','Regular','Early Bird','VIP','VVIP'] as $tp): ?><option><?= $tp ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Kuota</label><input type="number" name="kuota" class="form-control" placeholder="500" min="1" required></div>
                    <div class="mb-3"><label class="form-label">Harga (0 = Gratis)</label><input type="number" name="harga" class="form-control" placeholder="0" min="0" required></div>
                    <button type="submit" class="btn btn-accent w-100"><i class="bi bi-ticket-perforated me-1"></i>Buat Tiket</button>
                </form>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($activeTab === 'sponsor'): ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-section">
                <div class="card-section-header d-flex justify-content-between align-items-center">
                    <span class="section-label"><i class="bi bi-award me-1"></i>Data Sponsorship</span>
                    <?php
                    $totalSponsor = array_sum(array_column($sponsorship,'nilai'));
                    $totalLunas   = array_sum(array_map(fn($s)=>$s['status_bayar']==='lunas'?$s['nilai']:0,$sponsorship)); ?>
                    <div style="font-size:var(--caption)">
                        <span style="color:var(--success)">Lunas: Rp <?= number_format($totalLunas) ?></span>
                        <span class="ms-3" style="opacity:.5">Total: Rp <?= number_format($totalSponsor) ?></span>
                    </div>
                </div>
                <div class="card-section-body">
                <?php if (empty($sponsorship)): ?>
                <div style="text-align:center;opacity:.5;padding:2rem"><i class="bi bi-award fs-2 d-block mb-2"></i>Belum ada data sponsorship.</div>
                <?php else: ?>
                <?php foreach ($sponsorship as $s): ?>
                <div class="sponsor-row">
                    <div>
                        <strong><?= $s['sponsor'] ?></strong>
                        <div style="font-size:var(--caption);opacity:.6"><?= $s['id_event'] ?> · Paket: <?= $s['paket'] ?> · <span style="opacity:.7"><?= $s['id'] ?></span></div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span style="color:var(--text-accent);font-weight:600">Rp <?= number_format($s['nilai']) ?></span>
                        <?php if ($s['status_bayar']==='lunas'): ?>
                            <span class="lunas-badge"><i class="bi bi-check-circle me-1"></i>Lunas</span>
                        <?php else: ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="settle_sponsor">
                                <input type="hidden" name="sponsor_id" value="<?= $s['id'] ?>">
                                <input type="hidden" name="current_tab" value="sponsor">
                                <button type="submit" class="belum-badge border-0" style="cursor:pointer;background:rgba(239,68,68,.15)">
                                    <i class="bi bi-clock me-1"></i>Belum — Settlement
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Hapus sponsor <?= addslashes($s['sponsor']) ?>?')">
                            <input type="hidden" name="action" value="delete_sponsor">
                            <input type="hidden" name="sponsor_id" value="<?= $s['id'] ?>">
                            <input type="hidden" name="current_tab" value="sponsor">
                            <button type="submit" class="btn-del"><i class="bi bi-trash3"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-section">
                <div class="card-section-header"><span class="section-label"><i class="bi bi-plus-circle me-1"></i>Tambah Sponsor</span></div>
                <div class="card-section-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add_sponsor">
                    <input type="hidden" name="current_tab" value="sponsor">
                    <div class="mb-3">
                        <label class="form-label">Event</label>
                        <select name="id_event" class="form-select" required>
                            <option value="">-- Pilih Event --</option>
                            <?php foreach ($approved as $a): ?><option value="<?= $a['id'] ?>"><?= $a['id'] ?> — <?= $a['tipe_event'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nama Sponsor</label><input type="text" name="sponsor" class="form-control" placeholder="Brand X" required></div>
                    <div class="mb-3"><label class="form-label">Paket</label>
                        <select name="paket" class="form-select">
                            <?php foreach (['Platinum','Gold','Silver','Media Partner'] as $pk): ?><option><?= $pk ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nilai (Rp)</label><input type="number" name="nilai" class="form-control" placeholder="10000000" min="0" required></div>
                    <button type="submit" class="btn btn-accent w-100"><i class="bi bi-award me-1"></i>Catat Sponsor</button>
                </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php if ($msg && isset($toastMap[$msg])): ?>
const t = document.getElementById('toastApp');
t.textContent = '<?= $toastMap[$msg][0] ?>';
t.style.background = '<?= $toastMap[$msg][1]==="success" ? "var(--success)" : "var(--danger)" ?>';
t.style.display = 'block';
setTimeout(() => t.style.display = 'none', 3000);
<?php endif; ?>
</script>
</body>
</html>