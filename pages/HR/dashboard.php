<?php
$page_title = 'Dashboard HR';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/hr_header.php';

// Statistik
$total_pegawai = $pdo->query("SELECT COUNT(*) FROM pegawai WHERE status='aktif'")->fetchColumn();
$hadir_hari_ini = $pdo->query("SELECT COUNT(*) FROM absensi WHERE tanggal = CURDATE() AND status='hadir'")->fetchColumn();
$cuti_pending  = $pdo->query("SELECT COUNT(*) FROM cuti WHERE status='pending'")->fetchColumn();
$jadwal_hari_ini = $pdo->query("SELECT COUNT(*) FROM jadwal_shift WHERE tanggal = CURDATE()")->fetchColumn();

// 5 pegawai terbaru
$pegawai_baru = $pdo->query("SELECT nama, jabatan, departemen, tgl_masuk FROM pegawai ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<!-- STAT CARDS -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        <div class="stat-info">
            <h3><?= $total_pegawai ?></h3>
            <p>Total Pegawai Aktif</p>
        </div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
        <div class="stat-info">
            <h3><?= $hadir_hari_ini ?></h3>
            <p>Hadir Hari Ini</p>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-info">
            <h3><?= $cuti_pending ?></h3>
            <p>Cuti Menunggu Approval</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3><?= $jadwal_hari_ini ?></h3>
            <p>Shift Terjadwal Hari Ini</p>
        </div>
    </div>
</div>

<!-- TABEL PEGAWAI TERBARU -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Pegawai Terbaru</h2>
        <a href="pegawai/index.php" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-arrow-right"></i> Lihat Semua
        </a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Departemen</th>
                    <th>Tgl Masuk</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pegawai_baru): ?>
                    <?php foreach ($pegawai_baru as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nama']) ?></td>
                        <td><?= htmlspecialchars($p['jabatan']) ?></td>
                        <td><?= htmlspecialchars($p['departemen']) ?></td>
                        <td><?= date('d M Y', strtotime($p['tgl_masuk'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">
                        <div class="empty-state">
                            <i class="fa-solid fa-users-slash"></i>
                            <p>Belum ada data pegawai</p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MENU SHORTCUT -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Menu Cepat</h2>
    </div>
    <div style="display:grid; grid-template-columns: repeat(auto-fit,minmax(150px,1fr)); gap:16px;">
        <a href="pegawai/tambah.php" class="btn btn-primary" style="justify-content:center; padding:16px;">
            <i class="fa-solid fa-user-plus"></i> Tambah Pegawai
        </a>
        <a href="shift/index.php" class="btn btn-outline" style="justify-content:center; padding:16px;">
            <i class="fa-solid fa-calendar-plus"></i> Atur Shift
        </a>
        <a href="absensi/index.php" class="btn btn-outline" style="justify-content:center; padding:16px;">
            <i class="fa-solid fa-fingerprint"></i> Lihat Absensi
        </a>
        <a href="cuti/index.php" class="btn btn-outline" style="justify-content:center; padding:16px;">
            <i class="fa-solid fa-umbrella-beach"></i> Kelola Cuti
        </a>
        <a href="payroll/index.php" class="btn btn-outline" style="justify-content:center; padding:16px;">
            <i class="fa-solid fa-money-bill-wave"></i> Payroll
        </a>
        <a href="kpi/index.php" class="btn btn-outline" style="justify-content:center; padding:16px;">
            <i class="fa-solid fa-chart-line"></i> KPI
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/hr_footer.php'; ?>
