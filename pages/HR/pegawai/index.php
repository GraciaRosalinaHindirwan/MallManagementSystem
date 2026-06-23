<?php
$page_title = 'Data Pegawai';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/hr_header.php';

// Hapus pegawai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $id = (int)$_POST['hapus_id'];
    $pdo->prepare("DELETE FROM cuti    WHERE pegawai_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM payroll WHERE pegawai_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM pegawai WHERE id = ?")->execute([$id]);
    header("Location: index.php?msg=hapus");
    exit;
}

// Filter + Pagination
$search     = $_GET['search'] ?? '';
$filter_dep = $_GET['departemen'] ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 10;
$offset     = ($page - 1) * $per_page;

$conditions = ["(p.nama LIKE ? OR p.nik LIKE ? OR p.departemen LIKE ?)"];
$params     = ["%$search%", "%$search%", "%$search%"];

if ($filter_dep) {
    $conditions[] = "p.departemen = ?";
    $params[]     = $filter_dep;
}

$where = "WHERE " . implode(" AND ", $conditions);

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM pegawai p $where");
$count_stmt->execute($params);
$total      = $count_stmt->fetchColumn();
$total_page = ceil($total / $per_page);

$sql = "
    SELECT 
        p.id, p.nik, p.nama, p.jabatan, p.departemen, p.status,
        GREATEST(0, 12 - COALESCE(SUM(
            CASE 
                WHEN c.status = 'disetujui'
                AND c.jenis_cuti = 'tahunan'
                AND YEAR(c.tgl_mulai) = YEAR(CURDATE())
                THEN (DATEDIFF(c.tgl_selesai, c.tgl_mulai) + 1)
                ELSE 0
            END
        ), 0)) as sisa_cuti
    FROM pegawai p
    LEFT JOIN cuti c ON c.pegawai_id = p.id
    $where
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pegawai = $stmt->fetchAll();
?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-<?= $_GET['msg'] === 'hapus' ? 'danger' : 'success' ?>">
    <i class="fa-solid fa-<?= $_GET['msg'] === 'hapus' ? 'trash' : 'circle-check' ?>"></i>
    <?= $_GET['msg'] === 'tambah' ? 'Pegawai berhasil ditambahkan!' : ($_GET['msg'] === 'edit' ? 'Data berhasil diupdate!' : 'Pegawai berhasil dihapus!') ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Pegawai</h2>
        <a href="tambah.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-user-plus"></i> Tambah Pegawai
        </a>
    </div>

    <form method="GET" class="filter-bar">
        <input type="text" name="search"
            placeholder="Cari nama / NIK / departemen..."
            value="<?= htmlspecialchars($search) ?>">
        <select name="departemen">
            <option value="">Semua Departemen</option>
            <?php foreach (['HR','CS','Security','Operations','Facility','Finance','IT'] as $dep): ?>
            <option value="<?= $dep ?>" <?= $filter_dep === $dep ? 'selected' : '' ?>><?= $dep ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-search"></i> Cari
        </button>
        <?php if ($search || $filter_dep): ?>
        <a href="index.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-xmark"></i> Reset
        </a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Departemen</th>
                    <th>Sisa Cuti</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pegawai): ?>
                    <?php foreach ($pegawai as $i => $p): ?>
                    <tr>
                        <td><?= ($offset + $i + 1) ?></td>
                        <td><?= htmlspecialchars($p['nik']) ?></td>
                        <td><?= htmlspecialchars($p['nama']) ?></td>
                        <td><?= htmlspecialchars($p['jabatan']) ?></td>
                        <td><?= htmlspecialchars($p['departemen']) ?></td>
                        <td>
                            <?php
                                $sisa  = (int)$p['sisa_cuti'];
                                $warna = $sisa <= 3 ? 'badge-danger' : ($sisa <= 6 ? 'badge-warning' : 'badge-success');
                            ?>
                            <span class="badge <?= $warna ?>"><?= $sisa ?> hari</span>
                        </td>
                        <td>
                            <span class="badge <?= $p['status'] === 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; gap:6px; align-items:center;">
                                <a href="detail.php?id=<?= $p['id'] ?>" class="btn-aksi detail" title="Detail">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $p['id'] ?>" class="btn-aksi edit" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Hapus pegawai <?= htmlspecialchars(addslashes($p['nama'])) ?>?\nData yang dihapus tidak bisa dikembalikan.')">
                                    <input type="hidden" name="hapus_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-aksi hapus" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <i class="fa-solid fa-users-slash"></i>
                            <p>Tidak ada data pegawai<?= $search ? " untuk \"$search\"" : '' ?></p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_page > 1): ?>
    <div class="pagination">
        <?php $base_url = 'index.php?' . http_build_query(array_filter(['search' => $search, 'departemen' => $filter_dep])); ?>
        <?php if ($page > 1): ?>
        <a href="<?= $base_url ?>&page=<?= $page - 1 ?>" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $total_page; $i++): ?>
        <a href="<?= $base_url ?>&page=<?= $i ?>"
           class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_page): ?>
        <a href="<?= $base_url ?>&page=<?= $page + 1 ?>" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-chevron-right"></i>
        </a>
        <?php endif; ?>
        <span style="color:rgba(255,255,255,0.4); font-size:var(--caption); align-self:center;">
            <?= $total ?> pegawai &bull; halaman <?= $page ?>/<?= $total_page ?>
        </span>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>