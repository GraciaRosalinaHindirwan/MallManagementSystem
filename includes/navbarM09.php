<?php
$page_title = $page_title ?? 'Dashboard Admin';
$active_page = $active_page ?? '';
$user_name = $user_name ?? 'Guest';
$role = $role ?? 'admin';

$menu = [
    [
        'section' => 'Portal Admin',
        'roles' => ['admin'],
        'items' => [
            [
                'key' => 'rbac',
                'label' => 'Log Aktivitas',
                'href' => '../admin/logs.php',
                'roles' => ['admin']
            ],
            [
                'key' => 'regist-user',
                'label' => 'Tambah User',
                'href' => '../../public/registUser.php',
                'roles' => ['admin']
            ],
            [
                'key' => 'list-user',
                'label' => 'Kelola User',
                'href' => '../admin/listUser.php',
                'roles' => ['admin']
            ],
            [
                'key' => 'Reset Password',
                'label' => 'Reset Password User',
                'href' => '../admin/adminResetPassword.php',
                'roles' => ['admin']
            ],
        ],
    ],
];

/* Label & badge tampilan per role (untuk topbar) */
$roleLabels = [
    'leasingManager' => 'Leasing Manager',
    'financeManager' => 'Finance Manager',
    'financeStaff' => 'Finance Staff',
    'tenant' => 'Tenant',
    'admin' => 'Admin',
];
$roleBadge = $roleLabels[$role] ?? 'User';

/* Saring menu: hanya section & item yang role-nya cocok */
$visibleMenu = [];
foreach ($menu as $section) {
    if (!in_array($role, $section['roles'], true))
        continue;
    $items = array_values(array_filter($section['items'], function ($item) use ($role) {
        return in_array($role, $item['roles'], true);
    }));
    if (!empty($items)) {
        $section['items'] = $items;
        $visibleMenu[] = $section;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — Mall ERP · M02</title>
    <style>
        /* ─────────────────────────────────────────────
           Design tokens — sama dengan designSystem.css
        ───────────────────────────────────────────── */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        :root {
            --primary: #0B376D;
            --primary-dark: #082A53;
            --secondary: #167E80;
            --secondary-dark: #0D4859;
            --accent: #00D4D8;
            --success: #22C55E;
            --danger: #EF4444;
            --background: #021F42;
            --text: #F5F7FA;
            --text-secondary: #B8C7D9;
            --text-accent: #FFB62A;
            --font-family: 'Poppins', sans-serif;
            --h1: 32px;
            --h2: 24px;
            --subheading: 20px;
            --body: 16px;
            --label: 14px;
            --caption: 12px;

            /* Sidebar dimensions */
            --sidebar-w: 260px;
            --sidebar-w-collapsed: 76px;
            --topbar-h: 60px;
        }

        /* ── Reset ── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-family);
            background: var(--background);
            color: var(--text);
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ── Layout shell ── */
        .m02-layout {
            display: flex;
            min-height: 100vh;
        }

        /* ══════════════════════════════
           SIDEBAR
        ══════════════════════════════ */
        .m02-sidebar {
            width: var(--sidebar-w);
            background: var(--primary-dark);
            border-right: 1px solid rgba(255, 255, 255, 0.07);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 200;
            transition: width 0.22s ease, transform 0.28s ease;
        }

        /* ---- Collapsed state (desktop) ---- */
        .m02-sidebar.collapsed {
            width: var(--sidebar-w-collapsed);
        }

        .m02-sidebar.collapsed .m02-brand-text,
        .m02-sidebar.collapsed .m02-nav-section-label,
        .m02-sidebar.collapsed .m02-nav-label,
        .m02-sidebar.collapsed .m02-logout-label {
            display: none;
        }

        .m02-sidebar.collapsed .m02-brand {
            justify-content: center;
            padding: 20px 0 16px;
        }

        .m02-sidebar.collapsed .m02-nav-item {
            justify-content: center;
            padding: 10px 0;
        }

        .m02-sidebar.collapsed .m02-logout {
            justify-content: center;
        }

        .m02-sidebar.collapsed .m02-collapse-toggle .m02-collapse-arrow {
            transform: rotate(180deg);
        }

        /* brand */
        .m02-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 20px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        }

        .m02-brand-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            background: linear-gradient(135deg, var(--accent), var(--secondary));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .m02-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            white-space: nowrap;
        }

        .m02-brand-title {
            font-size: var(--label);
            font-weight: 700;
            color: var(--text);
        }

        .m02-brand-sub {
            font-size: 10px;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        /* collapse toggle button (desktop only) */
        .m02-collapse-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            min-width: 28px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
        }

        .m02-collapse-toggle:hover {
            background: rgba(0, 212, 216, 0.15);
            color: var(--accent);
        }

        .m02-collapse-arrow {
            display: inline-block;
            transition: transform 0.2s ease;
            font-size: 13px;
        }

        .m02-sidebar.collapsed .m02-brand {
            gap: 0;
        }

        /* nav sections */
        .m02-nav {
            flex: 1;
            padding: 12px 0;
        }

        .m02-nav-section {
            margin-bottom: 4px;
        }

        .m02-nav-section-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(184, 199, 217, 0.45);
            padding: 10px 20px 4px;
            white-space: nowrap;
        }

        .m02-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 20px;
            font-size: var(--label);
            color: var(--text-secondary);
            border-radius: 0;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            border-left: 3px solid transparent;
            white-space: nowrap;
            position: relative;
        }

        .m02-nav-item:hover {
            background: rgba(0, 212, 216, 0.07);
            color: var(--text);
        }

        .m02-nav-item.active {
            background: rgba(0, 212, 216, 0.12);
            color: var(--accent);
            border-left-color: var(--accent);
            font-weight: 600;
        }

        .m02-nav-icon {
            font-size: 15px;
            min-width: 18px;
            text-align: center;
        }

        .m02-nav-label {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* tooltip saat collapsed (desktop) */
        .m02-sidebar.collapsed .m02-nav-item::after {
            content: attr(data-label);
            position: absolute;
            left: calc(100% + 10px);
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-dark);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text);
            font-size: var(--caption);
            padding: 5px 10px;
            border-radius: 6px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s;
            z-index: 300;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
        }

        .m02-sidebar.collapsed .m02-nav-item:hover::after {
            opacity: 1;
        }

        /* sidebar footer */
        .m02-sidebar-footer {
            padding: 12px 20px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.07);
        }

        .m02-logout {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: var(--label);
            color: rgba(239, 68, 68, 0.85);
            cursor: pointer;
            transition: background 0.15s;
            white-space: nowrap;
        }

        .m02-logout:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        /* close button — mobile only */
        .m02-sidebar-close {
            display: none;
            position: absolute;
            top: 14px;
            right: 14px;
            background: none;
            border: none;
            color: var(--text);
            font-size: 20px;
            cursor: pointer;
            opacity: 0.6;
        }

        .m02-sidebar-close:hover {
            opacity: 1;
        }

        /* ══════════════════════════════
           MAIN AREA
        ══════════════════════════════ */
        .m02-main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: margin-left 0.22s ease;
        }

        body.m02-collapsed-layout .m02-main {
            margin-left: var(--sidebar-w-collapsed);
        }

        /* ── Topbar ── */
        .m02-topbar {
            height: var(--topbar-h);
            background: var(--primary-dark);
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .m02-topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .m02-hamburger {
            background: none;
            border: none;
            color: var(--text);
            font-size: 20px;
            cursor: pointer;
            opacity: 0.7;
        }

        .m02-hamburger:hover {
            opacity: 1;
        }

        .m02-hamburger.mobile-only {
            display: none;
        }

        .m02-topbar-title {
            font-size: var(--subheading);
            font-weight: 600;
            color: var(--text);
        }

        .m02-topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .m02-module-badge {
            font-size: 10px;
            background: rgba(0, 212, 216, 0.15);
            color: var(--accent);
            border: 1px solid rgba(0, 212, 216, 0.25);
            border-radius: 20px;
            padding: 3px 10px;
            font-weight: 600;
            letter-spacing: 0.04em;
        }

        .m02-role-badge {
            font-size: 10px;
            background: rgba(255, 182, 42, 0.12);
            color: var(--text-accent);
            border: 1px solid rgba(255, 182, 42, 0.3);
            border-radius: 20px;
            padding: 3px 10px;
            font-weight: 600;
            letter-spacing: 0.04em;
        }

        .m02-user-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 5px 14px 5px 8px;
            font-size: var(--caption);
            color: var(--text-secondary);
        }

        .m02-user-avatar {
            width: 26px;
            height: 26px;
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        /* ── Content area ── */
        .m02-content {
            flex: 1;
            padding: 28px 32px;
        }

        /* ── Footer ── */
        .m02-footer {
            padding: 16px 32px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            font-size: var(--caption);
            color: rgba(184, 199, 217, 0.35);
            text-align: center;
        }

        /* ══════════════════════════════
           Mobile overlay
        ══════════════════════════════ */
        .m02-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 190;
        }

        /* ══════════════════════════════
           Responsive
        ══════════════════════════════ */
        @media (max-width: 768px) {
            .m02-sidebar {
                transform: translateX(-100%);
                width: var(--sidebar-w) !important;
                /* collapse mode tidak berlaku di mobile */
            }

            .m02-sidebar.open {
                transform: translateX(0);
            }

            .m02-sidebar.collapsed .m02-brand-text,
            .m02-sidebar.collapsed .m02-nav-section-label,
            .m02-sidebar.collapsed .m02-nav-label,
            .m02-sidebar.collapsed .m02-logout-label {
                display: revert;
            }

            .m02-sidebar.collapsed .m02-nav-item::after {
                display: none;
            }

            .m02-collapse-toggle {
                display: none;
            }

            .m02-sidebar-close {
                display: block;
            }

            .m02-main {
                margin-left: 0 !important;
            }

            .m02-hamburger.mobile-only {
                display: block;
            }

            .m02-hamburger.desktop-only {
                display: none;
            }

            .m02-content {
                padding: 20px 16px;
            }

            .m02-module-badge {
                display: none;
            }

            .m02-overlay.open {
                display: block;
            }
        }
    </style>
</head>

<body>

    <div class="m02-layout">

        <!-- ── Overlay (mobile) ── -->
        <div class="m02-overlay" id="m02Overlay" onclick="m02CloseSidebar()"></div>

        <!-- SIDEBAR -->
        <aside class="m02-sidebar" id="m02Sidebar">
            <button class="m02-sidebar-close" onclick="m02CloseSidebar()">✕</button>

            <!-- Brand + collapse toggle -->
            <div class="m02-brand">
                <div class="m02-brand-text" style="flex:1">
                    <span class="m02-brand-title">Mall ERP</span>
                </div>
                <button class="m02-collapse-toggle desktop-only" id="m02CollapseToggle" onclick="m02ToggleCollapse()"
                    title="Collapse / Expand sidebar">
                    <span class="m02-collapse-arrow">‹</span>
                </button>
            </div>

            <!-- Nav -->
            <nav class="m02-nav">
                <?php foreach ($visibleMenu as $section): ?>
                    <div class="m02-nav-section">
                        <div class="m02-nav-section-label"><?= htmlspecialchars($section['section']) ?></div>
                        <?php foreach ($section['items'] as $item): ?>
                            <a href="<?= htmlspecialchars($item['href']) ?>"
                                class="m02-nav-item <?= ($active_page === $item['key']) ? 'active' : '' ?>"
                                data-label="<?= htmlspecialchars($item['label']) ?>">
                                <span class="m02-nav-label"><?= htmlspecialchars($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($visibleMenu)): ?>
                    <div class="m02-nav-section">
                        <div class="m02-nav-section-label">Tidak ada menu untuk role ini</div>
                    </div>
                <?php endif; ?>
            </nav>

            <!-- Footer -->
            <div class="m02-sidebar-footer">
                <a href="/MallManagementSystem/public/logout.php" class="m02-logout" data-label="Logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span class="m02-logout-label">Logout</span>

                </a>
            </div>
        </aside>

        <!-- MAIN -->
        <div class="m02-main" id="m02Main">

            <!-- Topbar -->
            <header class="m02-topbar">
                <div class="m02-topbar-left">
                    <button class="m02-hamburger mobile-only" onclick="m02OpenSidebar()">☰</button>
                    <span class="m02-topbar-title"><?= htmlspecialchars($page_title) ?></span>
                </div>
                <div class="m02-topbar-right">
                    <span class="m02-role-badge"><?= htmlspecialchars($roleBadge) ?></span>
                    <div class="m02-user-chip">
                        <div class="m02-user-avatar">
                            <?= strtoupper(substr($user_name, 0, 1)) ?>
                        </div>
                        <?= htmlspecialchars($user_name) ?>
                    </div>
                </div>
            </header>

            <script>
                (function () {
                    var sidebar = document.getElementById('m02Sidebar');
                    var overlay = document.getElementById('m02Overlay');
                    var STORAGE_KEY = 'm02SidebarCollapsed';

                    // ---- Mobile open/close ----
                    window.m02OpenSidebar = function () {
                        sidebar.classList.add('open');
                        overlay.classList.add('open');
                    };
                    window.m02CloseSidebar = function () {
                        sidebar.classList.remove('open');
                        overlay.classList.remove('open');
                    };

                    // ---- Desktop collapse/expand ----
                    window.m02ToggleCollapse = function () {
                        var collapsed = sidebar.classList.toggle('collapsed');
                        document.body.classList.toggle('m02-collapsed-layout', collapsed);
                        try { localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0'); } catch (e) { }
                    };

                    // restore saved state on load
                    try {
                        if (localStorage.getItem(STORAGE_KEY) === '1' && window.innerWidth > 768) {
                            sidebar.classList.add('collapsed');
                            document.body.classList.add('m02-collapsed-layout');
                        }
                    } catch (e) { }

                    // close mobile sidebar on resize to desktop
                    window.addEventListener('resize', function () {
                        if (window.innerWidth > 768) {
                            sidebar.classList.remove('open');
                            overlay.classList.remove('open');
                        }
                    });
                })();
            </script>