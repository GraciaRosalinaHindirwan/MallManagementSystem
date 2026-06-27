<<<<<<< HEAD
<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'Guest';
?>
<nav>
    <button type="button" class="btn p-0 border-0" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
        <svg width="35" height="35" viewBox="0 0 61 61" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7.625 43.2083H53.375M7.625 30.5H53.375M7.625 17.7916H53.375" stroke="#FFB62A" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>
    <span class="navbar-brand">Mall ERP <span style="font-size: 14px; color: #cbd5e1; font-weight: normal;">— M06 Finance</span></span>
</nav>

<div class="offcanvas offcanvas-start offcanvas-sidebar" data-bs-scroll="true" data-bs-backdrop="true" tabindex="-1" id="sidebarMenu">
    <div class="offcanvas-header" style="border-bottom: 1px solid rgba(255,255,255,0.05); padding: 20px;">
        <h5 class="m-0" style="font-size: 18px; font-weight: 700; color: #FFB62A;"><i class="fa-solid fa-city"></i> Mall ERP</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body d-flex flex-column justify-content-between" style="padding: 20px 0 0 0;">
        <div>
            <p style="font-size: 11px; color: #64748b; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; padding-left: 25px; margin-bottom: 15px;">
                M06 FINANCE STAFF
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
                    
                <?php elseif ($role === 'Finance Manager'): ?>
                    <a href="../financeManager/dashboardFinance.php" class="nav-sidebar-item <?= ($current_page == 'dashboardFinance.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-chart-line"></i> Dashboard Executive
                    </a>
                    <a href="../financeManager/agingReceivable.php" class="nav-sidebar-item <?= ($current_page == 'agingReceivable.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-clock"></i> Aging Receivable
                    </a>
                    <a href="../financeManager/bankReconciliation.php" class="nav-sidebar-item <?= ($current_page == 'bankReconciliation.php') ? 'active' : '' ?>">
                        <i class="fa-solid fa-building-columns"></i> Bank Reconciliation
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div style="background: rgba(0,0,0,0.2); padding: 20px; border-top: 1px solid rgba(255,255,255,0.05); margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="width: 35px; height: 35px; background: var(--accent); color: #021F42; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                    <?= substr($_SESSION['nama'] ?? 'U', 0, 1); ?>
                </div>
                <div>
                    <p style="margin: 0; font-size: 13px; font-weight: 600; color: #fff;"><?= $_SESSION['nama'] ?? 'User'; ?></p>
                    <p style="margin: 0; font-size: 11px; color: #00cfd5;"><?= $role; ?></p>
                </div>
            </div>
            <a href="../../logout.php" onclick="return confirm('Apakah anda yakin ingin keluar?')" style="display: flex; align-items: center; gap: 10px; color: #f87171; text-decoration: none; font-size: 13px; font-weight: 600; padding: 5px 0;">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar / Logout
            </a>
        </div>
    </div>
</div>

<div class="content-wrapper"><?php

$current_page = basename($_SERVER['PHP_SELF'], '.php');

if (!defined('BASE_URL')) {
    $project_root = realpath(__DIR__ . '/..');
    $doc_root     = realpath($_SERVER['DOCUMENT_ROOT']);
    $base = '';
    if ($doc_root && $project_root && strpos($project_root, $doc_root) === 0) {
        $base = substr($project_root, strlen($doc_root));
    }
    $base = str_replace('\\', '/', $base);
    define('BASE_URL', $base);
}

// Edit di sini untuk mengubah nama department, menu di sidebar, dan nama user yang tampil di navbar
$department_name = $department_name ?? 'Department ABC DEF'; // Ganti kata-kata yang diapit petik satu
$menu_items = $menu_items ?? [];
// Contoh format menu_items:
// $menu_items = [
//     [
//         'icon' => 'fa-solid fa-chart-line',
//         'label' => 'Dashboard',
//         'link' => 'dashboard.php',
//         'active_page' => 'dashboard'
//     ],
//     [
//         'icon' => 'fa-solid fa-file-invoice',
//         'label' => 'Invoice',
//         'link' => 'invoice/index.php',
//         'active_page' => 'invoice'
//     ],
//     [
//         'icon' => 'fa-solid fa-chart-pie',
//         'label' => 'Laporan Keuangan',
//         'link' => 'laporan/index.php',
//         'active_page' => 'laporan'
//     ],
//     [
//         'icon' => 'fa-solid fa-receipt',
//         'label' => 'Transaksi',
//         'link' => 'transaksi/index.php',
//         'active_page' => 'transaksi'
//     ],
// ];
$user_name = $user_name ?? 'Manager';
$page_title = $page_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?: '' ?> — Mall Management System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/asset/css/designSystem.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/asset/css/template.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <div class="layout">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-close" id="sidebarClose">
                <i class="fa-solid fa-times"></i>
            </button>

            <div class="sidebar-brand">
                <i class="fa-solid fa-building"></i>
                <span>Mall ERP</span>
            </div>
            <div class="sidebar-section-label"><?= htmlspecialchars($department_name ?: 'Menu') ?></div>
            <nav class="sidebar-nav">
                <?php if (empty($menu_items)): ?>
                    <div class="nav-item">
                        <i class="fa-solid fa-circle-info"></i> Tidak ada menu
                    </div>
                <?php else: ?>
                    <?php foreach ($menu_items as $item): ?>
                        <a href="<?= $item['link'] ?? '#' ?>" class="nav-item <?= ($current_page === ($item['active_page'] ?? '')) ? 'active' : '' ?>">
                            <i class="<?= $item['icon'] ?? 'fa-solid fa-circle' ?>"></i>
                            <?= htmlspecialchars($item['label'] ?? 'Menu') ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="<?= BASE_URL ?>/public/logout.php" class="nav-item">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="topbar">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1 class="page-title"><?= htmlspecialchars($page_title ?: 'Dashboard') ?></h1>
                <div class="topbar-user">
                    <i class="fa-solid fa-circle-user"></i>
                    <span><?= htmlspecialchars($user_name ?: 'User') ?></span>
                </div>
            </div>
            <div class="content-body">
                <div class="container">
                    <?php
                    if (isset($content)) {
                        echo $content;
                    }
                    ?>
                </div>
                <?php require_once __DIR__ . '/footer.php'; ?>
            </div>
        </main>
    </div>

    <script>
        (function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const body = document.body;

            if (!menuToggle || !sidebar) {
                return;
            }

            function openSidebar() {
                sidebar.classList.add('open');
                body.classList.add('sidebar-open');
            }

            function closeSidebar() {
                sidebar.classList.remove('open');
                body.classList.remove('sidebar-open');
            }

            menuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openSidebar();
            });

            if (sidebarClose) {
                sidebarClose.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeSidebar();
                });
            }

            window.addEventListener('resize', function() {
                if (window.innerWidth > 576) {
                    closeSidebar();
                }
            });

            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 576) {
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isClickOnToggle = menuToggle.contains(event.target);

                    if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('open')) {
                        closeSidebar();
                    }
                }
            });
        })();
    </script>
</body>

</html>
=======
<<<<<<< HEAD
<?php
if (session_status() == PHP_SESSION_NONE) { 
    session_start(); 
}
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'Guest';

// Bersihkan nama dari duplikat kata (Staff)/(Manager) biar rapi
$user_name = $_SESSION['nama'] ?? 'User';
$user_name = str_replace(['(Staff)', 'Manager', 'Staff', 'Manager'], '', $user_name);
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
        
        .topbar {
            background-color: #082A53;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            
            /* 🔥 BERHASIL DIKUNCI: FIXED STICKY TANPA MERUSAK STRUKTUR ASLI 🔥 */
            position: sticky;
            top: 0;
            z-index: 1020;
            min-height: 75px;
            padding: 12px 32px;
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
                 <span><?= htmlspecialchars($user_name); ?> (<?= ($role === 'Finance Manager') ? 'Manager' : 'Staff'; ?>)</span>
            </div>
        </div>
        <div class="content-body">
</div>

<div class="content-wrapper">
=======
<?php

$current_page = basename($_SERVER['PHP_SELF'], '.php');

if (!defined('BASE_URL')) {
    $project_root = realpath(__DIR__ . '/..');
    $doc_root     = realpath($_SERVER['DOCUMENT_ROOT']);
    $base = '';
    if ($doc_root && $project_root && strpos($project_root, $doc_root) === 0) {
        $base = substr($project_root, strlen($doc_root));
    }
    $base = str_replace('\\', '/', $base);
    define('BASE_URL', $base);
}

// Edit di sini untuk mengubah nama department, menu di sidebar, dan nama user yang tampil di navbar
$department_name = $department_name ?? 'Department ABC DEF'; // Ganti kata-kata yang diapit petik satu
$menu_items = $menu_items ?? [];
// Contoh format menu_items:
// $menu_items = [
//     [
//         'icon' => 'fa-solid fa-chart-line',
//         'label' => 'Dashboard',
//         'link' => 'dashboard.php',
//         'active_page' => 'dashboard'
//     ],
//     [
//         'icon' => 'fa-solid fa-file-invoice',
//         'label' => 'Invoice',
//         'link' => 'invoice/index.php',
//         'active_page' => 'invoice'
//     ],
//     [
//         'icon' => 'fa-solid fa-chart-pie',
//         'label' => 'Laporan Keuangan',
//         'link' => 'laporan/index.php',
//         'active_page' => 'laporan'
//     ],
//     [
//         'icon' => 'fa-solid fa-receipt',
//         'label' => 'Transaksi',
//         'link' => 'transaksi/index.php',
//         'active_page' => 'transaksi'
//     ],
// ];
$user_name = $user_name ?? 'Manager';
$page_title = $page_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?: '' ?> — Mall Management System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/asset/css/designSystem.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/asset/css/template.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

    <div class="layout">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-close" id="sidebarClose">
                <i class="fa-solid fa-times"></i>
            </button>

            <div class="sidebar-brand">
                <i class="fa-solid fa-building"></i>
                <span>Mall ERP</span>
            </div>
            <div class="sidebar-section-label"><?= htmlspecialchars($department_name ?: 'Menu') ?></div>
            <nav class="sidebar-nav">
                <?php if (empty($menu_items)): ?>
                    <div class="nav-item">
                        <i class="fa-solid fa-circle-info"></i> Tidak ada menu
                    </div>
                <?php else: ?>
                    <?php foreach ($menu_items as $item): ?>
                        <a href="<?= $item['link'] ?? '#' ?>" class="nav-item <?= ($current_page === ($item['active_page'] ?? '')) ? 'active' : '' ?>">
                            <i class="<?= $item['icon'] ?? 'fa-solid fa-circle' ?>"></i>
                            <?= htmlspecialchars($item['label'] ?? 'Menu') ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="<?= BASE_URL ?>/public/logout.php" class="nav-item">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="topbar">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1 class="page-title"><?= htmlspecialchars($page_title ?: 'Dashboard') ?></h1>
                <div class="topbar-user">
                    <i class="fa-solid fa-circle-user"></i>
                    <span><?= htmlspecialchars($user_name ?: 'User') ?></span>
                </div>
            </div>
            <div class="content-body">
                <div class="container">
                    <?php
                    if (isset($content)) {
                        echo $content;
                    }
                    ?>
                </div>
                <?php require_once __DIR__ . '/footer.php'; ?>
            </div>
        </main>
    </div>

    <script>
        (function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const body = document.body;

            if (!menuToggle || !sidebar) {
                return;
            }

            function openSidebar() {
                sidebar.classList.add('open');
                body.classList.add('sidebar-open');
            }

            function closeSidebar() {
                sidebar.classList.remove('open');
                body.classList.remove('sidebar-open');
            }

            menuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openSidebar();
            });

            if (sidebarClose) {
                sidebarClose.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeSidebar();
                });
            }

            window.addEventListener('resize', function() {
                if (window.innerWidth > 576) {
                    closeSidebar();
                }
            });

            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 576) {
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isClickOnToggle = menuToggle.contains(event.target);

                    if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('open')) {
                        closeSidebar();
                    }
                }
            });
        })();
    </script>
</body>

</html>
<<<<<<< HEAD
<?php
if (session_status() == PHP_SESSION_NONE) { 
    session_start(); 
}
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
                 <span><?= htmlspecialchars($user_name); ?> (<?= ($role === 'Finance Manager') ? 'Manager' : 'Staff'; ?>)</span>
            </div>
        </div>
        <div class="content-body">
=======
>>>>>>> a5be243c53609d1dca6e7c58cb8bb13db7ed270b
>>>>>>> 8ae22b10bc0f61c3f5d2b110cae64285153316e7
>>>>>>> 2540dc4bc0a7af1bb8304254bac22a5278d6a350
