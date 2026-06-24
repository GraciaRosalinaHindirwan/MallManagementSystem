<?php
$page_title = 'Detail Pegawai';
require_once __DIR__ . '/../../../config/database.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM pegawai WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { header("Location: index.php"); exit; }

$gaji_stmt = $pdo->prepare("
    SELECT * FROM payroll 
    WHERE pegawai_id = ? 
    ORDER BY tahun DESC, bulan DESC 
    LIMIT 1
");
$gaji_stmt->execute([$id]);
$gaji = $gaji_stmt->fetch();

$sisa_stmt = $pdo->prepare("
    SELECT GREATEST(0, 12 - COALESCE(SUM(
        CASE 
            WHEN status = 'disetujui'
            AND jenis_cuti = 'tahunan'
            AND YEAR(tgl_mulai) = YEAR(CURDATE())
            THEN (DATEDIFF(tgl_selesai, tgl_mulai) + 1)
            ELSE 0
        END
    ), 0)) as sisa_cuti
    FROM cuti WHERE pegawai_id = ?
");
$sisa_stmt->execute([$id]);
$sisa_cuti = (int)$sisa_stmt->fetchColumn();

$cuti_stmt = $pdo->prepare("
    SELECT * FROM cuti WHERE pegawai_id = ?
    ORDER BY created_at DESC LIMIT 5
");
$cuti_stmt->execute([$id]);
$riwayat_cuti = $cuti_stmt->fetchAll();

require_once __DIR__ . '/../../../includes/hr_header.php';
?>

<div style="margin-bottom:20px;">
    <a href="index.php" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
    <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm" style="margin-left:8px;">
        <i class="fa-solid fa-pen"></i> Edit Pegawai
    </a>
</div>

<!-- Info Utama -->
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title"><?= htmlspecialchars($p['nama']) ?></h2>
            <div style="color:rgba(245,247,250,0.5); font-size:14px; margin-top:4px;">
                <?= htmlspecialchars($p['jabatan']) ?> &bull; <?= htmlspecialchars($p['departemen']) ?>
            </div>
        </div>
        <span class="badge <?= $p['status'] === 'aktif' ? 'badge-success' : 'badge-danger' ?>" style="font-size:14px; padding:6px 16px;">
            <?= ucfirst($p['status']) ?>
        </span>
    </div>

    <div class="detail-grid">
        <div class="detail-item">
            <label>NIK</label>
            <p><?= htmlspecialchars($p['nik']) ?></p>
        </div>
        <div class="detail-item">
            <label>Email</label>
            <p><?= htmlspecialchars($p['email']) ?></p>
        </div>
        <div class="detail-item">
            <label>No. HP</label>
            <p><?= $p['no_hp'] ? htmlspecialchars($p['no_hp']) : '—' ?></p>
        </div>
        <div class="detail-item">
            <label>Tanggal Lahir</label>
            <p><?= $p['tgl_lahir'] ? date('d M Y', strtotime($p['tgl_lahir'])) : '—' ?></p>
        </div>
        <div class="detail-item">
            <label>Tanggal Masuk</label>
            <p><?= date('d M Y', strtotime($p['tgl_masuk'])) ?></p>
        </div>
        <div class="detail-item">
            <label>Sisa Cuti Tahunan</label>
            <p>
                <?php $warna = $sisa_cuti <= 3 ? 'badge-danger' : ($sisa_cuti <= 6 ? 'badge-warning' : 'badge-success'); ?>
                <span class="badge <?= $warna ?>"><?= $sisa_cuti ?> hari</span>
            </p>
        </div>
        <div class="detail-item" style="grid-column:1/-1;">
            <label>Alamat</label>
            <p><?= $p['alamat'] ? htmlspecialchars($p['alamat']) : '—' ?></p>
        </div>
    </div>
</div>

<!-- Informasi Gaji -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Informasi Gaji</h2>
    </div>
    <?php if ($gaji): ?>
    <div class="detail-grid">
        <div class="detail-item">
            <label>Gaji Pokok</label>
            <p>Rp <?= number_format($gaji['gaji_pokok'], 0, ',', '.') ?></p>
        </div>
        <div class="detail-item">
            <label>Tunjangan</label>
            <p>Rp <?= number_format($gaji['tunjangan'] ?? 0, 0, ',', '.') ?></p>
        </div>
        <div class="detail-item">
            <label>Potongan</label>
            <p>Rp <?= number_format($gaji['potongan'] ?? 0, 0, ',', '.') ?></p>
        </div>
        <div class="detail-item">
            <label>Total</label>
            <p style="font-weight:700; color:var(--success);">
                Rp <?= number_format($gaji['total'] ?? 0, 0, ',', '.') ?>
            </p>
        </div>
        <div class="detail-item">
            <label>Periode</label>
            <p><?= $gaji['bulan'] ?>/<?= $gaji['tahun'] ?></p>
        </div>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:24px;">
        <i class="fa-solid fa-file-invoice-dollar"></i>
        <p>Belum ada data gaji</p>
    </div>
    <?php endif; ?>
</div>

<!-- Riwayat Cuti -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Riwayat Cuti</h2>
        <span style="color:rgba(245,247,250,0.4); font-size:12px;">5 terakhir</span>
    </div>
    <?php if ($riwayat_cuti): ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Jenis</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Durasi</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($riwayat_cuti as $c): ?>
                <?php
                    $durasi    = (new DateTime($c['tgl_mulai']))->diff(new DateTime($c['tgl_selesai']))->days + 1;
                    $badge_map = ['pending'=>'badge-warning','disetujui'=>'badge-success','ditolak'=>'badge-danger'];
                ?>
                <tr>
                    <td><span class="badge badge-info"><?= ucfirst($c['jenis_cuti']) ?></span></td>
                    <td><?= date('d M Y', strtotime($c['tgl_mulai'])) ?></td>
                    <td><?= date('d M Y', strtotime($c['tgl_selesai'])) ?></td>
                    <td><?= $durasi ?> hari</td>
                    <td><span class="badge <?= $badge_map[$c['status']] ?>"><?= ucfirst($c['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:24px;">
        <i class="fa-solid fa-umbrella-beach"></i>
        <p>Belum ada riwayat cuti</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../../includes/hr_footer.php'; ?>