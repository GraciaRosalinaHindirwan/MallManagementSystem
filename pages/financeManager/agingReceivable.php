<<<<<<< HEAD
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

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'Guest';

// Bersihkan nama dari duplikat kata biar rapi
$user_name = $_SESSION['nama'] ?? 'User';
$user_name = str_replace(['Staff', 'Manager'], '', $user_name);
$user_name = trim($user_name);

// Judul dinamis untuk Topbar
$page_title = 'Aging Receivable';
$department_name = "Finance & Accounting Dashboard";

// 1. Panggil file koneksi terpusat secara aman
if (file_exists('../../config/konek.php')) {
    require_once '../../config/konek.php';
} else {
    require_once '../../config/connection.php';
}

// PBI-M06-01-03: Logika Menghitung Umur Piutang (Aging Receivable)
$query_aging = "SELECT i.id, i.invoice_number, i.total_amount, i.due_date, t.brand_name,
                DATEDIFF(CURDATE(), i.due_date) as hari_terlambat
                FROM 06_invoices i
                LEFT JOIN 02_tenants t ON i.tenant_id = t.id_tenant
                ORDER BY hari_terlambat DESC";

$aging_list = [];
try {
    $res_aging = $conn->query($query_aging);
    if ($res_aging && $res_aging->num_rows > 0) {
        while ($r = $res_aging->fetch_assoc()) {
            $aging_list[] = $r;
        }
    }
} catch (Exception $e) {
    $error_msg = $e->getMessage();
}

// SIMULASI DATA JIKA DB KOSONG
if (empty($aging_list)) {
    $aging_list = [
        [
            'invoice_number' => 'INV/2026/06/0012',
            'brand_name' => 'Starbucks Coffee Mall Utama',
            'total_amount' => 45000000,
            'due_date' => date('Y-m-d', strtotime('+10 days')),
            'hari_terlambat' => -10
        ],
        [
            'invoice_number' => 'INV/2026/05/0089',
            'brand_name' => 'H&M Apparel Ground Floor',
            'total_amount' => 125000000,
            'due_date' => date('Y-m-d', strtotime('-12 days')),
            'hari_terlambat' => 12
        ],
        [
            'invoice_number' => 'INV/2026/04/0034',
            'brand_name' => 'Cinema XXI Anchor Tenant',
            'total_amount' => 210000000,
            'due_date' => date('Y-m-d', strtotime('-45 days')),
            'hari_terlambat' => 45
        ],
        [
            'invoice_number' => 'INV/2026/06/0015',
            'brand_name' => 'Uniqlo Large Unit',
            'total_amount' => 180000000,
            'due_date' => date('Y-m-d', strtotime('+5 days')),
            'hari_terlambat' => -5
        ]
    ];
}

$total_belum_jatuh_tempo = 0;
$total_aging_1_30 = 0;
$total_aging_30_plus = 0;

$render_data = [];
foreach ($aging_list as $row) {
    $hari = (int)$row['hari_terlambat'];
    $sisa = (float)$row['total_amount'];
    
    if ($hari <= 0) {
        $kategori = "Belum Jatuh Tempo";
        $badge_color = "background-color: #00cfd5; color: #021F42;";
        $total_belum_jatuh_tempo += $sisa;
    } elseif ($hari > 0 && $hari <= 30) {
        $kategori = "1 - 30 Hari";
        $badge_color = "background-color: #FFB62A; color: #021F42;";
        $total_aging_1_30 += $sisa;
    } else {
        $kategori = "> 30 Hari (Macet)";
        $badge_color = "background-color: #ff4d4d; color: #ffffff;";
        $total_aging_30_plus += $sisa;
    }

    $render_data[] = array_merge($row, [
        'kategori' => $kategori,
        'badge_color' => $badge_color,
        'sisa' => $sisa
    ]);
}

// MASTER DATA MENU SIDEBAR UNTUK TINGKAT MANAGER (DISERASIKAN DENGAN DASHBOARD)
$menu_items = [
    [
        'icon' => 'fa-solid fa-gauge',
        'label' => 'Dashboard Manager',
        'link' => 'dashboardManager.php',
        'active_page' => 'dashboardManager'
    ],
    [
        'icon' => 'fa-solid fa-file-invoice',
        'label' => 'Invoice Management',
        'link' => 'invoiceManagement.php',
        'active_page' => 'invoiceManagement'
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
        'link' => 'bankReco.php',
        'active_page' => 'bankReco'
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

// Mulai tangkap isi workspace halaman utama
ob_start();
?>

<style>
    body, .layout, .main-content, .content-body { background-color: #021F42 !important; color: #F5F7FA !important; }
    .sidebar { background-color: #011630 !important; }
    .topbar { background-color: #011630 !important; border-bottom: 1px solid rgba(255,255,255,0.05); }
</style>

<div class="container-fluid" style="text-align: left; padding-top: 10px;">
    <div class="mb-5">
        <h1 style="color: #FFB62A; font-size: 32px; margin: 0; font-weight: 700; letter-spacing: -0.5px;">Dashboard Analisis Umur Piutang</h1>
        <p style="color: #cbd5e1; margin: 6px 0 0 0; font-size: 14px;">PBI-M06-01-03 — Pemantauan Aging Piutang Tenant Terpusat (Finance Manager)</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-12 col-md-4">
            <div style="background: #011630; padding: 24px; border-radius: 12px; border-left: 5px solid #00cfd5; border-top: 1px solid rgba(255,255,255,0.05); border-right: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.15); height: 100%;">
                <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 1px; font-weight: 600; text-transform: uppercase;">BELUM JATUH TEMPO</h5>
                <h3 style="color: #ffffff; margin: 14px 0 0 0; font-size: 26px; font-weight: 700; word-break: break-all;">Rp <?= number_format($total_belum_jatuh_tempo, 0, ',', '.'); ?></h3>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div style="background: #011630; padding: 24px; border-radius: 12px; border-left: 5px solid #FFB62A; border-top: 1px solid rgba(255,255,255,0.05); border-right: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.15); height: 100%;">
                <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 1px; font-weight: 600; text-transform: uppercase;">MENUNGGAK 1 - 30 HARI</h5>
                <h3 style="color: #FFB62A; margin: 14px 0 0 0; font-size: 26px; font-weight: 700; word-break: break-all;">Rp <?= number_format($total_aging_1_30, 0, ',', '.'); ?></h3>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div style="background: #011630; padding: 24px; border-radius: 12px; border-left: 5px solid #ff4d4d; border-top: 1px solid rgba(255,255,255,0.05); border-right: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.15); height: 100%;">
                <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 1px; font-weight: 600; text-transform: uppercase;">MENUNGGAK > 30 HARI</h5>
                <h3 style="color: #ff4d4d; margin: 14px 0 0 0; font-size: 26px; font-weight: 700; word-break: break-all;">Rp <?= number_format($total_aging_30_plus, 0, ',', '.'); ?></h3>
            </div>
        </div>
    </div>

    <div style="background: #011630; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); padding: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse; font-size: 14px; color: #ffffff;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.04); text-align: left; border-bottom: 2px solid rgba(255,255,255,0.08);">
                        <th style="padding: 16px 12px; font-weight: 600;">No. Invoice</th>
                        <th style="padding: 16px 12px; font-weight: 600;">Nama Tenant</th>
                        <th style="padding: 16px 12px; font-weight: 600;">Sisa Piutang</th>
                        <th style="padding: 16px 12px; font-weight: 600;">Tanggal Jatuh Tempo</th>
                        <th style="padding: 16px 12px; font-weight: 600;">Status Keterlambatan</th>
                        <th style="padding: 16px 12px; font-weight: 600;">Kategori Umur Piutang</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($render_data as $row): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.06); transition: background 0.2s;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.02)'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 16px 12px;"><strong><?= htmlspecialchars($row['invoice_number']); ?></strong></td>
                            <td style="padding: 16px 12px; color: #cbd5e1;"><?= htmlspecialchars($row['brand_name'] ?? 'Tenant General'); ?></td>
                            <td style="padding: 16px 12px; font-weight: 600; color: #00cfd5;">Rp <?= number_format($row['sisa'], 0, ',', '.'); ?></td>
                            <td style="padding: 16px 12px; color: #cbd5e1;"><?= date('d M Y', strtotime($row['due_date'])); ?></td>
                            <td style="padding: 16px 12px;">
                                <?= $row['hari_terlambat'] <= 0 ? '<span style="color: #10b981; font-weight: 500;"><i class="fa-solid fa-circle-check me-1"></i> Lancar</span>' : '<span style="color: #ff4d4d; font-weight: 500;"><i class="fa-solid fa-triangle-exclamation me-1"></i> Terlambat ' . $row['hari_terlambat'] . ' Hari</span>'; ?>
                            </td>
                            <td style="padding: 16px 12px;">
                                <span style="padding: 6px 14px; font-size: 12px; border-radius: 20px; font-weight: 600; display: inline-block; <?= $row['badge_color']; ?>">
                                    <?= $row['kategori']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/navbarMO6.php'; 
?>
=======
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

// Sesi default sementara tetap dibiarkan di bawahnya agar aman dicoba sekarang
$_SESSION['role'] = 'financeManager';
$_SESSION['nama'] = 'Manager';

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'Guest';

// Bersihkan nama dari duplikat kata biar rapi
$user_name = $_SESSION['nama'] ?? 'User';
$user_name = str_replace(['Staff', 'Manager'], '', $user_name);
$user_name = trim($user_name);

// Judul dinamis untuk Topbar
$page_display_title = 'Aging Receivable';

// 1. Panggil file koneksi terpusat secara aman
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

// PBI-M06-01-03: Logika Menghitung Umur Piutang (Aging Receivable)
$query_aging = "SELECT i.id, i.invoice_number, i.total_amount, i.due_date, t.brand_name,
                DATEDIFF(CURDATE(), i.due_date) as hari_terlambat
                FROM 06_invoices i
                LEFT JOIN 02_tenants t ON i.tenant_id = t.id_tenant
                ORDER BY hari_terlambat DESC";

$aging_list = [];
try {
    $res_aging = $conn->query($query_aging);
    if ($res_aging && $res_aging->num_rows > 0) {
        while ($r = $res_aging->fetch_assoc()) {
            $aging_list[] = $r;
        }
    }
} catch (Exception $e) {
    $error_msg = $e->getMessage();
}

// SIMULASI DATA JIKA DB KOSONG
if (empty($aging_list)) {
    $aging_list = [
        [
            'invoice_number' => 'INV/2026/06/0012',
            'brand_name' => 'Starbucks Coffee Mall Utama',
            'total_amount' => 45000000,
            'due_date' => date('Y-m-d', strtotime('+10 days')),
            'hari_terlambat' => -10
        ],
        [
            'invoice_number' => 'INV/2026/05/0089',
            'brand_name' => 'H&M Apparel Ground Floor',
            'total_amount' => 125000000,
            'due_date' => date('Y-m-d', strtotime('-12 days')),
            'hari_terlambat' => 12
        ],
        [
            'invoice_number' => 'INV/2026/04/0034',
            'brand_name' => 'Cinema XXI Anchor Tenant',
            'total_amount' => 210000000,
            'due_date' => date('Y-m-d', strtotime('-45 days')),
            'hari_terlambat' => 45
        ],
        [
            'invoice_number' => 'INV/2026/06/0015',
            'brand_name' => 'Uniqlo Large Unit',
            'total_amount' => 180000000,
            'due_date' => date('Y-m-d', strtotime('+5 days')),
            'hari_terlambat' => -5
        ]
    ];
}

$total_belum_jatuh_tempo = 0;
$total_aging_1_30 = 0;
$total_aging_30_plus = 0;

$render_data = [];
foreach ($aging_list as $row) {
    $hari = (int)$row['hari_terlambat'];
    $sisa = (float)$row['total_amount'];
    
    if ($hari <= 0) {
        $kategori = "Belum Jatuh Tempo";
        $badge_color = "background-color: #00cfd5; color: #021F42;";
        $total_belum_jatuh_tempo += $sisa;
    } elseif ($hari > 0 && $hari <= 30) {
        $kategori = "1 - 30 Hari";
        $badge_color = "background-color: #FFB62A; color: #021F42;";
        $total_aging_1_30 += $sisa;
    } else {
        $kategori = "> 30 Hari (Macet)";
        $badge_color = "background-color: #ff4d4d; color: #ffffff;";
        $total_aging_30_plus += $sisa;
    }

    $render_data[] = array_merge($row, [
        'kategori' => $kategori,
        'badge_color' => $badge_color,
        'sisa' => $sisa
    ]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aging Receivable - Mall ERP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #021F42; color: #F5F7FA; font-family: 'Poppins', sans-serif; margin: 0; overflow-x: hidden; }
        .layout { display: flex; min-height: 100vh; width: 100vw; }
        .offcanvas-sidebar { width: 280px !important; background-color: #082A53 !important; border-right: 1px solid rgba(255, 255, 255, 0.05); color: #F5F7FA; }
        .sidebar-brand { padding: 24px; font-size: 22px; font-weight: 700; color: #FFB62A; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .nav-sidebar-item { display: flex; align-items: center; gap: 12px; color: #cbd5e1; text-decoration: none; padding: 12px 24px; font-size: 14px; font-weight: 500; transition: all 0.2s ease; }
        .nav-sidebar-item:hover { background: rgba(255, 255, 255, 0.05); color: #fff; }
        .nav-sidebar-item.active { background-color: #0B376D; color: #00D4D8 !important; font-weight: 600; border-left: 4px solid #00D4D8; }
        .main-content { flex-grow: 1; display: flex; flex-direction: column; width: 100vw; overflow-x: hidden; }
        .topbar { background-color: #082A53; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255, 255, 255, 0.05); position: sticky; top: 0; z-index: 1020; min-height: 70px; padding: 0 32px; }
        .menu-toggle-btn { background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; }
        .content-body { padding: 32px; }
    </style>
</head>
<body>

<div class="layout">
    <div class="offcanvas offcanvas-start offcanvas-sidebar" data-bs-scroll="true" data-bs-backdrop="true" tabindex="-1" id="sidebarMenu">
        <div class="sidebar-brand">
            <i class="fa-solid fa-city"></i>
            <span>Mall ERP</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close" style="margin-left: auto; font-size: 14px;"></button>
        </div>
        
        <div style="padding: 20px 0; flex-grow: 1;">
            <p style="font-size: 11px; color: #64748b; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; padding-left: 24px; margin-bottom: 15px;">
                M06 FINANCE MANAGEMENT
            </p>
            <div class="d-flex flex-column">
                <?php if ($role === 'Finance Staff'): ?>
                    <a href="../financeStaff/dashboardStaff.php" class="nav-sidebar-item <?= ($current_page == 'dashboardStaff.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-chart-pie"></i> Dashboard Staff
                    </a>
                    <a href="../financeStaff/invoiceManagement.php" class="nav-sidebar-item <?= ($current_page == 'invoiceManagement.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-file-invoice"></i> Invoice Management
                    </a>
                <?php elseif ($role === 'Finance Manager'): ?>
                    <a href="../financeManager/dashboardManager.php" class="nav-sidebar-item <?= ($current_page == 'dashboardManager.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-chart-line"></i> Dashboard Manager
                    </a>
                    <a href="../financeManager/agingReceivable.php" class="nav-sidebar-item <?= ($current_page == 'agingReceivable.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-clock"></i> Aging Receivable
                    </a>
                    <a href="../financeManager/bankReconciliation.php" class="nav-sidebar-item <?= ($current_page == 'bankReconciliation.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-building-columns"></i> Bank Reconciliation
                    </a>
                    <a href="../financeManager/financeStatement.php" class="nav-sidebar-item <?= ($current_page == 'financeStatement.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-receipt"></i> Finance Statement
                    </a>
                    <a href="../financeManager/taxReport.php" class="nav-sidebar-item <?= ($current_page == 'taxReport.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-percent"></i> Tax Report
                    </a>
                    <a href="../financeManager/budgetAnalysis.php" class="nav-sidebar-item <?= ($current_page == 'budgetAnalysis.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-magnifying-glass-chart"></i> Budget Analysis
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div style="padding: 24px; border-top: 1px solid rgba(255,255,255,0.05);">
            <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 8px;">
                Role: <?= htmlspecialchars($role); ?>
            </div>
            <a href="../../logout.php" onclick="return confirm('Apakah anda yakin ingin keluar?')" style="display: flex; align-items: center; gap: 8px; color: #f87171; text-decoration: none; font-size: 14px; font-weight: 600;">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <main class="main-content">
        <div class="topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <button type="button" class="menu-toggle-btn" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                    <svg width="32" height="32" viewBox="0 0 61 61" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7.625 43.2083H53.375M7.625 30.5H53.375M7.625 17.7916H53.375" stroke="#FFB62A" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <span style="font-size: 20px; font-weight: 700; color: #fff; margin: 0;">
                    <?= htmlspecialchars($page_display_title); ?> <span style="font-size: 14px; color: #cbd5e1; font-weight: normal;">— M06 Finance</span>
                </span>
            </div>
            
            <div style="display: flex; align-items: center; gap: 8px; color: #FFB62A; font-size: 15px; font-weight: 600;">
                 <i class="fa-solid fa-circle-user" style="font-size: 18px;"></i>
                 <span><?= htmlspecialchars($user_name); ?> (Manager)</span>
            </div>
        </div>

        <div class="content-body">
            <div class="mb-5">
                <h1 style="color: #FFB62A; font-size: 32px; margin: 0; font-weight: 700; letter-spacing: -0.5px;">Dashboard Analisis Umur Piutang</h1>
                <p style="color: #cbd5e1; margin: 6px 0 0 0; font-size: 14px;">PBI-M06-01-03 — Pemantauan Aging Piutang Tenant Terpusat (Finance Manager)</p>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-12 col-md-4">
                    <div style="background: rgba(255,255,255,0.03); padding: 24px; border-radius: 12px; border-left: 5px solid #00cfd5; box-shadow: 0 4px 12px rgba(0,0,0,0.15); height: 100%;">
                        <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 1px; font-weight: 600; text-transform: uppercase;">BELUM JATUH TEMPO</h5>
                        <h3 style="color: #ffffff; margin: 14px 0 0 0; font-size: 28px; font-weight: 700; word-break: break-all;">Rp <?= number_format($total_belum_jatuh_tempo, 0, ',', '.'); ?></h3>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div style="background: rgba(255,255,255,0.03); padding: 24px; border-radius: 12px; border-left: 5px solid #FFB62A; box-shadow: 0 4px 12px rgba(0,0,0,0.15); height: 100%;">
                        <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 1px; font-weight: 600; text-transform: uppercase;">MENUNGGAK 1 - 30 HARI</h5>
                        <h3 style="color: #FFB62A; margin: 14px 0 0 0; font-size: 28px; font-weight: 700; word-break: break-all;">Rp <?= number_format($total_aging_1_30, 0, ',', '.'); ?></h3>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div style="background: rgba(255,255,255,0.03); padding: 24px; border-radius: 12px; border-left: 5px solid #ff4d4d; box-shadow: 0 4px 12px rgba(0,0,0,0.15); height: 100%;">
                        <h5 style="font-size: 12px; margin: 0; color: #a0aec0; letter-spacing: 1px; font-weight: 600; text-transform: uppercase;">MENUNGGAK > 30 HARI</h5>
                        <h3 style="color: #ff4d4d; margin: 14px 0 0 0; font-size: 28px; font-weight: 700; word-break: break-all;">Rp <?= number_format($total_aging_30_plus, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>

            <div style="background: #011630; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); padding: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px; color: #ffffff;">
                        <thead>
                            <tr style="background: rgba(255,255,255,0.04); text-align: left; border-bottom: 2px solid rgba(255,255,255,0.08);">
                                <th style="padding: 16px 12px; font-weight: 600;">No. Invoice</th>
                                <th style="padding: 16px 12px; font-weight: 600;">Nama Tenant</th>
                                <th style="padding: 16px 12px; font-weight: 600;">Sisa Piutang</th>
                                <th style="padding: 16px 12px; font-weight: 600;">Tanggal Jatuh Tempo</th>
                                <th style="padding: 16px 12px; font-weight: 600;">Status Keterlambatan</th>
                                <th style="padding: 16px 12px; font-weight: 600;">Kategori Umur Piutang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($render_data as $row): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.06); transition: background 0.2s;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.02)'" onmouseout="this.style.backgroundColor='transparent'">
                                    <td style="padding: 16px 12px;"><strong><?= htmlspecialchars($row['invoice_number']); ?></strong></td>
                                    <td style="padding: 16px 12px; color: #cbd5e1;"><?= htmlspecialchars($row['brand_name'] ?? 'Tenant General'); ?></td>
                                    <td style="padding: 16px 12px; font-weight: 600; color: #00cfd5;">Rp <?= number_format($row['sisa'], 0, ',', '.'); ?></td>
                                    <td style="padding: 16px 12px; color: #cbd5e1;"><?= date('d M Y', strtotime($row['due_date'])); ?></td>
                                    <td style="padding: 16px 12px;">
                                        <?= $row['hari_terlambat'] <= 0 ? '<span style="color: #10b981; font-weight: 500;"><i class="fa-solid fa-circle-check me-1"></i> Lancar</span>' : '<span style="color: #ff4d4d; font-weight: 500;"><i class="fa-solid fa-triangle-exclamation me-1"></i> Terlambat ' . $row['hari_terlambat'] . ' Hari</span>'; ?>
                                    </td>
                                    <td style="padding: 16px 12px;">
                                        <span style="padding: 6px 14px; font-size: 12px; border-radius: 20px; font-weight: 600; display: inline-block; <?= $row['badge_color']; ?>">
                                            <?= $row['kategori']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
>>>>>>> main
