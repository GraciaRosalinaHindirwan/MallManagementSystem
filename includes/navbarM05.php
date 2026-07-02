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
$department_name = 'Customer Service';
$user_name       = 'CS Admin'; // Nama user yang akan tampil di ujung kanan atas

$menu_items = [
    [
        'icon'        => 'fa-solid fa-store',
        'label'       => 'Cari Tenant',
        'link'        => BASE_URL . '/pages/CS/cari-tenant.php', // Sesuaikan path folder kalian
        'active_page' => 'cari-tenant'
    ],
    [
        'icon'        => 'fa-solid fa-location-dot',
        'label'       => 'Fasilitas',
        'link'        => BASE_URL . '/pages/CS/fasilitas.php',
        'active_page' => 'fasilitas'
    ],
    [
        'icon'        => 'fa-solid fa-calendar-check',
        'label'       => 'Jadwal Event',
        'link'        => BASE_URL . '/pages/CS/event.php',
        'active_page' => 'event'
    ],
    [
        'icon'        => 'fa-solid fa-list',
        'label'       => 'Semua Tiket',
        'link'        => BASE_URL . '/pages/CS/tiket.php',
        'active_page' => 'tiket'
    ],
    [
        'icon'        => 'fa-solid fa-ticket',
        'label'       => 'Buat Tiket Baru',
        'link'        => BASE_URL . '/pages/CS/tiket-buat.php',
        'active_page' => 'tiket-buat'
    ],
    [
        'icon'        => 'fa-solid fa-triangle-exclamation',
        'label'       => 'SLA Breach',
        'link'        => BASE_URL . '/pages/CS/sla-breach.php',
        'active_page' => 'sla-breach'
    ],
    [
        'icon'        => 'fa-solid fa-box',
        'label'       => 'Barang Temuan',
        'link'        => BASE_URL . '/pages/CS/barang-temuan.php',
        'active_page' => 'barang-temuan'
    ],
    [
        'icon'        => 'fa-solid fa-magnifying-glass',
        'label'       => 'Laporan Kehilangan',
        'link'        => BASE_URL . '/pages/CS/barang-hilang.php',
        'active_page' => 'barang-hilang'
    ],
    [
        'icon'        => 'fa-solid fa-star',
        'label'       => 'Rating & Feedback',
        'link'        => BASE_URL . '/pages/CS/feedback.php',
        'active_page' => 'feedback'
    ]
];
$user_name = $user_name ?? 'Manager';
$page_title = $page_title ?? 'Customer Service ';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?: '' ?> — Mall Management System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/asset/css/designSystem.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/asset/css/templateM05.css">
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