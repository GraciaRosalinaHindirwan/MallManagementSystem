<?php
session_start();
require_once '../../config/konek.php';
// require_once __DIR__ . '/../../public/auth/checkSession.php';

$data = $conn->query("
SELECT *
FROM `08_approval_requests`
WHERE status IN ('approved','rejected')
ORDER BY approved_at DESC
");

$current_page = 'notificationList';
$department_name = "BI, Workflow & Notification";
$user_name = $_SESSION['full_name'] ?? 'Manager';
$page_title = "Audit Log";

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
        'link' => 'auditLog.php',
        'active_page' => 'auditLog'
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
        --success: #22C55E;
        --danger: #EF4444;
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
        padding: 40px;
    }

    .container {
        max-width: 1400px;
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
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 8px;
    }

    .page-title {
        color: var(--text);
    }

    .page-subtitle {
        color: #64748b;
        margin-bottom: 25px;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    th {
        background: var(--primary);
        color: white;
        padding: 16px;
        text-align: center;
    }

    td {
        padding: 16px;
        border-bottom: 1px solid #e5e7eb;
    }

    .text-center {
        text-align: center;
    }

    .badge {
        padding: 8px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
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
        color: #64748b;
    }
</style>

<div class="container">

    <div class="card">

        <div class="title-page">
            Audit Log
        </div>

        <div class="page-subtitle">
            Approval history records
        </div>

        <table>

            <tr>
                <th>Request Number</th>
                <th>Title</th>
                <th>Status</th>
                <th>Approved By</th>
                <th>Date</th>
                <th>Notes</th>
            </tr>

            <?php if ($data->num_rows > 0): ?>

                <?php while ($row = $data->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?= $row['request_number']; ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['title']); ?>
                        </td>

                        <td class="text-center">

                            <?php if ($row['status'] == "approved"): ?>

                                <span class="badge approved">
                                    Approved
                                </span>

                            <?php else: ?>

                                <span class="badge rejected">
                                    Rejected
                                </span>

                            <?php endif; ?>

                        </td>

                        <td class="text-center">
                            <?= htmlspecialchars($row['approved_by']); ?>
                        </td>

                        <td class="text-center">
                            <?= $row['approved_at']; ?>
                        </td>

                        <td>

                            <?php

                            if ($row['status'] == "rejected") {
                                echo htmlspecialchars($row['reject_reason']);
                            } else {
                                echo "-";
                            }

                            ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="6" class="empty">

                        No audit records found.

                    </td>

                </tr>

            <?php endif; ?>

        </table>

    </div>
</div>

<?php
$content = ob_get_clean();

// Panggil template navbar
require_once dirname(__DIR__, 2) . '/includes/08_nav_template.php';
?>