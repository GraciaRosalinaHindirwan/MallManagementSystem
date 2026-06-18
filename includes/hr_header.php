<?php
// includes/hr_header.php
// Shared header untuk semua halaman HR
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

$page_title = $page_title ?? 'HR Management';
$user_name  = $user_name  ?? 'HR Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — Mall Management System</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/asset/css/designSystem.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/asset/css/template.css">
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
        <div class="sidebar-section-label">HR Management</div>
        <nav class="sidebar-nav">
            <a href="<?= BASE_URL ?>/pages/HR/dashboard.php"
               class="nav-item <?= $current_page === 'dashboard' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/pages/HR/pegawai/index.php"
               class="nav-item <?= (in_array($current_page, ['index','tambah','edit']) && strpos($_SERVER['PHP_SELF'], 'pegawai') !== false) ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> Data Pegawai
            </a>
            <a href="<?= BASE_URL ?>/pages/HR/shift/index.php"
               class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'shift') !== false ? 'active' : '' ?>">
                <i class="fa-solid fa-calendar-days"></i> Jadwal Shift
            </a>
            <a href="<?= BASE_URL ?>/pages/HR/absensi/index.php"
               class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'absensi') !== false ? 'active' : '' ?>">
                <i class="fa-solid fa-fingerprint"></i> Absensi
            </a>
            <a href="<?= BASE_URL ?>/pages/HR/payroll/index.php"
               class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'payroll') !== false ? 'active' : '' ?>">
                <i class="fa-solid fa-money-bill-wave"></i> Payroll
            </a>
            <a href="<?= BASE_URL ?>/pages/HR/cuti/index.php"
               class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'cuti') !== false ? 'active' : '' ?>">
                <i class="fa-solid fa-umbrella-beach"></i> Cuti
            </a>
            <a href="<?= BASE_URL ?>/pages/HR/kpi/index.php"
               class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'kpi') !== false ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-line"></i> KPI
            </a>
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
            <h1 class="page-title"><?= htmlspecialchars($page_title) ?></h1>
            <div class="topbar-user">
                <i class="fa-solid fa-circle-user"></i>
                <span><?= htmlspecialchars($user_name) ?></span>
            </div>
        </div>
        <div class="content-body">

        <script>
            (function () {
                const menuToggle  = document.getElementById('menuToggle');
                const sidebar     = document.getElementById('sidebar');
                const sidebarClose = document.getElementById('sidebarClose');
                const body        = document.body;

                if (!menuToggle || !sidebar) return;

                function openSidebar()  { sidebar.classList.add('open');    body.classList.add('sidebar-open'); }
                function closeSidebar() { sidebar.classList.remove('open'); body.classList.remove('sidebar-open'); }

                menuToggle.addEventListener('click', function (e) {
                    e.preventDefault(); e.stopPropagation(); openSidebar();
                });

                if (sidebarClose) {
                    sidebarClose.addEventListener('click', function (e) {
                        e.preventDefault(); e.stopPropagation(); closeSidebar();
                    });
                }

                window.addEventListener('resize', function () {
                    if (window.innerWidth > 576) closeSidebar();
                });

                document.addEventListener('click', function (event) {
                    if (window.innerWidth <= 576) {
                        if (!sidebar.contains(event.target) &&
                            !menuToggle.contains(event.target) &&
                            sidebar.classList.contains('open')) {
                            closeSidebar();
                        }
                    }
                });
            })();
        </script>
