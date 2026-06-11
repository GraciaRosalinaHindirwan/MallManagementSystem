<?php
$page_title = 'Data Pegawai';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/hr_header.php';

// Hapus pegawai
if (isset($_GET['hapus'])) {
    $stmt = $pdo->prepare("DELETE FROM pegawai WHERE id = ?");
    $stmt->execute([(int)$_GET['hapus']]);
    header("Location: index.php?msg=hapus");
    exit;
}

// Pencarian
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM pegawai WHERE nama LIKE ? OR nik LIKE ? OR departemen LIKE ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$keyword = "%$search%";
$stmt->execute([$keyword, $keyword, $keyword]);
$pegawai = $stmt->fetchAll();
?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success">
    <i class="fa-solid fa-circle-check"></i>
    <?= $_GET['msg'] === 'tambah' ? 'Pegawai berhasil ditambahkan!' : ($_GET['msg'] === 'edit' ? 'Data berhasil diupdate!' : 'Pegawai berhasil dihapus!') ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daftar Pegawai</h2>
        <div style="display:flex; gap:12px; align-items:center;">
            <form method="GET" style="display:flex; gap:8px;">
                <input type="text" name="search" placeholder="Cari nama / NIK / departemen..."
                    value="<?= htmlspecialchars($search) ?>"
                    style="background:var(--primary-dark); border:1px solid rgba(255,255,255,0.12); border-radius:8px; padding:8px 12px; color:var(--text); font-family:var(--font-family); font-size:var(--label); outline:none;">
                <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-search"></i></button>
            </form>
            <a href="tambah.php" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-user-plus"></i> Tambah Pegawai
            </a>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>NIK</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Departemen</th>
                    <th>Email</th>
                    <th>Tgl Masuk</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pegawai): ?>
                    <?php foreach ($pegawai as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($p['nik']) ?></td>
                        <td><?= htmlspecialchars($p['nama']) ?></td>
                        <td><?= htmlspecialchars($p['jabatan']) ?></td>
                        <td><?= htmlspecialchars($p['departemen']) ?></td>
                        <td><?= htmlspecialchars($p['email']) ?></td>
                        <td><?= date('d M Y', strtotime($p['tgl_masuk'])) ?></td>
                        <td>
                            <span class="badge <?= $p['status'] === 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                                <?= ucfirst($p['status']) ?>
                            </span>
                        </td>
                        <td style="display:flex; gap:6px;">
                            <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <a href="index.php?hapus=<?= $p['id'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Hapus pegawai <?= htmlspecialchars($p['nama']) ?>?')">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9">
                        <div class="empty-state">
                            <i class="fa-solid fa-users-slash"></i>
                            <p>Tidak ada data pegawai<?= $search ? " untuk \"$search\"" : '' ?></p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>
