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

<div class="content-wrapper">
