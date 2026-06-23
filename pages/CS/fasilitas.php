<?php
// require_once __DIR__ . '/../../auth/checkSession.php';
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($conn)) $conn = null;

function sanitize(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

// $floors = [];
// if ($conn) {
//     $res    = $conn->query("SELECT * FROM `01_floors` ORDER BY id_floors ASC");
//     $floors = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
// }

$filterJenis = isset($_GET['jenis']) ? sanitize($_GET['jenis']) : '';

$facilities = [];
if ($conn) {
    $where  = ["1=1"];
    $params = [];
    $types  = '';

    if ($filterJenis !== '') {
        $where[]  = "category = ?";
        $params[] = $filterJenis;
        $types   .= 's';
    }

    $sql = "SELECT *
        FROM `03_assets`
        ORDER BY category ASC, name ASC";

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

$jenisList = ['Toilet','ATM','Mushola','Lift','Eskalator','Parkir','Lainnya'];
$jenisCount = [];
$jenisIcon  = [
    'Toilet'    => 'fa-restroom',
    'ATM'       => 'fa-credit-card',
    'Mushola'   => 'fa-moon',
    'Lift'      => 'fa-elevator',
    'Eskalator' => 'fa-stairs',
    'Parkir'    => 'fa-square-parking',
    'Lainnya'   => 'fa-ellipsis',
];

if ($conn) {
    $res = $conn->query("SELECT category, COUNT(*) as total FROM `03_assets` GROUP BY category");

while ($row = $res->fetch_assoc()) {
    $jenisCount[$row['category']] = (int)$row['total'];
}
    }

$grouped = [];
foreach ($facilities as $fac) {
    $key = $fac['category'];
    $grouped[$key][] = $fac;
}

ob_start();
?>

<!-- Ringkasan Jenis Fasilitas -->
<div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:12px; margin-bottom:20px;">
    <?php foreach ($jenisList as $jenis): ?>
    <?php $count = $jenisCount[$jenis] ?? 0; $isActive = $filterJenis === $jenis; ?>
    <a href="?jenis=<?= urlencode($jenis) ?>"
       style="background:<?= $isActive ? 'rgba(0,212,216,0.1)' : 'rgba(255,255,255,0.05)' ?>; border:1px solid <?= $isActive ? 'rgba(0,212,216,0.5)' : 'rgba(255,255,255,0.1)' ?>; border-radius:12px; padding:16px 12px; text-align:center; text-decoration:none; display:flex; flex-direction:column; align-items:center; gap:6px; transition:all 0.2s;"
       onmouseover="this.style.borderColor='rgba(0,212,216,0.4)'"
       onmouseout="this.style.borderColor='<?= $isActive ? 'rgba(0,212,216,0.5)' : 'rgba(255,255,255,0.1)' ?>'">
        <i class="fa-solid <?= $jenisIcon[$jenis] ?>" style="font-size:22px; color:<?= $isActive ? 'var(--accent,#00D4D8)' : 'rgba(245,247,250,0.4)' ?>;"></i>
        <p style="font-size:18px; font-weight:700; color:<?= $isActive ? 'var(--accent,#00D4D8)' : 'var(--text)' ?>;"><?= $count ?></p>
        <p style="font-size:12px; color:rgba(245,247,250,0.5);"><?= $jenis ?></p>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="" style="display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
        <select name="jenis" style="padding:10px 14px; background:var(--primary-dark,#082A53); border:1px solid rgba(255,255,255,0.12); border-radius:8px; color:var(--text); font-size:14px; outline:none;">
            <option value="">Semua Jenis</option>
            <?php foreach ($jenisList as $j): ?>
            <option value="<?= $j ?>" <?= $filterJenis === $j ? 'selected' : '' ?>><?= $j ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
        <a href="fasilitas.php" class="btn" style="background:transparent; border:1px solid rgba(255,255,255,0.2); color:rgba(245,247,250,0.6);">
            <i class="fa-solid fa-rotate-left"></i> Reset
        </a>
        <span style="font-size:13px; color:rgba(245,247,250,0.4); margin-left:auto;"><?= count($facilities) ?> fasilitas ditemukan</span>
    </form>
</div>

<!-- Hasil -->
<?php if (empty($facilities)): ?>
<div class="card" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:64px 0; color:rgba(245,247,250,0.3);">
    <i class="fa-solid fa-location-dot" style="font-size:48px; margin-bottom:12px;"></i>
    <p style="font-size:16px;">Tidak ada fasilitas ditemukan</p>
    <p style="font-size:13px; margin-top:4px;">Coba ubah filter lantai atau jenis fasilitas</p>
</div>
<?php else: ?>

<?php foreach ($grouped as $lantai => $facList): ?>
<div class="card" style="margin-bottom:16px;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
        <div style="width:32px; height:32px; border-radius:8px; background:rgba(0,212,216,0.1); display:flex; align-items:center; justify-content:center;">
            <i class="fa-solid fa-layer-group" style="color:var(--accent,#00D4D8);"></i>
        </div>
        <h2 style="font-size:15px; font-weight:600;"><?= sanitize($lantai) ?></h2>
        <span style="font-size:13px; color:rgba(245,247,250,0.4);"><?= count($facList) ?> fasilitas</span>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:12px;">
        <?php foreach ($facList as $fac): ?>
        <?php
            $statusColor = match($fac['status']) {
                'Tersedia'       => 'color:#4ade80; background:rgba(74,222,128,0.1); border-color:rgba(74,222,128,0.2)',
                'Tidak Tersedia' => 'color:#f87171; background:rgba(248,113,113,0.1); border-color:rgba(248,113,113,0.2)',
                'Maintenance'    => 'color:#fbbf24; background:rgba(251,191,36,0.1); border-color:rgba(251,191,36,0.2)',
                default          => 'color:rgba(245,247,250,0.5); background:rgba(255,255,255,0.05); border-color:rgba(255,255,255,0.1)'
            };
        ?>
        <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; padding:16px; transition:all 0.2s;"
             onmouseover="this.style.borderColor='rgba(0,212,216,0.4)'"
             onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'">
            <div style="display:flex; align-items:start; justify-content:space-between; margin-bottom:8px;">
                <div style="display:flex; align-items:center; gap:8px;">
                  <i class="fa-solid <?= $jenisIcon[$fac['category']] ?? 'fa-location-dot' ?>" style="color:var(--accent,#00D4D8);"></i>
                    <p style="font-size:14px; font-weight:600;"><?= sanitize($fac['name']) ?></p>
                </div>
                <span style="font-size:12px; padding:2px 8px; border-radius:20px; border:1px solid; flex-shrink:0; margin-left:8px; <?= $statusColor ?>">
                    <?= sanitize($fac['status']) ?>
                </span>
            </div>
            <div style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                <i class="fa-solid fa-location-dot" style="color:rgba(245,247,250,0.3); font-size:12px;"></i>
                <p style="font-size:13px; color:rgba(245,247,250,0.5);"><?= sanitize($fac['current_location'] ?? '-') ?></p>
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
$page_title  = 'Fasilitas Umum';
$current_page = 'fasilitas';
require_once __DIR__ . '/../../includes/navbarM05.php';