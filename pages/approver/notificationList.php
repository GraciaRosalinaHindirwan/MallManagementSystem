<?php
session_start();
require_once '../../config/konek.php';

$notif = $conn->query("
SELECT *
FROM `08_approval_requests`
WHERE status='pending'
ORDER BY submitted_at DESC
");

$totalNotif = $notif->num_rows;

// $role = $_SESSION['role'] ?? 'staf'; // default staf
// if ($role == 'manager') {
//     $approval_link = 'approval_manager.php';  // halaman untuk manager
//     $page_title = "Approval Manager";
//     $page_active = "approvalList";
// } else {
//     $approval_link = 'createApproval.php';        // halaman untuk staf
//     $page_title = "Approval";
//     $page_active = "createApproval";
// }

// $current_page = $page_active;
// =====================================================
// DEFINISIKAN VARIABEL UNTUK TEMPLATE
// =====================================================
$department_name = "BI, Workflow & Notification";
$user_name = $_SESSION['full_name'] ?? 'Manager';
$page_title = "Notification";

$menu_items = [
    [
        'icon' => 'fa-solid fa-chart-line',
        'label' => 'Dashboard KPI',
        'link' => '08_dashboard.php',
        'active_page' => 'dashboard'
    ],
    [
        'icon' => 'fa-solid fa-file-alt',
        'label' => 'Laporan',
        'link' => '08_laporan.php',
        'active_page' => 'laporan'
    ],
    [
        'icon' => 'fa-solid fa-check-circle',
        'label' => 'Approval',
        'link' => 'notificationList.php',
        'active_page' => 'notificationList'
    ],
    [
        'icon' => 'fa-solid fa-bell',
        'label' => 'Notifikasi',
        'link' => 'notifikasiList.php',
        'active_page' => 'notifikasi'
    ],
];

ob_start();
?>

<style>
    :root {
        --primary: #0B376D;
        --primary-dark: #082A53;
        --accent: #00D4D8;
        --background: #021F42;
    }

    /* * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    body {
        background: var(--background);
        min-height: 100vh;
        padding: 40px;
    }

    .container {
        max-width: 1100px;
        margin: auto;
    } */

    .card {
        background: white;
        border-radius: 24px;
        padding: 35px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, .15);
    }

    .title-page {
        font-size: 32px;
        color: var(--primary);
        font-weight: 700;
        margin-bottom: 10px;
    }

    .page-title {
        color: var(--text);
    }

    .page-subtitle {
        color: #64748b;
        margin-bottom: 25px;
    }

    .counter {
        background: #eefbfd;
        color: var(--primary);
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 25px;
        font-weight: 600;
    }

    .notification {
        border-left: 5px solid var(--accent);
        background: #f8fafc;
        padding: 20px;
        margin-bottom: 15px;
        border-radius: 12px;
    }

    .notification-title {
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 5px;
    }

    .notification-info {
        color: #64748b;
        margin-bottom: 12px;
    }

    .btn {
        display: inline-block;
        background: var(--primary);
        color: white;
        text-decoration: none;
        padding: 10px 16px;
        border-radius: 10px;
    }

    .empty {
        text-align: center;
        padding: 30px;
        color: #64748b;
    }
</style>

<div class="container">

    <div class="card">

        <div class="title-page">
            Approval Notifications
        </div>

        <div class="page-subtitle">
            Pending requests waiting for approval
        </div>

        <div class="counter">
            Total Pending Approval :
            <b><?= $totalNotif; ?></b>
        </div>

        <?php if ($totalNotif > 0): ?>

            <?php while ($row = $notif->fetch_assoc()): ?>

                <div class="notification">

                    <div class="notification-title">
                        🔔 <?= $row['request_number']; ?>
                    </div>

                    <div class="notification-info">

                        <?= htmlspecialchars($row['title']); ?>

                        <br>

                        Submitted by :
                        <?= htmlspecialchars($row['submitted_by']); ?>

                    </div>

                    <a
                        href="approvalDetail.php?id=<?= $row['approval_id']; ?>"
                        class="btn">

                        View Detail

                    </a>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty">

                Tidak ada approval yang menunggu persetujuan.

            </div>

        <?php endif; ?>

    </div>

</div>

<?php
$content = ob_get_clean();

// Panggil template navbar
require_once dirname(__DIR__, 2) . '/includes/08_nav_template.php';
?>