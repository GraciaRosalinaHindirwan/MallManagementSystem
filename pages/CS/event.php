<?php
// require_once __DIR__ . '/../../auth/checkSession.php';
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($conn)) $conn = null;

function sanitize(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$filterStatus = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$filterTipe   = isset($_GET['tipe'])   ? sanitize($_GET['tipe'])   : '';
$search       = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$events = [];
if ($conn) {
    $where  = ["1=1"];
    $params = [];
    $types  = '';

    if ($filterStatus !== '') {
        $where[]  = "e.status = ?";
        $params[] = $filterStatus;
        $types   .= 's';
    }
    if ($filterTipe !== '') {
        $where[]  = "e.tipe_event = ?";
        $params[] = $filterTipe;
        $types   .= 's';
    }
    if ($search !== '') {
        $where[]  = "(e.nama_event LIKE ? OR a.nama_area LIKE ?)";
        $like     = "%$search%";
        $params   = array_merge($params, [$like, $like]);
        $types   .= 'ss';
    }

    $whereStr = implode(' AND ', $where);
    $sql = "SELECT e.*, a.nama_area, f.floor_number
            FROM `04_event_booking` e
            LEFT JOIN `04_event_areas` a ON e.id_area = a.id_area
            LEFT JOIN `01_floors` f ON a.floor_id = f.id_floors
            WHERE $whereStr
            ORDER BY
                CASE e.status
                    WHEN 'approved' THEN 1
                    WHEN 'pending'  THEN 2
                    ELSE 3
                END,
                e.tanggal_mulai ASC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $events = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result = $conn->query($sql);
        $events = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

$countAll      = 0;
$countApproved = 0;
$countPending  = 0;
$countOther    = 0;
if ($conn) {
    $res = $conn->query("SELECT status, COUNT(*) as total FROM `04_event_booking` GROUP BY status");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $countAll += $row['total'];
            if ($row['status'] === 'approved') $countApproved = $row['total'];
            if ($row['status'] === 'pending')  $countPending  = $row['total'];
            if (!in_array($row['status'], ['approved','pending'])) $countOther += $row['total'];
        }
    }
}

$tipeList = ['Bazar / Pameran','Launching Produk','Konser / Hiburan','Lainnya'];

ob_start();
?>

<!-- Ringkasan Status -->
<div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:16px; margin-bottom:20px;">
    <a href="event.php" style="background:<?= ($filterStatus===''&&$filterTipe===''&&$search==='') ? 'rgba(0,212,216,0.05)' : 'rgba(255,255,255,0.05)' ?>; border:1px solid <?= ($filterStatus===''&&$filterTipe===''&&$search==='') ? 'rgba(0,212,216,0.5)' : 'rgba(255,255,255,0.1)' ?>; border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:16px; text-decoration:none; transition:all 0.2s;">
        <div style="width:40px; height:40px; border-radius:10px; background:rgba(0,212,216,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i class="fa-solid fa-calendar" style="color:var(--accent,#00D4D8);"></i>
        </div>
        <div>
            <p style="font-size:22px; font-weight:700; color:var(--text);"><?= $countAll ?></p>
            <p style="font-size:12px; color:rgba(245,247,250,0.5);">Total Event</p>
        </div>
    </a>
    <a href="?status=approved" style="background:<?= $filterStatus==='approved' ? 'rgba(74,222,128,0.05)' : 'rgba(255,255,255,0.05)' ?>; border:1px solid <?= $filterStatus==='approved' ? 'rgba(74,222,128,0.4)' : 'rgba(255,255,255,0.1)' ?>; border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:16px; text-decoration:none; transition:all 0.2s;">
        <div style="width:40px; height:40px; border-radius:10px; background:rgba(74,222,128,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i class="fa-solid fa-circle-check" style="color:#4ade80;"></i>
        </div>
        <div>
            <p style="font-size:22px; font-weight:700; color:#4ade80;"><?= $countApproved ?></p>
            <p style="font-size:12px; color:rgba(245,247,250,0.5);">Disetujui</p>
        </div>
    </a>
    <a href="?status=pending" style="background:<?= $filterStatus==='pending' ? 'rgba(251,191,36,0.05)' : 'rgba(255,255,255,0.05)' ?>; border:1px solid <?= $filterStatus==='pending' ? 'rgba(251,191,36,0.4)' : 'rgba(255,255,255,0.1)' ?>; border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:16px; text-decoration:none; transition:all 0.2s;">
        <div style="width:40px; height:40px; border-radius:10px; background:rgba(251,191,36,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i class="fa-solid fa-clock" style="color:#fbbf24;"></i>
        </div>
        <div>
            <p style="font-size:22px; font-weight:700; color:#fbbf24;"><?= $countPending ?></p>
            <p style="font-size:12px; color:rgba(245,247,250,0.5);">Pending</p>
        </div>
    </a>
    <a href="?status=rejected" style="background:<?= $filterStatus==='rejected' ? 'rgba(255,255,255,0.05)' : 'rgba(255,255,255,0.03)' ?>; border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:16px; text-decoration:none; transition:all 0.2s;">
        <div style="width:40px; height:40px; border-radius:10px; background:rgba(255,255,255,0.05); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i class="fa-solid fa-circle-xmark" style="color:rgba(245,247,250,0.3);"></i>
        </div>
        <div>
            <p style="font-size:22px; font-weight:700; color:rgba(245,247,250,0.4);"><?= $countOther ?></p>
            <p style="font-size:12px; color:rgba(245,247,250,0.5);">Lainnya</p>
        </div>
    </a>
</div>

<!-- Filter -->
<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="" style="display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
        <div style="position:relative; flex:1; min-width:200px;">
            <i class="fa-solid fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:rgba(245,247,250,0.4);"></i>
            <input type="text" name="search" value="<?= $search ?>"
                   placeholder="Cari nama event atau area..."
                   style="width:100%; padding:10px 14px 10px 36px; background:var(--primary-dark,#082A53); border:1px solid rgba(255,255,255,0.12); border-radius:8px; color:var(--text); font-size:14px; outline:none; box-sizing:border-box;" />
        </div>
        <select name="status" style="padding:10px 14px; background:var(--primary-dark,#082A53); border:1px solid rgba(255,255,255,0.12); border-radius:8px; color:var(--text); font-size:14px; outline:none;">
            <option value="">Semua Status</option>
            <option value="approved" <?= $filterStatus==='approved' ? 'selected' : '' ?>>Disetujui</option>
            <option value="pending"  <?= $filterStatus==='pending'  ? 'selected' : '' ?>>Pending</option>
            <option value="rejected" <?= $filterStatus==='rejected' ? 'selected' : '' ?>>Ditolak</option>
        </select>
        <select name="tipe" style="padding:10px 14px; background:var(--primary-dark,#082A53); border:1px solid rgba(255,255,255,0.12); border-radius:8px; color:var(--text); font-size:14px; outline:none;">
            <option value="">Semua Tipe</option>
            <?php foreach ($tipeList as $tp): ?>
            <option value="<?= $tp ?>" <?= $filterTipe===$tp ? 'selected' : '' ?>><?= $tp ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
        <a href="event.php" class="btn" style="background:transparent; border:1px solid rgba(255,255,255,0.2); color:rgba(245,247,250,0.6);">
            <i class="fa-solid fa-rotate-left"></i> Reset
        </a>
    </form>
</div>

<!-- Daftar Event -->
<div class="card">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <div>
            <h2 class="card-title" style="margin-bottom:4px;">Jadwal Event</h2>
            <p style="font-size:13px; color:rgba(245,247,250,0.5);"><?= count($events) ?> event ditemukan</p>
        </div>
    </div>

    <?php if (empty($events)): ?>
    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:64px 0; color:rgba(245,247,250,0.3);">
        <i class="fa-solid fa-calendar-xmark" style="font-size:48px; margin-bottom:12px;"></i>
        <p style="font-size:16px;">Tidak ada event ditemukan</p>
        <p style="font-size:13px; margin-top:4px;">Coba ubah filter atau kata kunci pencarian</p>
    </div>
    <?php else: ?>
    <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($events as $ev): ?>
        <?php
            $statusStyle = match($ev['status']) {
                'approved' => ['color:#4ade80; background:rgba(74,222,128,0.1); border-color:rgba(74,222,128,0.2)', 'fa-circle-check', 'rgba(74,222,128,0.4)'],
                'pending'  => ['color:#fbbf24; background:rgba(251,191,36,0.1); border-color:rgba(251,191,36,0.2)',  'fa-clock',        'rgba(251,191,36,0.4)'],
                default    => ['color:rgba(245,247,250,0.4); background:rgba(255,255,255,0.05); border-color:rgba(255,255,255,0.1)', 'fa-circle-xmark', 'rgba(255,255,255,0.1)'],
            };
            $tglMulai   = date('d M Y', strtotime($ev['tanggal_mulai']));
            $tglSelesai = date('d M Y', strtotime($ev['tanggal_selesai']));
            $jamMulai   = date('H:i', strtotime($ev['tanggal_mulai']));
            $jamSelesai = date('H:i', strtotime($ev['tanggal_selesai']));
            $samaTgl    = $tglMulai === $tglSelesai;
            $statusLabel = match($ev['status']) {
                'approved' => 'Disetujui',
                'pending'  => 'Pending',
                'rejected' => 'Ditolak',
                default    => ucfirst($ev['status'])
            };
        ?>
        <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-left:4px solid <?= $statusStyle[2] ?>; border-radius:10px; padding:16px 20px; cursor:pointer; transition:all 0.2s;"
             onclick="openDetail(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES) ?>)"
             onmouseover="this.style.borderColor='rgba(0,212,216,0.3)'"
             onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'">
            <div style="display:flex; align-items:start; justify-content:space-between; gap:12px;">
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                        <span style="font-size:12px; padding:2px 10px; border-radius:20px; border:1px solid; <?= $statusStyle[0] ?>">
                            <i class="fa-solid <?= $statusStyle[1] ?>" style="margin-right:4px;"></i><?= $statusLabel ?>
                        </span>
                        <span style="font-size:12px; padding:2px 10px; border-radius:20px; background:rgba(255,255,255,0.05); color:rgba(245,247,250,0.4);">
                            <?= sanitize($ev['tipe_event']) ?>
                        </span>
                    </div>
                    <p style="font-size:15px; font-weight:600; margin-bottom:4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= sanitize($ev['nama_event']) ?></p>
                </div>
            </div>
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:16px; margin-top:12px; padding-top:12px; border-top:1px solid rgba(255,255,255,0.08);">
                <div style="display:flex; align-items:center; gap:6px; font-size:13px; color:rgba(245,247,250,0.6);">
                    <i class="fa-solid fa-calendar"></i>
                    <span><?= $samaTgl ? $tglMulai : "$tglMulai – $tglSelesai" ?></span>
                </div>
                <div style="display:flex; align-items:center; gap:6px; font-size:13px; color:rgba(245,247,250,0.6);">
                    <i class="fa-solid fa-clock"></i>
                    <span><?= $jamMulai ?> – <?= $jamSelesai ?> WIB</span>
                </div>
                <div style="display:flex; align-items:center; gap:6px; font-size:13px; color:rgba(245,247,250,0.6);">
                    <i class="fa-solid fa-location-dot"></i>
                    <span><?= sanitize($ev['nama_area'] ?? '-') ?><?= $ev['floor_number'] ? ' · Lantai '.$ev['floor_number'] : '' ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Detail -->
<div id="detailModal" style="display:none; position:fixed; inset:0; z-index:999; align-items:center; justify-content:center; background:rgba(2,31,66,0.85); backdrop-filter:blur(4px);">
    <div style="background:#102F5C; border:1px solid rgba(0,212,216,0.3); border-radius:16px; padding:28px; width:100%; max-width:480px; margin:16px; box-shadow:0 8px 32px rgba(0,0,0,0.45);">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
            <h3 style="font-size:18px; font-weight:600;">Detail Event</h3>
            <button onclick="closeDetail()" style="background:transparent; border:none; color:rgba(245,247,250,0.4); font-size:18px; cursor:pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div style="display:flex; flex-direction:column; gap:16px;">
            <div>
                <div style="display:flex; gap:8px; margin-bottom:8px;" id="m-badges"></div>
                <p style="font-size:17px; font-weight:700;" id="m-nama"></p>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div style="background:rgba(255,255,255,0.05); border-radius:10px; padding:14px;">
                    <p style="font-size:12px; color:rgba(245,247,250,0.4); margin-bottom:4px;"><i class="fa-solid fa-calendar" style="margin-right:4px;"></i>Tanggal</p>
                    <p style="font-size:14px; font-weight:500;" id="m-tanggal"></p>
                </div>
                <div style="background:rgba(255,255,255,0.05); border-radius:10px; padding:14px;">
                    <p style="font-size:12px; color:rgba(245,247,250,0.4); margin-bottom:4px;"><i class="fa-solid fa-clock" style="margin-right:4px;"></i>Jam</p>
                    <p style="font-size:14px; font-weight:500;" id="m-jam"></p>
                </div>
                <div style="background:rgba(255,255,255,0.05); border-radius:10px; padding:14px; grid-column:1/-1;">
                    <p style="font-size:12px; color:rgba(245,247,250,0.4); margin-bottom:4px;"><i class="fa-solid fa-location-dot" style="margin-right:4px;"></i>Lokasi</p>
                    <p style="font-size:14px; font-weight:500;" id="m-lokasi"></p>
                </div>
                <div style="background:rgba(255,255,255,0.05); border-radius:10px; padding:14px; grid-column:1/-1;">
                    <p style="font-size:12px; color:rgba(245,247,250,0.4); margin-bottom:4px;"><i class="fa-solid fa-users" style="margin-right:4px;"></i>Estimasi Pengunjung</p>
                    <p style="font-size:14px; font-weight:500;" id="m-pengunjung"></p>
                </div>
            </div>
        </div>
        <button onclick="closeDetail()" class="btn btn-primary" style="width:100%; margin-top:20px; justify-content:center;">
            Tutup
        </button>
    </div>
</div>

<?php
$content = ob_get_clean();

ob_start();
?>
<script>
const modal = document.getElementById('detailModal');

function openDetail(ev) {
    const statusLabel = { approved: 'Disetujui', pending: 'Pending', rejected: 'Ditolak' };
    const statusColor = {
        approved: 'color:#4ade80; background:rgba(74,222,128,0.1); border:1px solid rgba(74,222,128,0.2)',
        pending:  'color:#fbbf24; background:rgba(251,191,36,0.1); border:1px solid rgba(251,191,36,0.2)',
        rejected: 'color:rgba(245,247,250,0.4); background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1)',
    };

    const tglMulai   = new Date(ev.tanggal_mulai).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
    const tglSelesai = new Date(ev.tanggal_selesai).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
    const jamMulai   = ev.tanggal_mulai.substring(11,16);
    const jamSelesai = ev.tanggal_selesai.substring(11,16);
    const samaTgl    = ev.tanggal_mulai.substring(0,10) === ev.tanggal_selesai.substring(0,10);

    document.getElementById('m-badges').innerHTML =
        `<span style="font-size:12px; padding:2px 10px; border-radius:20px; ${statusColor[ev.status] || ''}">${statusLabel[ev.status] || ev.status}</span>
         <span style="font-size:12px; padding:2px 10px; border-radius:20px; background:rgba(255,255,255,0.05); color:rgba(245,247,250,0.4);">${ev.tipe_event}</span>`;

    document.getElementById('m-nama').textContent       = ev.nama_event;
    document.getElementById('m-tanggal').textContent    = samaTgl ? tglMulai : `${tglMulai} – ${tglSelesai}`;
    document.getElementById('m-jam').textContent        = `${jamMulai} – ${jamSelesai} WIB`;
    document.getElementById('m-lokasi').textContent     = (ev.nama_area || '-') + (ev.floor_number ? ' · Lantai ' + ev.floor_number : '');
    document.getElementById('m-pengunjung').textContent = ev.estimasi_pengunjung ? ev.estimasi_pengunjung + ' orang' : '-';

    modal.style.display = 'flex';
}

function closeDetail() { modal.style.display = 'none'; }
modal.addEventListener('click', e => { if (e.target === modal) closeDetail(); });
</script>
<?php
$extraScript = ob_get_clean();

$page_title   = 'Jadwal Event';
$current_page = 'event';
require_once __DIR__ . '/../../includes/navbarM05.php';