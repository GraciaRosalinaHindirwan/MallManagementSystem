<?php
<<<<<<< HEAD
if (session_status() == PHP_SESSION_NONE) { 
    session_start(); 
}
=======
<<<<<<< HEAD
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Deteksi nama file halaman yang sedang aktif saat ini
$current_page = basename($_SERVER['PHP_SELF']);

// Deteksi otomatis BASE_URL agar link navigasi tidak bermasalah (Not Found)
if (!defined('BASE_URL')) {
    $project_root = realpath(__DIR__ . '/..');
    $doc_root     = realpath($_SERVER['DOCUMENT_ROOT']);
    $base = '';
    if ($doc_root && $project_root && strpos($project_root, $doc_root) === 0) {
        $base = substr($project_root, strlen($doc_root));
    }
    $base = str_replace('\\', '/', $base);
    $base = '/' . trim($base, '/') . '/';
    if ($base == '//') $base = '/';
    define('BASE_URL', $base);
}

// Wadah penampung item menu sidebar
$menu_items = [];

// Variabel default identitas Eva yang akan berubah secara otomatis
$display_name = 'Eva';
$display_role = 'Guest';
$department_name = 'M06 - Core System';

// =========================================================================
// LOGIKA ATURAN SKENARIO: MENU SIDEBAR & PROFIL DINAMIS EVA
// =========================================================================

if ($current_page == 'purchase_requests.php' || $current_page == 'purchase_orders.php') {
    // KONDISI 1 & 2: Halaman Purchasing Staff
    $display_name    = 'Eva (Purchasing)';
    $display_role    = 'Purchasing Staff';
    $department_name = 'M06 - Purchasing Staff';

    $menu_items[] = [
        'id'    => 'purchase_requests',
        'icon'  => 'fa-solid fa-file-invoice',
        'label' => 'Purchase Requests',
        'link'  => BASE_URL . 'pages/purchasingStaff/purchase_requests.php'
    ];
    $menu_items[] = [
        'id'    => 'purchase_orders',
        'icon'  => 'fa-solid fa-cart-shopping',
        'label' => 'Purchase Orders',
        'link'  => BASE_URL . 'pages/purchasingStaff/purchase_orders.php'
    ];
    $menu_items[] = [
        'id'    => 'approval_po',
        'icon'  => 'fa-solid fa-user-check',
        'label' => 'Approval PO',
        'link'  => BASE_URL . 'pages/purchasingManager/approval_po.php?mode=view'
    ];
} elseif ($current_page == 'vendor_bill.php') {
    // KONDISI 3: Halaman Finance Staff
    $display_name    = 'Eva (Finance)';
    $display_role    = 'Finance Staff';
    $department_name = 'M06 - Finance Staff';

    $menu_items[] = [
        'id'    => 'purchase_requests',
        'icon'  => 'fa-solid fa-file-invoice',
        'label' => 'Purchase Requests',
        'link'  => BASE_URL . 'pages/purchasingStaff/purchase_requests.php'
    ];
    $menu_items[] = [
        'id'    => 'vendor_bill',
        'icon'  => 'fa-solid fa-money-check-dollar',
        'label' => 'Vendor Bill & Receipt',
        'link'  => BASE_URL . 'pages/financeStaff/vendor_bill.php'
    ];
} elseif ($current_page == 'approval_po.php') {
    // KONDISI 4: Halaman Purchasing Manager
    $display_name    = 'Eva (Manager Purchasing)';
    $display_role    = 'Purchasing Manager';
    $department_name = 'M06 - Purchasing Manager';

    $menu_items[] = [
        'id'    => 'purchase_requests',
        'icon'  => 'fa-solid fa-file-invoice',
        'label' => 'Purchase Requests',
        'link'  => BASE_URL . 'pages/purchasingStaff/purchase_requests.php'
    ];
    $menu_items[] = [
        'id'    => 'approval_po',
        'icon'  => 'fa-solid fa-user-check',
        'label' => 'Approval PO',
        'link'  => BASE_URL . 'pages/purchasingManager/approval_po.php'
    ];
} elseif ($current_page == 'budget_analysis.php' || $current_page == 'tax_report.php') {
    // KONDISI 5: Halaman Finance Manager
    $display_name    = 'Eva (Manager Finance)';
    $display_role    = 'Finance Manager';
    $department_name = 'M06 - Finance Manager';

    $menu_items[] = [
        'id'    => 'vendor_bill',
        'icon'  => 'fa-solid fa-money-check-dollar',
        'label' => 'Vendor Bill & Receipt',
        'link'  => BASE_URL . 'pages/financeStaff/vendor_bill.php'
    ];
    $menu_items[] = [
        'id'    => 'budget_analysis',
        'icon'  => 'fa-solid fa-chart-pie',
        'label' => 'Budget Analysis',
        'link'  => BASE_URL . 'pages/financeManager/budget_analysis.php'
    ];
    $menu_items[] = [
        'id'    => 'tax_report',
        'icon'  => 'fa-solid fa-receipt',
        'label' => 'Tax Report',
        'link'  => BASE_URL . 'pages/financeManager/tax_report.php'
    ];
}

// Sinkronisasi ulang ke session agar tidak bentrok dengan setelan halaman lain
$_SESSION['nama'] = $display_name;
$_SESSION['role'] = $display_role;
?>

<nav>
    <button type="button" class="btn p-0 border-0" id="menuToggle">
        <svg width="32" height="32" viewBox="0 0 61 61" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7.625 43.2083H53.375M7.625 30.5H53.375M7.625 17.7916H53.375" stroke="#FFB62A" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>
    <span class="navbar-brand">Mall ERP <span style="font-size: 14px; color: #cbd5e1; font-weight: normal;">— <?= $department_name; ?></span></span>
</nav>

<div class="offcanvas-sidebar" id="sidebarMenu">
    <div style="border-bottom: 1px solid rgba(255,255,255,0.05); padding: 20px; display: flex; justify-content: space-between; align-items: center;">
        <h5 style="color: var(--accent); margin: 0; font-weight: 700; letter-spacing: 1px; font-size: 15px;">MENU UTAMA</h5>
        <button type="button" id="sidebarClose" style="background: transparent; border: 0; color: #ef4444; font-size: 22px; cursor: pointer; padding: 0 5px;">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div style="padding: 0; display: flex; flex-direction: column; justify-content: space-between; flex-grow: 1;">
        <div class="mt-3 flex-grow-1">
            <?php
            foreach ($menu_items as $item):
                // Cek status menyala hijau toska (active state)
                $is_active = ($current_page == $item['id'] . '.php');
            ?>
                <a href="<?= $item['link']; ?>" class="nav-sidebar-item <?= $is_active ? 'active' : ''; ?>">
                    <i class="<?= $item['icon']; ?>"></i> <?= $item['label']; ?>
                </a>
            <?php endforeach; ?>
=======
if (session_status() == PHP_SESSION_NONE) { session_start(); }
>>>>>>> 53642bb7d7372fb6e9da9fd0ddd0a7e13c3b42f3
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'Guest';

// Bersihkan nama dari duplikat kata (Staff)/(Manager) biar rapi
$user_name = $_SESSION['nama'] ?? 'User';
$user_name = str_replace(['Staff', 'Manager', 'Staff', 'Manager'], '', $user_name);
$user_name = trim($user_name);

// Judul dinamis sesuai role di Topbar
$page_display_title = ($role === 'Finance Manager') ? 'Dashboard Manager' : 'Dashboard Staff';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #021F42;
            color: #F5F7FA;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            overflow-x: hidden;
        }
        .layout {
            display: flex;
            min-height: 100vh;
            width: 100vw;
        }
        
        /* STYLE SIDEBAR OFFCANVAS MURNI TEMPLATE AWAL */
        .offcanvas-sidebar {
            width: 280px !important;
            background-color: #082A53 !important;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            color: #F5F7FA;
        }
        
        .sidebar-brand {
            padding: 24px;
            font-size: 22px;
            font-weight: 700;
            color: #FFB62A;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .nav-sidebar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #cbd5e1;
            text-decoration: none;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .nav-sidebar-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }
        
        .nav-sidebar-item.active {
            background-color: #0B376D;
            color: #00D4D8 !important;
            font-weight: 600;
            border-left: 4px solid #00D4D8;
        }

        /* AREA WORKSPACE KONTEN UTAMA */
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            width: 100vw;
        }
        
        /* SINKRONISASI DENGAN TEMPLATE PATOKAN + LOCK NAVBAR */
        .topbar {
            background-color: #082A53;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            
            /* Sifat penahan agar atas tidak bergerak */
            position: sticky;
            top: 0;
            z-index: 1020;
            min-height: 70px;
            padding: 0 32px;
        }
        
        .menu-toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
        }
        
        .content-body {
            padding: 32px;
        }
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
                    <a href="../financeStaff/billingManagement.php" class="nav-sidebar-item <?= ($current_page == 'billingManagement.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-cash-register"></i> Billing System
                    </a>
                    <a href="../financeStaff/journalManagement.php" class="nav-sidebar-item <?= ($current_page == 'journalManagement.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-book"></i> Jurnal Otomatis
                    </a>
                    <a href="../financeStaff/dashboardNonSewa.php" class="nav-sidebar-item <?= ($current_page == 'journalManagement.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-book"></i> Non-Sewa Management
                    </a>
                     <a href="../financeStaff/bukuBesar.php" class="nav-sidebar-item <?= ($current_page == 'bukuBesar.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-book"></i> Buku Besar
                    </a>
                    <a href="../financeStaff/vendor_bill.php" class="nav-sidebar-item <?= ($current_page == 'vendor_bill.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-book"></i> Vendor Bills
                    </a>
                    
                <?php elseif ($role === 'Finance Manager'): ?>
                    <a href="../financeManager/dashboardManager.php" class="nav-sidebar-item <?= ($current_page == 'dashboardFinance.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-chart-line"></i> Dashboard Manager
                    </a>
                    <a href="../financeManager/agingReceivable.php" class="nav-sidebar-item <?= ($current_page == 'agingReceivable.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-clock"></i> Aging Receivable
                    </a>
                    <a href="../financeManager/bankReconciliation.php" class="nav-sidebar-item <?= ($current_page == 'bankReconciliation.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-building-columns"></i> Bank Reconciliation
                    </a>
                    <a href="../financeManager/financeStatement.php" class="nav-sidebar-item <?= ($current_page == 'financeStatement.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-building-columns"></i> Finance Statement
                    </a>
                    <a href="../financeManager/taxReport.php" class="nav-sidebar-item <?= ($current_page == 'taxReport.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-building-columns"></i> Tax Report
                    </a>
                    <a href="../financeManager/budgetAnalysis.php" class="nav-sidebar-item <?= ($current_page == 'budgetAnalysis.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-building-columns"></i> Budget Analysis
                    </a>
                <?php endif; ?>
            </div>
>>>>>>> 566c94f9b4e907effbc09fbd6e3a4579cf056628
        </div>

<<<<<<< HEAD
        <div style="padding: 24px; border-top: 1px solid rgba(255,255,255,0.05);">
            <div style="font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 8px;">
                Role: <?= htmlspecialchars($role); ?>
            </div>
            <a href="../../logout.php" onclick="return confirm('Apakah anda yakin ingin keluar?')" style="display: flex; align-items: center; gap: 8px; color: #f87171; text-decoration: none; font-size: 14px; font-weight: 600;">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
=======
        <div style="background: rgba(0,0,0,0.2); padding: 20px; border-top: 1px solid rgba(255,255,255,0.05); margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="width: 35px; height: 35px; background: var(--accent); color: #021F42; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
<<<<<<< HEAD
                    E
                </div>
                <div>
                    <p style="margin: 0; font-size: 13px; font-weight: 600; color: #fff;"><?= $display_name; ?></p>
                    <p style="margin: 0; font-size: 11px; color: #00cfd5; font-weight: 500;"><?= $display_role; ?></p>
                </div>
            </div>
            <a href="<?= BASE_URL; ?>logout.php" onclick="return confirm('Apakah anda yakin ingin keluar?')" style="color: #ef4444; text-decoration: none; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar Aplikasi
=======
                    <?= substr($_SESSION['nama'] ?? 'U', 0, 1); ?>
                </div>
                <div>
                    <p style="margin: 0; font-size: 13px; font-weight: 600; color: #fff;"><?= $_SESSION['nama'] ?? 'User'; ?></p>
                    <p style="margin: 0; font-size: 11px; color: #00cfd5;"><?= $role; ?></p>
                </div>
            </div>
            <a href="../../logout.php" onclick="return confirm('Apakah anda yakin ingin keluar?')" style="display: flex; align-items: center; gap: 10px; color: #f87171; text-decoration: none; font-size: 13px; font-weight: 600; padding: 5px 0;">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar / Logout
>>>>>>> 566c94f9b4e907effbc09fbd6e3a4579cf056628
>>>>>>> 53642bb7d7372fb6e9da9fd0ddd0a7e13c3b42f3
            </a>
        </div>
    </div>

<<<<<<< HEAD
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
                 <span><?= htmlspecialchars($user_name); ?> (<?= ($role === 'Finance Manager') ? 'Manager' : 'Staff'; ?>)</span>
            </div>
        </div>
        <div class="content-body">
=======
<<<<<<< HEAD
<script>
    (function() {
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebarMenu');
        const sidebarClose = document.getElementById('sidebarClose');
        const body = document.body;

        if (!menuToggle || !sidebar) return;

        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            sidebar.classList.add('open');
            body.classList.add('sidebar-open');
        });

        if (sidebarClose) {
            sidebarClose.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                sidebar.classList.remove('open');
                body.classList.remove('sidebar-open');
            });
        }

        document.addEventListener('click', function(event) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggle = menuToggle.contains(event.target);
            if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                body.classList.remove('sidebar-open');
            }
        });
    })();
</script>
=======
<div class="content-wrapper">
>>>>>>> 566c94f9b4e907effbc09fbd6e3a4579cf056628
>>>>>>> 53642bb7d7372fb6e9da9fd0ddd0a7e13c3b42f3
