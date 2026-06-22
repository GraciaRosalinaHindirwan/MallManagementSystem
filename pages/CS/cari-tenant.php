<?php
// require_once __DIR__ . '/../../auth/checkSession.php';
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($conn)) $conn = null;

function sanitize(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

/* ── Ambil data kategori untuk filter ───────────────────── */
$categories = [];
if ($conn) {
    $res = $conn->query("SELECT * FROM `01_tenant_categories` ORDER BY name ASC");
    $categories = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/* ── Ambil data lantai untuk filter ─────────────────────── */
$floors = [];
if ($conn) {
    $res = $conn->query("SELECT * FROM `01_floors` ORDER BY id_floors ASC");
    $floors = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/* ── Filter & Search ─────────────────────────────────────── */
$search    = isset($_GET['search'])   ? sanitize($_GET['search'])  : '';
$filterCat = isset($_GET['category']) ? (int)$_GET['category']    : 0;
$filterFloor = isset($_GET['floor'])  ? (int)$_GET['floor']       : 0;

/* ── Query tenant ────────────────────────────────────────── */
$tenants = [];
if ($conn) {
    $where  = ["t.status = 'Active'"];
    $params = [];
    $types  = '';

    if ($search !== '') {
        $where[]  = "(t.brand_name LIKE ? OR t.tenant_name LIKE ? OR tc.name LIKE ? OR u.unit_code LIKE ?)";
        $like     = "%$search%";
        $params   = array_merge($params, [$like, $like, $like, $like]);
        $types   .= 'ssss';
    }
    if ($filterCat > 0) {
        $where[]  = "t.id_category = ?";
        $params[] = $filterCat;
        $types   .= 'i';
    }
    if ($filterFloor > 0) {
        $where[]  = "f.id_floors = ?";
        $params[] = $filterFloor;
        $types   .= 'i';
    }

    $whereStr = implode(' AND ', $where);
    $sql = "SELECT t.id_tenant, t.brand_name, t.tenant_name, t.status,
                   tc.name AS nama_kategori,
                   u.unit_code, u.area_size,
                   f.floor_number
            FROM `02_tenants` t
            LEFT JOIN `01_tenant_categories` tc ON t.id_category = tc.id_tenant_categories
            LEFT JOIN `01_units` u ON u.tenant_id = t.id_tenant
            LEFT JOIN `01_floors` f ON u.floor_id = f.id_floors
            WHERE $whereStr
            ORDER BY f.id_floors ASC, t.brand_name ASC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result  = $stmt->get_result();
        $tenants = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result  = $conn->query($sql);
        $tenants = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

/* ── Render konten ───────────────────────────────────────── */
ob_start();
?>

<!-- Search & Filter Bar -->
<div class="card">
    <h2 class="card-title">Cari Tenant</h2>
    <p style="color: rgba(245,247,250,0.5); font-size:14px; margin-bottom:20px;">
        Cari informasi tenant berdasarkan nama toko, kategori, atau lokasi unit.
    </p>

    <form method="GET" action="" class="form-grid" style="grid-template-columns: 2fr 1fr 1fr; gap:12px;">
        <div style="position:relative;">
            <i class="fa-solid fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:rgba(245,247,250,0.4);"></i>
            <input type="text" name="search" value="<?= $search ?>"
                   placeholder="Nama toko, tenant, kategori, unit..."
                   style="width:100%; padding:10px 14px 10px 36px; background:var(--primary-dark,#082A53); border:1px solid rgba(255,255,255,0.12); border-radius:8px; color:var(--text); font-size:14px; outline:none; box-sizing:border-box;" />
        </div>
        <select name="floor" style="padding:10px 14px; background:var(--primary-dark,#082A53); border:1px solid rgba(255,255,255,0.12); border-radius:8px; color:var(--text); font-size:14px; outline:none;">
            <option value="0">Semua Lantai</option>
            <?php foreach ($floors as $fl): ?>
            <option value="<?= $fl['id_floors'] ?>" <?= $filterFloor === (int)$fl['id_floors'] ? 'selected' : '' ?>>
                Lantai <?= sanitize($fl['floor_number']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="category" style="padding:10px 14px; background:var(--primary-dark,#082A53); border:1px solid rgba(255,255,255,0.12); border-radius:8px; color:var(--text); font-size:14px; outline:none;">
            <option value="0">Semua Kategori</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id_tenant_categories'] ?>" <?= $filterCat === (int)$cat['id_tenant_categories'] ? 'selected' : '' ?>>
                <?= sanitize($cat['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <div style="grid-column: 1/-1; display:flex; gap:8px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-search"></i> Cari
            </button>
            <a href="cari-tenant.php" class="btn" style="background:transparent; border:1px solid rgba(255,255,255,0.2); color:rgba(245,247,250,0.6);">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Hasil Pencarian -->
<div class="card">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <div>
            <h2 class="card-title" style="margin-bottom:4px;">Hasil Pencarian</h2>
            <p style="color:rgba(245,247,250,0.5); font-size:13px;"><?= count($tenants) ?> tenant ditemukan</p>
        </div>
    </div>

    <?php if (empty($tenants)): ?>
    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:64px 0; color:rgba(245,247,250,0.3);">
        <i class="fa-solid fa-store" style="font-size:48px; margin-bottom:12px;"></i>
        <p style="font-size:16px;">Tidak ada tenant ditemukan</p>
        <p style="font-size:13px; margin-top:4px;">Coba ubah kata kunci atau filter pencarian</p>
    </div>
    <?php else: ?>
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:16px;">
        <?php foreach ($tenants as $t): ?>
        <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:20px; cursor:pointer; transition:all 0.2s;"
             onclick="openDetail(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)"
             onmouseover="this.style.borderColor='var(--accent,#00D4D8)'"
             onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'">

            <div style="display:flex; align-items:start; justify-content:space-between; margin-bottom:12px;">
                <div style="width:40px; height:40px; border-radius:10px; background:rgba(0,212,216,0.15); display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-store" style="color:var(--accent,#00D4D8);"></i>
                </div>
                <span class="badge badge-success">Aktif</span>
            </div>

            <p style="font-size:15px; font-weight:600; margin-bottom:4px;"><?= sanitize($t['brand_name']) ?></p>
            <p style="font-size:13px; color:rgba(245,247,250,0.5); margin-bottom:12px;"><?= sanitize($t['nama_kategori'] ?? '-') ?></p>

            <div style="border-top:1px solid rgba(255,255,255,0.08); padding-top:12px; display:flex; flex-direction:column; gap:6px;">
                <div style="display:flex; align-items:center; gap:8px; font-size:13px; color:rgba(245,247,250,0.6);">
                    <i class="fa-solid fa-location-dot" style="width:14px;"></i>
                    <span>Lantai <?= sanitize($t['floor_number'] ?? '-') ?> — Unit <?= sanitize($t['unit_code'] ?? '-') ?></span>
                </div>
                <div style="display:flex; align-items:center; gap:8px; font-size:13px; color:rgba(245,247,250,0.6);">
                    <i class="fa-solid fa-building" style="width:14px;"></i>
                    <span><?= sanitize($t['tenant_name']) ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Detail Tenant -->
<div id="detailModal" style="display:none; position:fixed; inset:0; z-index:999; align-items:center; justify-content:center; background:rgba(2,31,66,0.85); backdrop-filter:blur(4px);">
    <div style="background:#102F5C; border:1px solid rgba(0,212,216,0.3); border-radius:16px; padding:28px; width:100%; max-width:420px; margin:16px; box-shadow:0 8px 32px rgba(0,0,0,0.45);">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
            <h3 style="font-size:18px; font-weight:600;">Detail Tenant</h3>
            <button onclick="closeDetail()" style="background:transparent; border:none; color:rgba(245,247,250,0.4); font-size:18px; cursor:pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div>
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                <div style="width:48px; height:48px; border-radius:12px; background:rgba(0,212,216,0.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fa-solid fa-store" style="color:var(--accent,#00D4D8); font-size:20px;"></i>
                </div>
                <div>
                    <p style="font-size:16px; font-weight:700;" id="m-brand"></p>
                    <p style="font-size:13px; color:rgba(245,247,250,0.5);" id="m-kategori"></p>
                </div>
            </div>
            <div style="background:rgba(255,255,255,0.05); border-radius:10px; padding:16px; display:flex; flex-direction:column; gap:12px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <i class="fa-solid fa-location-dot" style="color:var(--accent,#00D4D8); width:16px;"></i>
                    <div>
                        <p style="font-size:12px; color:rgba(245,247,250,0.4);">Lokasi</p>
                        <p style="font-size:14px;" id="m-lokasi"></p>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <i class="fa-solid fa-building" style="color:var(--accent,#00D4D8); width:16px;"></i>
                    <div>
                        <p style="font-size:12px; color:rgba(245,247,250,0.4);">Nama Perusahaan</p>
                        <p style="font-size:14px;" id="m-tenant"></p>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:12px;">
                    <i class="fa-solid fa-tag" style="color:var(--accent,#00D4D8); width:16px;"></i>
                    <div>
                        <p style="font-size:12px; color:rgba(245,247,250,0.4);">Status</p>
                        <p style="font-size:14px;" id="m-status"></p>
                    </div>
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

function openDetail(t) {
    document.getElementById('m-brand').textContent    = t.brand_name;
    document.getElementById('m-kategori').textContent = t.nama_kategori || '-';
    document.getElementById('m-lokasi').textContent   = 'Lantai ' + (t.floor_number || '-') + ' — Unit ' + (t.unit_code || '-');
    document.getElementById('m-tenant').textContent   = t.tenant_name;
    document.getElementById('m-status').textContent   = t.status;
    modal.style.display = 'flex';
}

function closeDetail() {
    modal.style.display = 'none';
}

modal.addEventListener('click', function(e) {
    if (e.target === modal) closeDetail();
});
</script>
<?php
$extraScript = ob_get_clean();

$page_title  = 'Cari Tenant';
$current_page = 'cari-tenant';

require_once __DIR__ . '/../../includes/navbarM05.php';