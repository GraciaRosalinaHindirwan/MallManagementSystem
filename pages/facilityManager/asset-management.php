<?php
$page_title  = "Asset Management";
$page = "asset_management";
require_once 'auth/checkSession.php';
require_once '../MallManagementSystem/config/konek.php';
require_once 'asset_functions.php';
 // Ambil data aset
$assets = mysqli_query($conn, "SELECT * FROM 03_assets ORDER BY asset_id DESC");
$totalAssets = mysqli_num_rows($assets);
 // Hitung statistik
$totalBookValue = 0;
$vitalCount = 0;
$criticalCount = 0;
$allAssets = [];
while ($a = mysqli_fetch_assoc($assets)) {
    $allAssets[] = $a;
    $dep = calculateDepreciation($a['purchase_value'], $a['purchase_date'], $a['useful_life']);
    $totalBookValue += $dep['bookValue'];
    if ($a['is_vital']) {
        $vitalCount++;
        if ($dep['remainingYears'] < 1) $criticalCount++;
    }
}
mysqli_data_seek($assets, 0); // reset pointer
 // Ambil riwayat mutasi
$mutations = mysqli_query($conn, "SELECT m.*, a.name as asset_name FROM 03_asset_mutations m JOIN 03_assets a ON m.asset_id = a.asset_id ORDER BY m.mutation_id DESC LIMIT 10");
?>
 <?php 
 $page_title = "Asset Management";
 ob_start();
?>
<div class="card">
    <h2 class="card-title">
        <i class="fa-solid fa-boxes-stacked"></i>
        Asset Management
    </h2>
    <p>
    Pendataan, mutasi, depresiasi, dan monitoring aset vital
    </p>
</div>
 <?php if (isset($_GET['success'])): ?>
    <div class="bg-success/20 border-l-4 border-success text-success p-3 rounded mb-4">
        <?= $_GET['success'] == 'add' ? '✅ Aset baru berhasil didaftarkan dengan ID unik.' : '📍 Lokasi aset berhasil diubah & mutasi tercatat.' ?>
    </div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="bg-danger/20 border-l-4 border-danger text-danger p-3 rounded mb-4">
        <?= $_GET['error'] == 'add' ? '❌ Gagal menambahkan aset.' : '❌ Gagal memproses mutasi.' ?>
    </div>
<?php endif; ?>
 <!-- Kartu Statistik -->
<div class="stats-grid">
     <div class="stat-card">
        <div class="stat-icon">
            <i class="fa-solid fa-box"></i>
        </div>
        <div class="stat-info">
            <h3><?= $totalAssets ?></h3>
            <p>Total Aset</p>
        </div>
    </div>
     <div class="stat-card">
        <div class="stat-icon">
            <i class="fa-solid fa-money-bill-trend-up"></i>
        </div>
        <div class="stat-info">
            <h3>Rp <?= number_format($totalBookValue, 0, ',', '.') ?></h3>
            <p>Nilai Buku Total</p>
        </div>
    </div>
     <div class="stat-card success">
        <div class="stat-icon">
            <i class="fa-solid fa-shield-heart"></i>
        </div>
        <div class="stat-info">
            <h3><?= $vitalCount ?></h3>
            <p>Aset Vital</p>
        </div>
    </div>
     <div class="stat-card danger">
        <div class="stat-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-info">
            <h3><?= $criticalCount ?></h3>
            <p>Perlu Penggantian</p>
        </div>
    </div>
 </div>
 <!-- Form Registrasi Aset -->
<div class="card">
     <h3 class="card-title">
        Registrasi Aset Baru
    </h3>
     <form action="asset_process.php" method="POST">
         <input type="hidden" name="action" value="add_asset">
         <div class="form-grid">
             <div class="form-group">
                <label>Nama Aset</label>
                <input type="text" name="name" required>
            </div>
             <div class="form-group">
                <label>Kategori</label>
                <select name="category">
                    <option>HVAC</option>
                    <option>Lift</option>
                    <option>CCTV</option>
                    <option>Genset</option>
                    <option>Electrical</option>
                    <option>Plumbing</option>
                </select>
            </div>
             <div class="form-group">
                <label>Nilai Perolehan (Rp)</label>
                <input type="number" step="0.01" name="value" required>
            </div>
             <div class="form-group">
                <label>Tanggal Beli</label>
                <input type="date" name="purchaseDate" required>
            </div>
             <div class="form-group">
                <label>Umur Ekonomis (tahun)</label>
                <input type="number" name="usefulLife" required>
            </div>
             <div class="form-group">
                <label>Lokasi Awal</label>
                <input type="text" name="location" required>
            </div>
         </div>
         <div class="form-actions">
            <label>
                <input type="checkbox" name="isVital" id="isVital">
                Tandai sebagai Asset Vital
            </label>
             <button type="submit" class="btn btn-primary">
                Daftarkan Asset
            </button>
        </div>
     </form>
 </div>
 <!-- Form Mutasi Lokasi -->
<div class="card">
     <h3 class="card-title">
        Mutasi Lokasi Asset
    </h3>
     <form action="asset_process.php" method="POST">
         <input type="hidden" name="action" value="mutation">
         <div class="form-grid">
             <div class="form-group">
                <label>Pilih Asset</label>
                <select name="asset_id" required>
                     <option value="">-- Pilih Asset --</option>
                     <?php while ($a = mysqli_fetch_assoc($assets)): ?>
                        <option value="<?= $a['asset_id'] ?>">
                            <?= htmlspecialchars($a['name']) ?>
                            (<?= htmlspecialchars($a['current_location']) ?>)
                        </option>
                    <?php endwhile; ?>
                 </select>
            </div>
             <div class="form-group">
                <label>Lokasi Baru</label>
                <input type="text" name="new_location" required>
            </div>
         </div>
         <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Update & Catat Mutasi
            </button>
        </div>
     </form>
 </div>
<?php if (mysqli_num_rows($mutations) > 0): ?>
     <div class="card">
         <h3 class="card-title">
            Riwayat Mutasi Terbaru
        </h3>
         <ul>
             <?php while ($m = mysqli_fetch_assoc($mutations)): ?>
                 <li>
                    <?= htmlspecialchars($m['asset_name']) ?>
                    :
                    <?= htmlspecialchars($m['old_location']) ?>
                    →
                    <?= htmlspecialchars($m['new_location']) ?>
                    (<?= $m['mutation_date'] ?>)
                </li>
             <?php endwhile; ?>
         </ul>
     </div>
 <?php endif; ?>
 <!-- Tabel Depresiasi -->
<div class="card">
     <h3 class="card-title">
        Depresiasi Asset
    </h3>
     <div class="table-wrap">
         <table>
             <thead>
                <tr>
                    <th>ID Unik</th>
                    <th>Nama Asset</th>
                    <th>Nilai Awal</th>
                    <th>Nilai Buku</th>
                    <th>Depresiasi/Tahun</th>
                    <th>QR</th>
                </tr>
            </thead>
             <tbody>
                 <?php foreach ($allAssets as $a): ?>
                     <?php
                    $dep = calculateDepreciation(
                        $a['purchase_value'],
                        $a['purchase_date'],
                        $a['useful_life']
                    );
                    ?>
                     <tr>
                         <td><?= htmlspecialchars($a['asset_code']) ?></td>
                         <td><?= htmlspecialchars($a['name']) ?></td>
                         <td>
                            Rp <?= number_format($a['purchase_value'],0,',','.') ?>
                        </td>
                         <td>
                            Rp <?= number_format($dep['bookValue'],0,',','.') ?>
                        </td>
                         <td>
                            Rp <?= number_format($dep['annualDep'],0,',','.') ?>
                        </td>
                         <td>
                            <span class="badge badge-info">
                                <?= $a['asset_code'] ?>
                            </span>
                        </td>
                     </tr>
                 <?php endforeach; ?>
             </tbody>
         </table>
     </div>
 </div>
 <!-- Monitoring Lifecycle Aset Vital -->
<div class="card">
     <h3 class="card-title">
        Monitoring Lifecycle Asset Vital
    </h3>
     <div class="stats-grid">
         <?php
        $vitalAssets = array_filter(
            $allAssets,
            fn($a) => $a['is_vital'] == 1
        );
        ?>
         <?php if (empty($vitalAssets)): ?>
             <div class="empty-state">
                Belum ada asset vital yang didaftarkan.
            </div>
         <?php else: ?>
             <?php foreach ($vitalAssets as $a): ?>
                 <?php
                $dep = calculateDepreciation(
                    $a['purchase_value'],
                    $a['purchase_date'],
                    $a['useful_life']
                );
                 $need = $dep['remainingYears'] < 1;
                ?>
                 <div class="card">
                     <strong>
                        <?= htmlspecialchars($a['name']) ?>
                    </strong>
                     <p>Kategori: <?= $a['category'] ?></p>
                     <p>
                        Dibeli:
                        <?= date('d M Y', strtotime($a['purchase_date'])) ?>
                    </p>
                     <p>
                        Lokasi:
                        <?= htmlspecialchars($a['current_location']) ?>
                    </p>
                     <p>
                        Sisa Umur:
                        <?= round($dep['remainingYears'],1) ?> tahun
                    </p>
                     <?php if ($need): ?>
                        <span class="badge badge-danger">
                            Segera Diganti
                        </span>
                    <?php else: ?>
                        <span class="badge badge-success">
                            Operasional
                        </span>
                    <?php endif; ?>
                 </div>
             <?php endforeach; ?>
         <?php endif; ?>
     </div>
 </div>
<?php
 $content = ob_get_clean();
 include "../../includes/navbarM03.php";
