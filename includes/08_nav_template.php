<?php
// includes/08_navbar.php

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

// Default values (bisa di-override oleh halaman)
$department_name = $department_name ?? 'BI, Workflow & Notification';
$page_title = $page_title ?? 'Dashboard KPI';
$user_name = $user_name ?? 'Manager';

// Menu default modul 8 (bisa di-override)
$menu_items = $menu_items ?? [
     [
        'icon'        => 'fa-solid fa-chart-line',
        'label'       => 'Dashboard KPI',
        'link'        => '08_dashboard.php',
        'active_page' => 'dashboard',
    ],
    [
        'icon'        => 'fa-solid fa-file-alt',
        'label'       => 'Laporan',
        'link'        => '08_laporan.php',
        'active_page' => 'laporan',
    ],
    [
        'icon'        => 'fa-solid fa-check-circle',
        'label'       => 'Approval',
        'link'        => '#',
        'active_page' => 'approval',
    ],
    [
        'icon'        => 'fa-solid fa-bell',
        'label'       => 'Notifikasi',
        'link'        => 'index.php',
        'active_page' => 'index',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> — Mall Management System</title>
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
            <div class="sidebar-section-label"><?= htmlspecialchars($department_name) ?></div>
            <nav class="sidebar-nav">
                <?php foreach ($menu_items as $item): ?>
                    <a href="<?= $item['link'] ?? '#' ?>"
                        class="nav-item <?= ($current_page === ($item['active_page'] ?? '')) ? 'active' : '' ?>">
                        <i class="<?= $item['icon'] ?? 'fa-solid fa-circle' ?>"></i>
                        <?= htmlspecialchars($item['label'] ?? 'Menu') ?>
                    </a>
                <?php endforeach; ?>
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
                <div class="container">
                    <?php
                    if (isset($content)) {
                        echo $content;
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        (function() {
            const menuToggle = document.getElementById('menuToggle');
            const sidebar = document.getElementById('sidebar');
            const sidebarClose = document.getElementById('sidebarClose');
            const body = document.body;

            if (!menuToggle || !sidebar) return;

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
