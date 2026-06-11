<?php
session_start();
require_once '../../config/database.php';

if (!isset($conn)) $conn = null;

function sanitize(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

/* ── Filter ──────────────────────────────────────────────── */
$filterStatus = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$filterTipe   = isset($_GET['tipe'])   ? sanitize($_GET['tipe'])   : '';
$search       = isset($_GET['search']) ? sanitize($_GET['search']) : '';

/* ── Ambil data event ────────────────────────────────────── */
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
        $where[]  = "(e.nama_event LIKE ? OR e.lokasi LIKE ? OR e.deskripsi LIKE ?)";
        $like     = "%$search%";
        $params   = array_merge($params, [$like, $like, $like]);
        $types   .= 'sss';
    }

    $whereStr = implode(' AND ', $where);
    $sql = "SELECT e.*, fl.nama_lantai
            FROM events e
            LEFT JOIN floors fl ON e.id_floor = fl.id_floor
            WHERE $whereStr
            ORDER BY
                CASE e.status WHEN 'Berlangsung' THEN 1 WHEN 'Akan Datang' THEN 2 ELSE 3 END,
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

/* ── Hitung summary ──────────────────────────────────────── */
$countAll        = 0;
$countBerlangsung = 0;
$countAkanDatang  = 0;
$countSelesai     = 0;
if ($conn) {
    $res = $conn->query("SELECT status, COUNT(*) as total FROM events GROUP BY status");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $countAll += $row['total'];
            if ($row['status'] === 'Berlangsung')  $countBerlangsung = $row['total'];
            if ($row['status'] === 'Akan Datang')  $countAkanDatang  = $row['total'];
            if ($row['status'] === 'Selesai')       $countSelesai     = $row['total'];
        }
    }
}

$tipeList = ['Pameran','Hiburan','Bazaar','Promosi','Lainnya'];

/* ── Render konten ───────────────────────────────────────── */
ob_start();
?>

<!-- Summary -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <a href="event.php" class="cs-card flex items-center gap-4 hover:border-accent/50 transition-all <?= $filterStatus === '' && $filterTipe === '' && $search === '' ? 'border-accent bg-accent/5' : '' ?>">
        <div class="w-10 h-10 rounded-md bg-accent/10 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-calendar3 text-accent text-lg"></i>
        </div>
        <div>
            <p class="text-h2 font-bold text-text leading-none"><?= $countAll ?></p>
            <p class="text-caption text-text/50 mt-0.5">Total Event</p>
        </div>
    </a>
    <a href="?status=Berlangsung" class="cs-card flex items-center gap-4 hover:border-success/50 transition-all <?= $filterStatus === 'Berlangsung' ? 'border-success bg-success/5' : '' ?>">
        <div class="w-10 h-10 rounded-md bg-success/10 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-play-circle text-success text-lg"></i>
        </div>
        <div>
            <p class="text-h2 font-bold text-success leading-none"><?= $countBerlangsung ?></p>
            <p class="text-caption text-text/50 mt-0.5">Berlangsung</p>
        </div>
    </a>
    <a href="?status=Akan Datang" class="cs-card flex items-center gap-4 hover:border-accent/50 transition-all <?= $filterStatus === 'Akan Datang' ? 'border-accent bg-accent/5' : '' ?>">
        <div class="w-10 h-10 rounded-md bg-accent/10 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-calendar-plus text-accent text-lg"></i>
        </div>
        <div>
            <p class="text-h2 font-bold text-accent leading-none"><?= $countAkanDatang ?></p>
            <p class="text-caption text-text/50 mt-0.5">Akan Datang</p>
        </div>
    </a>
    <a href="?status=Selesai" class="cs-card flex items-center gap-4 hover:border-white/20 transition-all <?= $filterStatus === 'Selesai' ? 'border-white/30 bg-white/5' : '' ?>">
        <div class="w-10 h-10 rounded-md bg-white/5 flex items-center justify-center flex-shrink-0">
            <i class="bi bi-check-circle text-text/40 text-lg"></i>
        </div>
        <div>
            <p class="text-h2 font-bold text-text/40 leading-none"><?= $countSelesai ?></p>
            <p class="text-caption text-text/50 mt-0.5">Selesai</p>
        </div>
    </a>
</div>

<!-- Filter Bar -->
<div class="cs-card">
    <form method="GET" action="" class="flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-48">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-text/40"></i>
            <input type="text" name="search" value="<?= $search ?>"
                   placeholder="Cari nama event, lokasi..."
                   class="cs-input pl-9" />
        </div>
        <select name="status" class="cs-input !w-44 cursor-pointer">
            <option value="">Semua Status</option>
            <option value="Berlangsung"  <?= $filterStatus === 'Berlangsung'  ? 'selected' : '' ?>>Berlangsung</option>
            <option value="Akan Datang"  <?= $filterStatus === 'Akan Datang'  ? 'selected' : '' ?>>Akan Datang</option>
            <option value="Selesai"      <?= $filterStatus === 'Selesai'      ? 'selected' : '' ?>>Selesai</option>
        </select>
        <select name="tipe" class="cs-input !w-44 cursor-pointer">
            <option value="">Semua Tipe</option>
            <?php foreach ($tipeList as $tp): ?>
            <option value="<?= $tp ?>" <?= $filterTipe === $tp ? 'selected' : '' ?>><?= $tp ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="cs-btn bg-accent text-background hover:brightness-110">
            <i class="bi bi-funnel"></i> Filter
        </button>
        <a href="event.php" class="cs-btn bg-transparent border border-border text-text/60 hover:bg-white/5">
            <i class="bi bi-arrow-counterclockwise"></i> Reset
        </a>
    </form>
</div>

<!-- Daftar Event -->
<div class="cs-card">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-body font-semibold">Jadwal Event</h2>
            <p class="text-caption text-text/50"><?= count($events) ?> event ditemukan</p>
        </div>
    </div>

    <?php if (empty($events)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-text/30">
        <i class="bi bi-calendar-x text-5xl mb-3"></i>
        <p class="text-body">Tidak ada event ditemukan</p>
        <p class="text-caption mt-1">Coba ubah filter atau kata kunci pencarian</p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($events as $ev): ?>
        <?php
            $statusStyle = match($ev['status']) {
                'Berlangsung' => ['bg-success/10 text-success border-success/20', 'bi-play-circle', 'border-l-success'],
                'Akan Datang' => ['bg-accent/10 text-accent border-accent/20',    'bi-clock',        'border-l-accent'],
                default       => ['bg-white/5 text-text/40 border-border',        'bi-check-circle', 'border-l-border'],
            };
            $tglMulai   = date('d M Y', strtotime($ev['tanggal_mulai']));
            $tglSelesai = date('d M Y', strtotime($ev['tanggal_selesai']));
            $samaDari   = $tglMulai === $tglSelesai;
        ?>
        <div class="bg-surface-raised border border-border border-l-4 <?= $statusStyle[2] ?> rounded-lg p-4 hover:border-accent/30 transition-all cursor-pointer"
             onclick="openDetail(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES) ?>)">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-caption px-2 py-0.5 rounded-full border <?= $statusStyle[0] ?>">
                            <i class="bi <?= $statusStyle[1] ?> mr-1"></i><?= sanitize($ev['status']) ?>
                        </span>
                        <span class="text-caption text-text/40 px-2 py-0.5 rounded-full bg-white/5">
                            <?= sanitize($ev['tipe_event']) ?>
                        </span>
                    </div>
                    <p class="text-label font-semibold text-text mb-1 truncate"><?= sanitize($ev['nama_event']) ?></p>
                    <p class="text-caption text-text/50 line-clamp-1"><?= sanitize($ev['deskripsi']) ?></p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-4 mt-3 border-t border-border/50 pt-3">
                <div class="flex items-center gap-1.5 text-caption text-text/60">
                    <i class="bi bi-calendar3"></i>
                    <span><?= $samaDari ? $tglMulai : "$tglMulai – $tglSelesai" ?></span>
                </div>
                <div class="flex items-center gap-1.5 text-caption text-text/60">
                    <i class="bi bi-clock"></i>
                    <span><?= date('H:i', strtotime($ev['jam_mulai'])) ?> – <?= date('H:i', strtotime($ev['jam_selesai'])) ?> WIB</span>
                </div>
                <div class="flex items-center gap-1.5 text-caption text-text/60">
                    <i class="bi bi-geo-alt"></i>
                    <span><?= sanitize($ev['lokasi']) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Detail Event -->
<div id="detailModal" class="fixed inset-0 z-50 hidden items-center justify-center"
     style="background: rgba(2,31,66,0.85); backdrop-filter: blur(4px);">
    <div class="bg-surface-raised border border-border-strong rounded-xl p-6 w-full max-w-lg mx-4 shadow-lg">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-subheading font-semibold">Detail Event</h3>
            <button onclick="closeDetail()" class="text-text/40 hover:text-text">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="space-y-4">
            <div>
                <div class="flex items-center gap-2 mb-2" id="m-badges"></div>
                <p class="text-body font-bold" id="m-nama"></p>
            </div>
            <p class="text-label text-text/70 bg-white/5 rounded-lg p-3" id="m-deskripsi"></p>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white/5 rounded-lg p-3">
                    <p class="text-caption text-text/40 mb-1"><i class="bi bi-calendar3 mr-1"></i>Tanggal</p>
                    <p class="text-label font-medium" id="m-tanggal"></p>
                </div>
                <div class="bg-white/5 rounded-lg p-3">
                    <p class="text-caption text-text/40 mb-1"><i class="bi bi-clock mr-1"></i>Jam</p>
                    <p class="text-label font-medium" id="m-jam"></p>
                </div>
                <div class="bg-white/5 rounded-lg p-3 col-span-2">
                    <p class="text-caption text-text/40 mb-1"><i class="bi bi-geo-alt mr-1"></i>Lokasi</p>
                    <p class="text-label font-medium" id="m-lokasi"></p>
                </div>
            </div>
        </div>
        <button onclick="closeDetail()" class="mt-5 w-full cs-btn bg-accent text-background hover:brightness-110">
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
    const statusColor = {
        'Berlangsung': 'bg-green-500/10 text-green-400 border border-green-400/20',
        'Akan Datang': 'bg-cyan-500/10 text-cyan-400 border border-cyan-400/20',
        'Selesai':     'bg-white/5 text-white/40 border border-white/10',
    };

    const tglMulai   = new Date(ev.tanggal_mulai).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
    const tglSelesai = new Date(ev.tanggal_selesai).toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
    const samaTgl    = ev.tanggal_mulai === ev.tanggal_selesai;

    document.getElementById('m-badges').innerHTML =
        `<span class="text-caption px-2 py-0.5 rounded-full ${statusColor[ev.status] || ''}">${ev.status}</span>
         <span class="text-caption px-2 py-0.5 rounded-full bg-white/5 text-white/40">${ev.tipe_event}</span>`;

    document.getElementById('m-nama').textContent      = ev.nama_event;
    document.getElementById('m-deskripsi').textContent = ev.deskripsi || '-';
    document.getElementById('m-tanggal').textContent   = samaTgl ? tglMulai : `${tglMulai} – ${tglSelesai}`;
    document.getElementById('m-jam').textContent       = ev.jam_mulai.substring(0,5) + ' – ' + ev.jam_selesai.substring(0,5) + ' WIB';
    document.getElementById('m-lokasi').textContent    = ev.lokasi || '-';

    modal.style.display = 'flex';
}

function closeDetail() { modal.style.display = 'none'; }

modal.addEventListener('click', e => { if (e.target === modal) closeDetail(); });
</script>
<?php
$extraScript = ob_get_clean();

$pageTitle   = 'Jadwal Event — Mall ERP CS';
$currentMenu = 'event';

require_once '../../includes/layout_cs.php';
