<?php
session_start();
require_once '../../config/database.php';

if (!isset($conn)) $conn = null;

function sanitize(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$floors = [];
if ($conn) {
    $res = $conn->query("SELECT * FROM floors ORDER BY id_floor ASC");
    $floors = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

$categories = [];
if ($conn) {
    $res = $conn->query("SELECT * FROM tenant_categories ORDER BY nama_kategori ASC");
    $categories = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

$search      = isset($_GET['search'])    ? sanitize($_GET['search'])    : '';
$filterFloor = isset($_GET['floor'])     ? (int)$_GET['floor']          : 0;
$filterCat   = isset($_GET['category'])  ? (int)$_GET['category']       : 0;

$tenants = [];
if ($conn) {
    $where = ["t.status = 'Aktif'"];
    $params = [];
    $types  = '';

    if ($search !== '') {
        $where[]  = "(t.nama_toko LIKE ? OR t.nomor_unit LIKE ? OR tc.nama_kategori LIKE ?)";
        $like     = "%$search%";
        $params   = array_merge($params, [$like, $like, $like]);
        $types   .= 'sss';
    }
    if ($filterFloor > 0) {
        $where[]  = "t.id_floor = ?";
        $params[] = $filterFloor;
        $types   .= 'i';
    }
    if ($filterCat > 0) {
        $where[]  = "t.id_category = ?";
        $params[] = $filterCat;
        $types   .= 'i';
    }

    $whereStr = implode(' AND ', $where);
    $sql = "SELECT t.*, f.nama_lantai, f.kode_lantai, tc.nama_kategori, tc.icon
            FROM tenants t
            LEFT JOIN floors f ON t.id_floor = f.id_floor
            LEFT JOIN tenant_categories tc ON t.id_category = tc.id_category
            WHERE $whereStr
            ORDER BY f.id_floor ASC, t.nama_toko ASC";

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

ob_start();
?>

<div class="cs-card">
    <h2 class="text-body font-semibold mb-1">Cari Tenant</h2>
    <p class="text-caption text-text/50 mb-5">Cari informasi tenant berdasarkan nama toko, kategori, atau lokasi unit.</p>

    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="md:col-span-2 relative">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-text/40"></i>
            <input type="text" name="search" value="<?= $search ?>"
                   placeholder="Nama toko, unit, kategori..."
                   class="cs-input pl-9" />
        </div>
        <select name="floor" class="cs-input cursor-pointer">
            <option value="0">Semua Lantai</option>
            <?php foreach ($floors as $fl): ?>
            <option value="<?= $fl['id_floor'] ?>" <?= $filterFloor === (int)$fl['id_floor'] ? 'selected' : '' ?>>
                <?= sanitize($fl['nama_lantai']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="category" class="cs-input cursor-pointer">
            <option value="0">Semua Kategori</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id_category'] ?>" <?= $filterCat === (int)$cat['id_category'] ? 'selected' : '' ?>>
                <?= sanitize($cat['nama_kategori']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <div class="md:col-span-4 flex gap-2">
            <button type="submit" class="cs-btn bg-accent text-background hover:brightness-110">
                <i class="bi bi-search"></i> Cari
            </button>
            <a href="cari-tenant.php" class="cs-btn bg-transparent border border-border text-text/60 hover:bg-white/5">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </a>
        </div>
    </form>
</div>

<div class="cs-card">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h2 class="text-body font-semibold">Hasil Pencarian</h2>
            <p class="text-caption text-text/50"><?= count($tenants) ?> tenant ditemukan</p>
        </div>
    </div>

    <?php if (empty($tenants)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-text/30">
        <i class="bi bi-shop text-5xl mb-3"></i>
        <p class="text-body">Tidak ada tenant ditemukan</p>
        <p class="text-caption mt-1">Coba ubah kata kunci atau filter pencarian</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($tenants as $t): ?>
        <div class="bg-surface-raised border border-border rounded-lg p-4 hover:border-accent/50 transition-all cursor-pointer"
             onclick="openDetail(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)">
            <div class="flex items-start justify-between mb-3">
                <div class="w-10 h-10 rounded-md bg-accent/10 flex items-center justify-center flex-shrink-0">
                    <i class="bi <?= sanitize($t['icon'] ?? 'bi-shop') ?> text-accent text-lg"></i>
                </div>
                <span class="text-caption px-2 py-0.5 rounded-full bg-success/10 text-success border border-success/20">
                    Aktif
                </span>
            </div>
            <p class="text-label font-semibold text-text mb-1"><?= sanitize($t['nama_toko']) ?></p>
            <p class="text-caption text-text/50 mb-3"><?= sanitize($t['nama_kategori'] ?? '-') ?></p>
            <div class="space-y-1.5 border-t border-border/50 pt-3">
                <div class="flex items-center gap-2 text-caption text-text/60">
                    <i class="bi bi-geo-alt w-3"></i>
                    <span><?= sanitize($t['nama_lantai'] ?? '-') ?> — Unit <?= sanitize($t['nomor_unit']) ?></span>
                </div>
                <div class="flex items-center gap-2 text-caption text-text/60">
                    <i class="bi bi-clock w-3"></i>
                    <span><?= sanitize($t['jam_buka']) ?> – <?= sanitize($t['jam_tutup']) ?> WIB</span>
                </div>
                <?php if ($t['no_telepon']): ?>
                <div class="flex items-center gap-2 text-caption text-text/60">
                    <i class="bi bi-telephone w-3"></i>
                    <span><?= sanitize($t['no_telepon']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div id="detailModal" class="fixed inset-0 z-50 hidden items-center justify-center"
     style="background: rgba(2,31,66,0.85); backdrop-filter: blur(4px);">
    <div class="bg-surface-raised border border-border-strong rounded-xl p-6 w-full max-w-md mx-4 shadow-lg">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-subheading font-semibold">Detail Tenant</h3>
            <button onclick="closeDetail()" class="text-text/40 hover:text-text">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="space-y-3">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 rounded-lg bg-accent/10 flex items-center justify-center">
                    <i id="m-icon" class="bi bi-shop text-accent text-2xl"></i>
                </div>
                <div>
                    <p class="text-body font-bold" id="m-nama"></p>
                    <p class="text-caption text-text/50" id="m-kategori"></p>
                </div>
            </div>
            <div class="bg-white/5 rounded-lg p-4 space-y-2.5">
                <div class="flex items-center gap-3">
                    <i class="bi bi-geo-alt text-accent w-4"></i>
                    <div>
                        <p class="text-caption text-text/40">Lokasi</p>
                        <p class="text-label" id="m-lokasi"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <i class="bi bi-clock text-accent w-4"></i>
                    <div>
                        <p class="text-caption text-text/40">Jam Operasional</p>
                        <p class="text-label" id="m-jam"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3" id="m-telp-row">
                    <i class="bi bi-telephone text-accent w-4"></i>
                    <div>
                        <p class="text-caption text-text/40">No. Telepon</p>
                        <p class="text-label" id="m-telp"></p>
                    </div>
                </div>
                <div class="flex items-start gap-3" id="m-desc-row">
                    <i class="bi bi-info-circle text-accent w-4 mt-0.5"></i>
                    <div>
                        <p class="text-caption text-text/40">Deskripsi</p>
                        <p class="text-label" id="m-desc"></p>
                    </div>
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

function openDetail(t) {
    document.getElementById('m-icon').className    = 'bi ' + (t.icon || 'bi-shop') + ' text-accent text-2xl';
    document.getElementById('m-nama').textContent  = t.nama_toko;
    document.getElementById('m-kategori').textContent = t.nama_kategori || '-';
    document.getElementById('m-lokasi').textContent   = (t.nama_lantai || '-') + ' — Unit ' + t.nomor_unit;
    document.getElementById('m-jam').textContent      = t.jam_buka + ' – ' + t.jam_tutup + ' WIB';
    document.getElementById('m-telp').textContent     = t.no_telepon || '-';
    document.getElementById('m-desc').textContent     = t.deskripsi || '-';
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

$pageTitle   = 'Cari Tenant — Mall ERP CS';
$currentMenu = 'tenant';

require_once '../../includes/layout_cs.php';
