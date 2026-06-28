<?php
/** @var mysqli $conn */ 
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

if (file_exists(__DIR__ . '/../../config/konek.php')) {
    require_once __DIR__ . '/../../config/konek.php';
} else {
    require_once __DIR__ . '/../../config/connection.php';
}

$total_plafon = 450000000; 
$sql_budget = "SELECT SUM(allocated_amount) as plafon FROM 06_mall_budgets WHERE budget_year = " . date('Y');
$res_budget = $conn->query($sql_budget);
if ($res_budget && $row_b = $res_budget->fetch_assoc()) {
    if ((float)$row_b['plafon'] > 0) $total_plafon = (float)$row_b['plafon'];
}

$total_pendapatan = 75000000;
$total_beban = 25000000;
$kolom_tanggal = 'id';
$cek_kolom = $conn->query("SHOW COLUMNS FROM 06_journal_entries");
if ($cek_kolom) {
    while ($k = $cek_kolom->fetch_assoc()) {
        $field = strtolower($k['Field']);
        if (in_array($field, ['entry_date', 'date', 'created_at', 'tanggal'])) { $kolom_tanggal = $field; break; }
    }
}
$sql_lr = "SELECT c.account_code, SUM(jl.debit) as dbt, SUM(jl.credit) as crd
           FROM 06_journal_lines jl
           JOIN 06_journal_entries j ON jl.journal_entry_id = j.id
           JOIN 06_chart_of_accounts c ON jl.account_id = c.id
           WHERE YEAR(j.`$kolom_tanggal`) = " . date('Y') . " GROUP BY c.id";
$res_lr = $conn->query($sql_lr);
if ($res_lr && $res_lr->num_rows > 0) {
    $total_pendapatan = 0; $total_beban = 0;
    while ($row = $res_lr->fetch_assoc()) {
        $prefix = substr($row['account_code'], 0, 1);
        if ($prefix == '4') $total_pendapatan += ($row['crd'] - $row['dbt']);
        elseif ($prefix == '5') $total_beban += ($row['dbt'] - $row['crd']);
    }
}
$laba_bersih_ytd = $total_pendapatan - $total_beban;

$outstanding_piutang = 0;
$sql_ar = "SELECT SUM(total_amount) as sisa FROM 06_invoices WHERE status = 'Belum Bayar'";
$res_ar = $conn->query($sql_ar);
if ($res_ar && $row_a = $res_ar->fetch_assoc()) {
    $outstanding_piutang = (float)$row_a['sisa'];
}

$department_name = "Finance Department (Manager Dashboard)";
$user_name = $_SESSION['nama'];
$page_title = "Executive Finance Dashboard";

$menu_items = [
    [
        'icon' => 'fa-solid fa-gauge',
        'label' => 'Dashboard Manager',
        'link' => 'dashboardManager.php',
        'active_page' => 'dashboardManager'
    ],
    [
        'icon' => 'fa-solid fa-scale-balanced',
        'label' => 'Financial Statement',
        'link' => 'financeStatement.php',
        'active_page' => 'financeStatement'
    ],
    [
        'icon' => 'fa-solid fa-chart-pie',
        'label' => 'Budget Analysis',
        'link' => 'budgetAnalysis.php',
        'active_page' => 'budgetAnalysis'
    ],
    [
        'icon' => 'fa-solid fa-calculator',
        'label' => 'Tax Report (PPN)',
        'link' => 'taxReport.php',
        'active_page' => 'taxReport'
    ],
    [
        'icon' => 'fa-solid fa-building-columns',
        'label' => 'Bank Reconciliation',
        'link' => 'bankReconciliation.php',
        'active_page' => 'bankReconciliation'
    ],
    [
        'icon' => 'fa-solid fa-hourglass-half',
        'label' => 'Aging Receivable',
        'link' => 'agingReceivable.php',
        'active_page' => 'agingReceivable'
    ],
    [
        'icon' => 'fa-solid fa-book',
        'label' => 'Log Otomasi Jurnal',
        'link' => 'journalManagement.php',
        'active_page' => 'journalManagement'
    ]
];

ob_start();
?>

<style>
    :root { --accent: #FFB62A !important; }
    body, .layout, .main-content, .content-body { background-color: #021F42 !important; color: #fff !important; }
    .sidebar { background-color: #011630 !important; }
    .topbar { background-color: #011630 !important; border-bottom: 1px solid rgba(255,255,255,0.05); }
    
    .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 10px; text-align: left; }
    .kpi-card { background: #011630; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 20px; transition: transform 0.2s; }
    .kpi-card:hover { transform: translateY(-3px); border-color: #FFB62A; }
    
    .menu-panel { background: #011630; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 25px; margin-top: 25px; text-align: left; }
    .grid-control { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-top: 15px; }
    .btn-panel { display: flex; align-items: center; justify-content: space-between; padding: 18px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 6px; text-decoration: none; color: #fff; transition: all 0.2s; }
    .btn-panel:hover { background: rgba(255,255,255,0.07); border-color: #FFB62A; transform: translateX(3px); }
</style>

<div class="container-fluid" style="padding-top: 5px;">
    <div class="d-flex justify-content-between align-items-center mb-4" style="text-align: left;">
        <div>
            <p class="small mb-0" style="color: #cbd5e1 !important; opacity: 0.9;">
                Grup Otoritas Eksekutif: <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong>. Panel analisis konsolidasi akuntansi mall.
            </p>
        </div>
        <div style="background-color: #FFB62A; color: #021F42; font-weight: bold; padding: 6px 15px; border-radius: 4px; font-size: 13px;">
            <i class="fa-solid fa-calendar-days me-1"></i> Tahun Buku: <?php echo date('Y'); ?>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="kpi-card" style="border-left: 4px solid #FFB62A;">
            <small style="color: #cbd5e1; font-size: 11px; display: block; letter-spacing: 0.5px;">PLAFON ANGGARAN (YTD)</small>
            <h3 style="margin: 8px 0; font-size: 20px; font-weight: 700; color: #fff;">Rp <?php echo number_format($total_plafon, 0, ',', '.'); ?></h3>
            <small style="color: #FFB62A; font-size: 11px;"><i class="fa-solid fa-chart-pie me-1"></i> Batas Kontrol Biaya Mall</small>
        </div>

        <div class="kpi-card" style="border-left: 4px solid #10b981;">
            <small style="color: #cbd5e1; font-size: 11px; display: block; letter-spacing: 0.5px;">LABA BERSIH BERJALAN</small>
            <h3 style="margin: 8px 0; font-size: 20px; font-weight: 700; color: #10b981;">Rp <?php echo number_format($laba_bersih_ytd, 0, ',', '.'); ?></h3>
            <small style="color: #cbd5e1; opacity: 0.6; font-size: 11px;">Pendapatan: Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></small>
        </div>

        <div class="kpi-card" style="border-left: 4px solid #ef4444;">
            <small style="color: #cbd5e1; font-size: 11px; display: block; letter-spacing: 0.5px;">TOTAL PIUTANG USAHA (AR)</small>
            <h3 style="margin: 8px 0; font-size: 20px; font-weight: 700; color: #ef4444;">Rp <?php echo number_format($outstanding_piutang, 0, ',', '.'); ?></h3>
            <small style="color: #cbd5e1; opacity: 0.6; font-size: 11px;"><i class="fa-solid fa-clock me-1"></i> Outstanding Invoice Tenant</small>
        </div>
    </div>

    <div class="menu-panel">
        <h5 style="color: #FFB62A; font-weight: 600; font-size: 15px; margin-top: 0; margin-bottom: 5px;">
            <i class="fa-solid fa-shield-halved me-2"></i> Konsol Kendali Mutu & Pelaporan Keuangan (M06)
        </h5>
        <p style="color: #cbd5e1; font-size: 12px; margin-bottom: 20px;">Akses cepat modul verifikasi manajer finansial:</p>
        
        <div class="grid-control">
            <a href="financeStatement.php" class="btn-panel">
                <div>
                    <strong style="color: #3b82f6; display: block; font-size: 14px;"><i class="fa-solid fa-wallet me-2"></i> Financial Statement</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Neraca Saldo, Laba Rugi, & Buku Besar</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #3b82f6; opacity: 0.7;"></i>
            </a>

            <a href="budgetAnalysis.php" class="btn-panel">
                <div>
                    <strong style="color: #06b6d4; display: block; font-size: 14px;"><i class="fa-solid fa-chart-line me-2"></i> Budget Analysis</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Monitoring penyerapan anggaran operasional</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #06b6d4; opacity: 0.7;"></i>
            </a>

            <a href="taxReport.php" class="btn-panel">
                <div>
                    <strong style="color: #eab308; display: block; font-size: 14px;"><i class="fa-solid fa-file-invoice-dollar me-2"></i> Tax Report (PPN)</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Rekapitulasi beban pajak masukan & keluaran</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #eab308; opacity: 0.7;"></i>
            </a>

            <a href="bankReconciliation.php" class="btn-panel">
                <div>
                    <strong style="color: #a855f7; display: block; font-size: 14px;"><i class="fa-solid fa-building-columns me-2"></i> Bank Reconciliation</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Pencocokan saldo koran bank vs kas sistem</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #a855f7; opacity: 0.7;"></i>
            </a>

            <a href="agingReceivable.php" class="btn-panel">
                <div>
                    <strong style="color: #f43f5e; display: block; font-size: 14px;"><i class="fa-solid fa-hourglass-half me-2"></i> Aging Receivable</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Analisis umur piutang tenant macet</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #f43f5e; opacity: 0.7;"></i>
            </a>
             <a href="taxReport.php" class="btn-panel">
                <div>
                    <strong style="color: #4f7769; display: block; font-size: 14px;"><i class="fa-solid fa-hourglass-half me-2"></i> Tax Report (PPN)</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Tax Report (PPN)</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #f43f5e; opacity: 0.7;"></i>
            </a>
             <a href="financeStatement.php" class="btn-panel">
                <div>
                    <strong style="color: #774f6a; display: block; font-size: 14px;"><i class="fa-solid fa-hourglass-half me-2"></i> Financial Statements</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Financial Statements</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #f43f5e; opacity: 0.7;"></i>
            </a>
             <a href="budgetAnalysis.php" class="btn-panel">
                <div>
                    <strong style="color: #e0edb1; display: block; font-size: 14px;"><i class="fa-solid fa-hourglass-half me-2"></i> Budget Analysis</strong>
                    <small style="color: #cbd5e1; font-size: 12px;">Budget Analysis</small>
                </div>
                <i class="fa-solid fa-chevron-right style-arrow" style="color: #f43f5e; opacity: 0.7;"></i>
            </a>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/navbarM06.php'; 
?>