<?php
session_start();
require_once '../../config/konek.php';

$data = $conn->query("
    SELECT *
    FROM `08_approval_requests`
    ORDER BY approval_id DESC
");

$role = $_SESSION['role'] ?? 'Staff';
if ($role != 'Staff') {
    header("Location: approvalList.php");
    exit();
}

// =============================================
// VARIABEL UNTUK TEMPLATE
// =============================================
$department_name = "BI, Workflow & Notification";
$page_title = "My Approval";
$user_name = $_SESSION['full_name'] ?? 'Staff';
$current_page = 'myApproval';

// =============================================
// MENU ITEMS - URUTAN YANG BENAR
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
        'link' => 'myApproval.php',
        'active_page' => 'myApproval'
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
<div class="container">

    <div class="header">
        <div class="logo">Mall ERP</div>
        <div class="page-tag">Approval Management</div>
    </div>

    <div class="card">

        <div class="card-title">
            My Approval Requests
        </div>

        <a href="createApproval.php" class="btn-create">
            + Create Approval
        </a>

        <div class="table-container">

            <table>

                <tr>
                    <th>Request Number</th>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Submitted By</th>
                    <th>Submitted At</th>
                    <th>Status</th>
                </tr>

                <?php if ($data->num_rows > 0): ?>

                    <?php while ($row = $data->fetch_assoc()): ?>

                        <tr>

                            <td><?= $row['request_number']; ?></td>

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
                                if ($row['status'] == 'pending') {
                                    echo "<span class='badge pending'>Pending</span>";
                                } elseif ($row['status'] == 'approved') {
                                    echo "<span class='badge approved'>Approved</span>";
                                } else {
                                    echo "<span class='badge rejected'>Rejected</span>";
                                }
                                ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="empty">
                            Belum ada approval yang diajukan.
                        </td>
                    </tr>

                <?php endif; ?>

            </table>

        </div>

    </div>

</div>

<style>
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
        max-width: 1200px;
        margin: auto;
    } */

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .logo {
        color: white;
        font-size: 28px;
        font-weight: 700;
    }

    .page-tag {
        background: rgba(255, 255, 255, .15);
        color: white;
        padding: 10px 18px;
        border-radius: 10px;
    }

    .card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .2);
    }

    .card-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 20px;
    }

    .btn-create {
        display: inline-block;
        background: var(--primary);
        color: white;
        text-decoration: none;
        padding: 12px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    .btn-create:hover {
        background: var(--primary-dark);
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: var(--primary);
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

    .btn-buka {
        background: rgba(0, 212, 216, 0.12);
        color: var(--accent, #00D4D8);
        border: 1px solid rgba(0, 212, 216, 0.25);
    }

    .btn-buka:hover {
        background: var(--accent, #00D4D8);
        color: var(--background, #021F42);
        transform: translateY(-1px);
    }
</style>

<?php
$content = ob_get_clean();

// Panggil template navbar
require_once dirname(__DIR__, 2) . '/includes/08_nav_template.php';
?>