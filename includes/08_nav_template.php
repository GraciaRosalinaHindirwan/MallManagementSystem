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

// Default values
$department_name = $department_name ?? 'BI, Workflow & Notification';
$page_title = $page_title ?? 'Dashboard KPI';
$user_name = $user_name ?? 'Manager';

// =============================================
// MENU BERDASARKAN ROLE
// =============================================
$role = $_SESSION['role'] ?? 'Staff';

// Menu dasar (semua role)
$base_menu = [
    [
        'icon' => 'fa-solid fa-gauge',
        'label' => 'Dashboard KPI',
        'link' => '08_dashboard.php',
        'active_page' => '08_dashboard'
    ],
    [
        'icon' => 'fa-solid fa-chart-line',
        'label' => 'Laporan',
        'link' => '08_laporan.php',
        'active_page' => '08_laporan'
    ],
    [
        'icon' => 'fa-solid fa-bell',
        'label' => 'Notifikasi',
        'link' => 'notifikasi.php',
        'active_page' => 'notifikasi'
    ],
];

// Menu Approval berdasarkan role
if ($role == 'Manager') {
    // Manager: lihat semua approval
    $approval_menu = [
        'icon' => 'fa-solid fa-check-circle',
        'label' => 'Approval',
        'link' => 'approvalList.php',  // Untuk Manager
        'active_page' => 'approvalList'
    ];
} else {
    // Staff: lihat approval sendiri
    $approval_menu = [
        'icon' => 'fa-solid fa-check-circle',
        'label' => 'Approval',
        'link' => 'myApproval.php',    // Untuk Staff
        'active_page' => 'myApproval'
    ];
}

// Gabungkan menu
$menu_items = $menu_items ?? array_merge($base_menu, [$approval_menu]);

// =============================================
// TAMBAHKAN MENU AUDIT LOG UNTUK MANAGER
// =============================================
if ($role == 'Manager') {
    $menu_items[] = [
        'icon' => 'fa-solid fa-clock-rotate-left',
        'label' => 'Audit Log',
        'link' => 'notificationList.php',
        'active_page' => 'notificationList'
    ];
}
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

    <style>
        /* =============================================
           STYLE UNTUK KONTEN APPROVAL
           ============================================= */
        .approval-content {
            padding: 0 5px;
        }

        .header-approval {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-tag {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 10px 18px;
            border-radius: 10px;
        }

        .card-approval {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary, #0B376D);
            margin-bottom: 20px;
        }

        .btn-create {
            display: inline-block;
            background: var(--primary, #0B376D);
            color: white;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .btn-create:hover {
            background: var(--primary-dark, #082A53);
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: var(--primary, #0B376D);
            color: white;
            padding: 14px;
            text-align: left;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f8fafc;
        }

        .badge {
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .approved {
            background: #DCFCE7;
            color: #166534;
        }

        .rejected {
            background: #FEE2E2;
            color: #991B1B;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #6b7280;
        }

        @media (max-width: 768px) {
            .header-approval {
                flex-direction: column;
                align-items: flex-start;
            }

            .card-approval {
                padding: 16px;
            }

            .card-title {
                font-size: 20px;
            }
        }

        @media (max-width: 576px) {
            .card-approval {
                padding: 12px;
            }

            th,
            td {
                padding: 8px 10px;
                font-size: 12px;
            }

            .badge {
                padding: 4px 10px;
                font-size: 10px;
            }

            .btn-create {
                width: 100%;
                text-align: center;
            }
        }
    </style>

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