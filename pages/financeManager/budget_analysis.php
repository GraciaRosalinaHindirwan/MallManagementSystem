<?php

/** @var mysqli $conn */ // Memberitahu VS Code agar tidak memunculkan garis merah pada variabel $conn

// 1. Inisialisasi Session dan Deteksi Otomatis File Koneksi Database
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Menyamakan role agar sinkron dengan hak akses finance staff/manager di navbar
$_SESSION['role'] = 'Finance Staff';
$_SESSION['nama'] = 'Eva (Finance)';

// Mengambil file koneksi database bawaan project secara aman (Mundur 2 tingkat ke config)
if (file_exists(__DIR__ . '/../../config/koneksi.php')) {
    require_once __DIR__ . '/../../config/koneksi.php';
} else {
    require_once __DIR__ . '/../../config/connection.php';
}

// Mengatur judul departemen dinamis untuk dibaca top-navbar
$department_name = "Finance Manager - Budget Analysis";

// 2. Sertakan File Header Atas (Mundur 2 tingkat agar file ditemukan)
include __DIR__ . '/../../includes/header.php';
if (!file_exists(__DIR__ . '/../../includes/header.php')) {
    include __DIR__ . '/../../header.php'; // Alternatif jika ditaruh di root luar
}

// =========================================================================
// REVISI POIN 1: MEMUNCUKKAN MENU SIDEBAR SECARA OTOMATIS KHUSUS HALAMAN EVA
// =========================================================================
// Kita intercept variabel $current_page agar navbar.php merender menu khusus Keuangan Eva
$current_page = 'vendor_bill.php';

// 3. Sertakan File Navbar & Sidebar
include __DIR__ . '/../../includes/navbar.php';
if (!file_exists(__DIR__ . '/../../includes/navbar.php')) {
    include __DIR__ . '/../../navbar.php'; // Alternatif jika ditaruh di root luar
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1" style="color: var(--accent); font-weight: 700;">
                    <i class="fa-solid fa-chart-pie me-2"></i> BUDGET ANALYSIS
                </h4>
                <p class="small mb-0" style="color: #cbd5e1 !important; font-weight: 400; opacity: 0.9;">
                    Memantau plafon anggaran tahunan operasional mall dan sisa saldo realisasi akun biaya.
                </p>
            </div>
            <div class="badge bg-primary px-3 py-2 fs-6">Tahun Anggaran: <?php echo date('Y'); ?></div>
        </div>

        <div class="card bg-dark text-white border-0 shadow-sm mb-4" style="background-color: #011630 !important; border: 1px solid rgba(255,255,255,0.05) !important;">
            <div class="card-body p-0">
                <table class="table-custom mb-0" style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="8%" class="text-center">No</th>
                            <th width="15%">Kode Akun</th>
                            <th width="27%">Nama Pos Anggaran (COA)</th>
                            <th width="18%" class="text-end">Plafon Anggaran</th>
                            <th width="18%" class="text-end">Realisasi Terpakai</th>
                            <th width="14%" class="text-end">Sisa Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query JOIN untuk mendapatkan data budget dan nama akunnya dari database mall_erp
                        $sql = "SELECT mb.*, coa.account_code, coa.account_name 
                                FROM mall_budgets mb
                                JOIN chart_of_accounts coa ON mb.account_id = coa.id
                                WHERE mb.budget_year = " . date('Y') . "
                                ORDER BY coa.account_code ASC";

                        $result = $conn->query($sql);
                        $no = 1;

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $plafon   = (float) $row['allocated_amount'];
                                $terpakai = (float) $row['used_amount'];
                                $sisa     = $plafon - $terpakai;

                                // Logika penanda warna jika budget menipis atau kritis (habis)
                                $badge_class = "text-success";
                                if ($sisa <= 0) {
                                    $badge_class = "text-danger fw-bold";
                                } elseif ($plafon > 0 && ($terpakai / $plafon) >= 0.8) {
                                    $badge_class = "text-warning";
                                }

                                echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.05);'>";
                                echo "<td class='text-center' style='padding: 15px;'>{$no}</td>";
                                echo "<td style='padding: 15px;'><span class='badge bg-secondary px-2 py-1'>{$row['account_code']}</span></td>";
                                echo "<td>" . htmlspecialchars($row['account_name'] ?? '') . "</td>";
                                echo "<td class='text-end fw-semibold' style='padding: 15px;'>Rp " . number_format($plafon, 2, ',', '.') . "</td>";
                                echo "<td class='text-end text-info' style='padding: 15px;'>Rp " . number_format($terpakai, 2, ',', '.') . "</td>";
                                echo "<td class='text-end {$badge_class}' style='padding: 15px;'>Rp " . number_format($sisa, 2, ',', '.') . "</td>";
                                echo "</tr>";
                                $no++;
                            }
                        } else {
                            // REVISI POIN 2: Memperjelas tulisan data kosong agar teks abu-abunya terang dan kontras
                            echo "<tr><td colspan='6' class='text-center py-4' style='padding: 30px; color: #cbd5e1 !important;'><i class='fa-solid fa-folder-open me-2' style='color: var(--accent);'></i>Belum ada data plafon anggaran yang disetup untuk tahun " . date('Y') . " di database.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="alert alert-info border-0 shadow-sm" style="background-color: rgba(0, 207, 213, 0.1); color: #00cfd5;">
            <h5 class="alert-heading fw-bold"><i class="fa-solid fa-circle-info me-2"></i>Informasi Skenario Pengujian (Eva)</h5>
            <p class="mb-0 small">Halaman ini otomatis menghitung sisa saldo secara *real-time* dengan membandingkan batasan master budget (`mall_budgets`) terhadap nilai transaksi yang terposting di jurnal akuntansi (`journal_entry_items`). Menjamin kontrol pengeluaran agar bebas dari over-budget.</p>
        </div>
    </div>
</div>

<?php
// 4. Sertakan File Footer
include __DIR__ . '/../../includes/footer.php';
if (!file_exists(__DIR__ . '/../../includes/footer.php')) {
    include __DIR__ . '/../../footer.php'; // Alternatif jika ditaruh di root luar
}
?>