<?php
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
        </div>

        <div style="background: rgba(0,0,0,0.2); padding: 20px; border-top: 1px solid rgba(255,255,255,0.05); margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="width: 35px; height: 35px; background: var(--accent); color: #021F42; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                    E
                </div>
                <div>
                    <p style="margin: 0; font-size: 13px; font-weight: 600; color: #fff;"><?= $display_name; ?></p>
                    <p style="margin: 0; font-size: 11px; color: #00cfd5; font-weight: 500;"><?= $display_role; ?></p>
                </div>
            </div>
            <a href="<?= BASE_URL; ?>logout.php" onclick="return confirm('Apakah anda yakin ingin keluar?')" style="color: #ef4444; text-decoration: none; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar Aplikasi
            </a>
        </div>
    </div>
</div>

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