<?php
session_start();
require_once '../../config/database.php';

if (!isset($conn)) $conn = null;

function sanitize(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

/* ── Ambil data lantai ───────────────────────────────────── */
$floors = [];
if ($conn) {
    $res    = $conn->query("SELECT * FROM floors ORDER BY id_floor ASC");
    $floors = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/* ── Filter ──────────────────────────────────────────────── */
$filterFloor = isset($_GET['floor']) ? (int)$_GET['floor']           : 0;
$filterJenis = isset($_GET['jenis']) ? sanitize($_GET['jenis'])       : '';

/* ── Ambil data fasilitas ────────────────────────────────── */
$facilities = [];
if ($conn) {
    $where  = ["1=1"];
    $params = [];
    $types  = '';

    if ($filterFloor > 0) {
        $where[]  = "f.id_floor = ?";
        $params[] = $filterFloor;
        $types   .= 'i';
    }
    if ($filterJenis !== '') {
        $where[]  = "f.jenis = ?";
        $params[] = $filterJenis;
        $types   .= 's';
    }

    $whereStr = implode(' AND ', $where);
    $sql = "SELECT f.*, fl.nama_lantai, fl.kode_lantai
            FROM facilities f
            LEFT JOIN floors fl ON f.id_floor = fl.id_floor
            WHERE $whereStr
            ORDER BY fl.id_floor ASC, f.jenis ASC, f.nama_fasilitas ASC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result     = $stmt->get_result();
        $facilities = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result     = $conn->query($sql);
        $facilities = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

/* ── Hitung per jenis (untuk summary) ───────────────────── */
$jenisList  = ['Toilet','ATM','Mushola','Lift','Eskalator','Parkir','Lainnya'];
$jenisCount = [];
$jenisIcon  = [
    'Toilet'    => 'bi-door-open',
    'ATM'       => 'bi-credit-card-2-front',
    'Mushola'   => 'bi-moon',
    'Lift'      => 'bi-arrow-up-square',
    'Eskalator' => 'bi-arrow-up-right-square',
    'Parkir'    => 'bi-p-square',
    'Lainnya'   => 'bi-three-dots',
];

if ($conn) {
    $res = $conn->query("SELECT jenis, COUNT(*) as total FROM facilities GROUP BY jenis");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $jenisCount[$row['jenis']] = (int)$row['total'];
        }
    }
}

/* ── Grup fasilitas per lantai ───────────────────────────── */
$grouped = [];
foreach ($facilities as $fac) {
    $key = $fac['nama_lantai'] ?? 'Tidak Diketahui';
    $grouped[$key][] = $fac;
}

/* ── Render konten ───────────────────────────────────────── */
ob_start();
?>

<!-- Summary Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
    <?php foreach ($jenisList as $jenis): ?>
    <?php $count = $jenisCount[$jenis] ?? 0; ?>
    <a href="?jenis=<?= urlencode($jenis) ?>&floor=<?= $filterFloor ?>"
       class="cs-card flex flex-col items-center text-center gap-1 hover:border-accent/50 transition-all cursor-pointer <?= $filterJenis === $jenis ? 'border-accent bg-accent/5' : '' ?>">
        <i class="bi <?= $jenisIcon[$jenis] ?> text-2xl <?= $filterJenis === $jenis ? 'text-accent' : 'text-text/50' ?>"></i>
        <p class="text-label font-semibold <?= $filterJenis === $jenis ? 'text-accent' : '' ?>"><?= $count ?></p>
        <p class="text-caption text-text/50"><?= $jenis ?></p>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filter Bar -->
<div class="cs-card">
    <form method="GET" action="" class="flex flex-wrap items-center gap-3">
        <select name="floor" class="cs-input !w-44 cursor-pointer">
            <option value="0">Semua Lantai</option>
            <?php foreach ($floors as $fl): ?>
            <option value="<?= $fl['id_floor'] ?>" <?= $filterFloor === (int)$fl['id_floor'] ? 'selected' : '' ?>>
                <?= sanitize($fl['nama_lantai']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="jenis" class="cs-input !w-44 cursor-pointer">
            <option value="">Semua Jenis</option>
            <?php foreach ($jenisList as $j): ?>
            <option value="<?= $j ?>" <?= $filterJenis === $j ? 'selected' : '' ?>><?= $j ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="cs-btn bg-accent text-background hover:brightness-110">
            <i class="bi bi-funnel"></i> Filter
        </button>
        <a href="fasilitas.php" class="cs-btn bg-transparent border border-border text-text/60 hover:bg-white/5">
            <i class="bi bi-arrow-counterclockwise"></i> Reset
        </a>
        <span class="text-caption text-text/40 ml-auto"><?= count($facilities) ?> fasilitas ditemukan</span>
    </form>
</div>

<!-- Daftar Fasilitas per Lantai -->
<?php if (empty($facilities)): ?>
<div class="cs-card flex flex-col items-center justify-center py-16 text-text/30">
    <i class="bi bi-geo-alt text-5xl mb-3"></i>
    <p class="text-body">Tidak ada fasilitas ditemukan</p>
    <p class="text-caption mt-1">Coba ubah filter lantai atau jenis fasilitas</p>
</div>
<?php else: ?>

<?php foreach ($grouped as $lantai => $facList): ?>
<div class="cs-card">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-8 h-8 rounded-md bg-accent/10 flex items-center justify-center">
            <i class="bi bi-layers text-accent"></i>
        </div>
        <h2 class="text-body font-semibold"><?= sanitize($lantai) ?></h2>
        <span class="text-caption text-text/40"><?= count($facList) ?> fasilitas</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($facList as $fac): ?>
        <?php
            $statusColor = match($fac['status']) {
                'Tersedia'        => 'text-success bg-success/10 border-success/20',
                'Tidak Tersedia'  => 'text-danger bg-danger/10 border-danger/20',
                'Maintenance'     => 'text-warning bg-warning/10 border-warning/20',
                default           => 'text-text/50 bg-white/5 border-border'
            };
        ?>
        <div class="bg-white/5 border border-border/50 rounded-lg p-4 hover:border-accent/40 transition-all">
            <div class="flex items-start justify-between mb-2">
                <div class="flex items-center gap-2">
                    <i class="bi <?= $jenisIcon[$fac['jenis']] ?? 'bi-geo-alt' ?> text-accent"></i>
                    <p class="text-label font-medium"><?= sanitize($fac['nama_fasilitas']) ?></p>
                </div>
                <span class="text-caption px-2 py-0.5 rounded-full border <?= $statusColor ?> flex-shrink-0 ml-2">
                    <?= sanitize($fac['status']) ?>
                </span>
            </div>
            <div class="flex items-start gap-2 mt-2">
                <i class="bi bi-geo-alt text-text/30 text-caption mt-0.5 flex-shrink-0"></i>
                <p class="text-caption text-text/50"><?= sanitize($fac['lokasi_detail'] ?? '-') ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();

$extraScript = '';

$pageTitle   = 'Fasilitas Umum — Mall ERP CS';
$currentMenu = 'fasilitas';

require_once '../../includes/layout_cs.php';
