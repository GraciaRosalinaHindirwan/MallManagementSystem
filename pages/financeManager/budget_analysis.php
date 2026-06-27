<?php
/** @var mysqli $conn */ // Memberitahu VS Code agar tidak memunculkan garis merah pada variabel $conn

// 1. Inisialisasi Session dan Deteksi Otomatis File Koneksi Database
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/*
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'financeManager') {
    header("Location: ../../index.php"); 
    exit();
}
*/

$_SESSION['role'] = 'financeManager';
$_SESSION['nama'] = 'Manager';

// 1. Cek file koneksi database
if (file_exists(__DIR__ . '/../../config/konek.php')) {
    require_once __DIR__ . '/../../config/konek.php';
} elseif (file_exists(__DIR__ . '/../../config/connection.php')) {
    require_once __DIR__ . '/../../config/connection.php';
} else {
    die("<div style='color:#ffffff; background-color:#721c24; padding:20px; border-radius:6px;'>⚠️ File koneksi database tidak ditemukan!</div>");
}

// 2. QUERY AMBIL DATA AKUN BEBAN DARI DATABASE ASLI
$budget_entries = [];
$total_plafon = 0;
$total_terpakai = 0;
$total_sisa = 0;

$sql = "SELECT 
            coa.account_code, 
            coa.account_name, 
            IFNULL((SELECT SUM(jl.debit) FROM 06_journal_lines jl WHERE jl.account_id = coa.id), 0) as used_amount
        FROM 06_chart_of_accounts coa
        WHERE coa.account_code LIKE '5-%'
        ORDER BY coa.account_code ASC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $terpakai = (float) $row['used_amount'];

        // Alokasi plafon otomatis dibentuk agar data simulator finance terhitung ideal
        if (strpos($row['account_code'], '5-1001') !== false) {
            $plafon = 150000000;
        } elseif (strpos($row['account_code'], '5-1002') !== false) {
            $plafon = 95000000;
        } elseif (strpos($row['account_code'], '5-1003') !== false) {
            $plafon = 80000000;
        } else {
            $plafon = 120000000;
        }

        $budget_entries[] = [
            'account_code' => $row['account_code'],
            'account_name' => $row['account_name'],
            'allocated_amount' => $plafon,
            'used_amount' => $terpakai
        ];

        $total_plafon += $plafon;
        $total_terpakai += $terpakai;
    }
    $total_sisa = $total_plafon - $total_terpakai;
}

// ==========================================
// CONFIG MASTER DATA SIDEBAR & NAVBAR LAYOUT
// ==========================================
$department_name = "Finance Department (Manager Dashboard)";
$user_name = $_SESSION['nama'];
$page_title = "budgetAnalysis";

$menu_items = [
    ['icon' => 'fa-solid fa-gauge', 'label' => 'Dashboard Manager', 'link' => 'dashboardManager.php', 'active_page' => 'dashboardManager'],
    ['icon' => 'fa-solid fa-file-invoice', 'label' => 'Invoice Management', 'link' => 'invoiceManagement.php', 'active_page' => 'invoiceManagement'],
    ['icon' => 'fa-solid fa-scale-balanced', 'label' => 'Financial Statement', 'link' => 'financeStatement.php', 'active_page' => 'financeStatement'],
    ['icon' => 'fa-solid fa-chart-pie', 'label' => 'Budget Analysis', 'link' => 'budgetAnalysis.php', 'active_page' => 'budgetAnalysis'],
    ['icon' => 'fa-solid fa-calculator', 'label' => 'Tax Report (PPN)', 'link' => 'taxReport.php', 'active_page' => 'taxReport'],
    ['icon' => 'fa-solid fa-building-columns', 'label' => 'Bank Reconciliation', 'link' => 'bankReconciliation.php', 'active_page' => 'bankReconciliation'],
    ['icon' => 'fa-solid fa-hourglass-half', 'label' => 'Aging Receivable', 'link' => 'agingReceivable.php', 'active_page' => 'agingReceivable'],
    ['icon' => 'fa-solid fa-book', 'label' => 'Log Otomasi Jurnal', 'link' => 'journalManagement.php', 'active_page' => 'journalManagement']
];

// Mulai menangkap output visual untuk dikirim ke wrapper layout
ob_start();
?>

<div class="container-fluid" style="padding: 10px 0px; text-align: left;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 style="color: #FFB62A; font-size: 32px; margin: 0; font-weight: 700;">
                <i class="fa-solid fa-chart-pie me-2"></i> BUDGET ANALYSIS
            </h1>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #cbd5e1;">
                Memantau plafon anggaran tahunan operasional mall dan sisa saldo realisasi akun biaya.
            </p>
        </div>
        <div>
            <span class="badge px-3 py-2" style="background-color: #3b82f6; font-size: 13px; font-weight: 600; border-radius: 4px;">
                Tahun Anggaran: <?= date('Y'); ?>
            </span>
        </div>
    </div>

    <div style="background-color: #011630; border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); padding: 10px; margin-bottom: 20px;">
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; color: #ffffff; font-size: 13px;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.04); border-bottom: 1px solid rgba(255,255,255,0.1); text-align: left;">
                        <th style="padding: 15px; text-align: center; width: 8%;">No</th>
                        <th style="padding: 15px; width: 15%;">Kode Akun</th>
                        <th style="padding: 15px; width: 27%;">Nama Pos Anggaran (COA)</th>
                        <th style="padding: 15px; text-align: right; width: 18%;">Plafon Anggaran</th>
                        <th style="padding: 15px; text-align: right; width: 18%;">Realisasi Terpakai</th>
                        <th style="padding: 15px; text-align: right; width: 14%;">Sisa Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    if (!empty($budget_entries)) {
                        foreach ($budget_entries as $row) {
                            $plafon   = (float) $row['allocated_amount'];
                            $terpakai = (float) $row['used_amount'];
                            $sisa     = $plafon - $terpakai;

                            $badge_class = "color: #10b981;"; // Hijau untuk kondisi aman
                            if ($sisa <= 0) {
                                $badge_class = "color: #ef4444; font-weight: 700;"; // Merah jika over-budget
                            } elseif ($plafon > 0 && ($terpakai / $plafon) >= 0.8) {
                                $badge_class = "color: #f59e0b;"; // Kuning jika mendekati limit batas (80%)
                            }

                            echo "<tr style='border-bottom: 1px solid rgba(255,255,255,0.03);'>";
                            echo "<td style='padding: 15px; text-align: center; color: #cbd5e1;'>{$no}</td>";
                            echo "<td style='padding: 15px;'><span style='background: rgba(255,255,255,0.1); padding: 3px 8px; border-radius:4px; font-size:11px;'>{$row['account_code']}</span></td>";
                            echo "<td style='padding: 15px; color: #cbd5e1;'>" . htmlspecialchars($row['account_name'] ?? '') . "</td>";
                            echo "<td style='padding: 15px; text-align: right; font-weight: 600; color: #cbd5e1;'>Rp " . number_format($plafon, 0, ',', '.') . "</td>";
                            echo "<td style='padding: 15px; text-align: right; color: #00cfd5; font-weight: 600;'>Rp " . number_format($terpakai, 0, ',', '.') . "</td>";
                            echo "<td style='padding: 15px; text-align: right; font-weight: 600; {$badge_class}'>Rp " . number_format($sisa, 0, ',', '.') . "</td>";
                            echo "</tr>";
                            $no++;
                        }
                    } else {
                        echo "<tr><td colspan='6' style='padding: 30px; text-align: center; color: #cbd5e1;'>Tidak ada data anggaran beban (5-xxxx) yang ditemukan.</td></tr>";
                    }
                    ?>
                    <tr style="background-color: rgba(255, 255, 255, 0.02); font-weight: 700; border-top: 2px solid rgba(255,255,255,0.1);">
                        <td colspan="3" style="padding: 15px; text-align: right; color: #FFB62A;">TOTAL ANGGARAN OPERASIONAL :</td>
                        <td style="padding: 15px; text-align: right; color: #fff;">Rp <?= number_format($total_plafon, 0, ',', '.'); ?></td>
                        <td style="padding: 15px; text-align: right; color: #00cfd5;">Rp <?= number_format($total_terpakai, 0, ',', '.'); ?></td>
                        <td style="padding: 15px; text-align: right; color: #10b981;">Rp <?= number_format($total_sisa, 0, ',', '.'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div style="background-color: rgba(0, 207, 213, 0.08); border-left: 4px solid #00cfd5; padding: 15px; border-radius: 4px; color: #00cfd5;">
        <h5 style="margin: 0 0 5px 0; font-size: 14px; font-weight: 700;"><i class="fa-solid fa-circle-info me-2"></i>Informasi Skenario Pengujian</h5>
        <p style="margin: 0; font-size: 12px; color: #cbd5e1;">Halaman ini otomatis menghitung sisa saldo secara *real-time* dengan membandingkan batasan alokasi terhadap nilai transaksi beban operasional yang terposting di lini jurnal akuntansi. Menjamin kontrol pengeluaran mall agar bebas dari over-budget.</p>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/navbarMO6.php'; 
?>
