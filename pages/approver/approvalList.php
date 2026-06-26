<?php
session_start();
require_once '../../config/konek.php';

$data = $conn->query("
SELECT *
FROM `08_approval_requests`
ORDER BY approval_id DESC
");

$role = $_SESSION['role'] ?? 'staff';
if ($role != 'manager') {
    header("Location: myApproval.php");
    exit();
}

// =====================================================
// TENTUKAN HALAMAN APPROVAL BERDASARKAN ROLE
// =====================================================
if ($role == 'manager') {
    $approval_link = 'approval_manager.php';  // halaman untuk manager
    $page_title = "Approval Manager";
    $page_active = "approvalList";
} else {
    $approval_link = 'myApproval.php';        // halaman untuk staf
    $page_title = "Approval";
    $page_active = "myApproval";
}

// =============================================
// VARIABEL UNTUK TEMPLATE
// =============================================
$department_name = "BI, Workflow & Notification";
$page_title = "Approval Manager";
$user_name = $_SESSION['full_name'] ?? 'Manager';
$current_page = 'approvalList';

// =============================================
// MENU ITEMS - APPROVAL ACTIVE PAGE = approvalList
// =============================================
$menu_items = [
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
        'icon' => 'fa-solid fa-check-circle',
        'label' => 'Approval',
        'link' => 'approval_manager.php',
        'active_page' => 'approvalList'  // ← Harus approvalList
    ],
    [
        'icon' => 'fa-solid fa-bell',
        'label' => 'Notifikasi',
        'link' => '08_notifikasi.php',
        'active_page' => '08_notifikasi'
    ],
];
ob_start();
?>

<style>
    :root {
        --primary: #0B376D;
        --primary-dark: #082A53;
        --secondary: #167E80;
        --accent: #00D4D8;
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
        min-height: 100vh;
        padding: 40px;
    }

    .container {
        max-width: 1300px;
        margin: auto;
    } */

    .card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .2);
    }

    .page-title {
        color: var(--text);
    }

    .title-page {
        font-size: 30px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 10px;
    }

    .page-subtitle {
        color: #6b7280;
        margin-bottom: 25px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: var(--primary);
        color: white;
        padding: 15px;
        text-align: left;
    }

    td {
        padding: 15px;
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

    .btn-detail {
        background: var(--primary);
        color: white;
        text-decoration: none;
        padding: 10px 14px;
        border-radius: 8px;
        font-size: 13px;
    }

    .btn-detail:hover {
        background: var(--primary-dark);
    }

    .empty {
        text-align: center;
        color: #6b7280;
        padding: 30px;
    }
</style>

<div class="container">

    <div class="card">

        <div class="title-page">
            Approval Request List
        </div>

        <div class="page-subtitle">
            Review and manage approval requests.
        </div>

        <table>

            <tr>
                <th>Request Number</th>
                <th>Type</th>
                <th>Title</th>
                <th>Submitted By</th>
                <th>Submitted At</th>
                <th>Status</th>
                <th>Action</th>
            </tr>

            <?php if ($data->num_rows > 0): ?>

                <?php while ($row = $data->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?= $row['request_number']; ?>
                        </td>

                        <td>
                            <?= ucfirst($row['request_type']); ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['title']); ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row['submitted_by']); ?>
                        </td>

                        <td>
                            <?= $row['submitted_at']; ?>
                        </td>

                        <td>

                            <?php

                            if ($row['status'] == "pending") {
                                echo "<span class='badge pending'>Pending</span>";
                            } elseif ($row['status'] == "approved") {
                                echo "<span class='badge approved'>Approved</span>";
                            } else {
                                echo "<span class='badge rejected'>Rejected</span>";
                            }

                            ?>

                        </td>

                        <td>

                            <a
                                href="approvalDetail.php?id=<?= $row['approval_id']; ?>"
                                class="btn-detail">

                                Detail

                            </a>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="7" class="empty">

                        No approval requests found.

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