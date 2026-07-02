<?php

interface AfterLoginAction
{
    public function handle();
}

class RedirectByRoleAction implements AfterLoginAction
{
    public function __construct(
        public string $role
    ) {
    }

    public function handle()
    {

        //redirect per role disini yeah
        // switch ($this->role) 
        $roleStr = strtolower(trim($this->role));
        switch ($roleStr) {
            case 'super admin':
                header('Location: ../../pages/admin/listUser.php');
                break;
            case 'manager':
                header('Location: ../../pages/Manager/08_dashboard.php');
                break;
            case 'leasing manager':
                header('Location: ../../pages/leasingManager/dashboard.php');
                break;
            case 'finance manager':
                header('Location: ../../pages/financeManager/dashboardManager.php');
                break;
            case 'finance staff':
                header('Location: ../../pages/financeStaff/dashboardStaff.php');
                break;
            case 'purchasing manager':
                header('Location: ../../pages/purchasingManager/dashboardPurchasingmanager.php');
                break;
            case 'purchasing staff':
                header('Location: ../../pages/purchasingStaff/dashboardPurchasingstaff.php');
                break;
            case 'customer service':
                header('Location: ../../pages/CS/cari-tenant.php');
                break;
            case 'event manager':
                header('Location: ../../pages/eventManager/index.php');
                break;
            case 'admin':
                header('Location: ../../testing/dashboardTest.php');
                break;
            case 'hr':
                header('Location: ../../testing/dashboardTest.php');
                break;
            case 'facility manager':
                header('Location: ../../pages/facilityManager/Damage_list.php');
                break;
            case 'facility staff':
                header('Location: ../../testing/dashboardTest.php');
                break;
            case 'teknisi':
                header('Location: ../../pages/teknisi/Checklist.php');
                break;
            case 'pengunjung':
                header('Location: ../../testing/dashboardTest.php');
                break;
            case 'petugas parkir':
                header('Location: ../../testing/dashboardTest.php');
                break;
            case 'event organizer':
                header('Location: ../../testing/dashboardTest.php');
                break;
            case 'tenant owner':
                header('Location: ../../pages/tenant/tenant_portal.php');
                break;
            case 'tenant staff':
                header('Location: ../../pages/tenant/tenant_portal.php');
                break;
            default:
                header('Location: ../../testing/dashboardTest.php');
                break;
        }

        exit;
    }
}

class MustChangePasswordAction implements AfterLoginAction
{
    public function handle()
    {
        $_SESSION['warning'] =
            'Silakan ganti password terlebih dahulu';

        header(
            'Location: ../changePassword.php'
        );
        exit;
    }
}

class AfterLoginProcess
{
    public static function execute(AfterLoginAction $process)
    {
        $process->handle();
    }
}