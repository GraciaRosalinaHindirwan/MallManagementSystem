<?php
session_start();
require_once '../../config/konek.php';

$role = $_SESSION['role'] ?? 'staff';
if ($role != 'staff') {
    header("Location: approvalList.php");
    exit();
}

// =====================================================
// DEFINISIKAN VARIABEL UNTUK TEMPLATE
// =====================================================
$department_name = "BI, Workflow & Notification";
$user_name = $_SESSION['full_name'] ?? 'Manager';

if (isset($_POST['submit'])) {

    $request_number = "APR" . date("YmdHis");

    $request_type = $_POST['request_type'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    // Nanti ganti pakai session login
    $submitted_by = "Staff";

    $sql = "INSERT INTO `08_approval_requests`
    (
        request_number,
        request_type,
        title,
        description,
        status,
        submitted_by,
        submitted_at
    )
    VALUES
    (
        '$request_number',
        '$request_type',
        '$title',
        '$description',
        'pending',
        '$submitted_by',
        NOW()
    )";

    if ($conn->query($sql)) {
        echo "
        <script>
            alert('Approval berhasil diajukan');
            window.location='myApproval.php';
        </script>";
        exit;
    }
}

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
        --text-accent: #FFB62A;
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
        max-width: 900px;
        margin: auto;
    } */

    .approval-card {
        background: white;
        border-radius: 20px;
        padding: 35px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .2);
    }

    .approval-title {
        font-size: 32px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 10px;
    }

    .approval-subtitle {
        color: #6b7280;
        margin-bottom: 30px;
    }

    .info-box {
        background: #eefbfd;
        border-left: 5px solid var(--accent);
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 25px;
        color: var(--background);
    }

    .info-box strong {
        color: var(--primary);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--primary);
    }

    .form-control {
        width: 100%;
        padding: 13px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 14px;
        transition: .3s;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 4px rgba(0, 212, 216, .15);
    }

    textarea.form-control {
        resize: none;
    }

    .btn-submit {
        background: var(--primary);
        color: white;
        border: none;
        padding: 14px 24px;
        border-radius: 12px;
        cursor: pointer;
        font-weight: 600;
        transition: .3s;
    }

    .btn-submit:hover {
        background: var(--primary-dark);
    }

    .btn-back {
        display: inline-block;
        margin-top: 15px;
        text-decoration: none;
        color: var(--secondary);
        font-weight: 600;
    }

    .btn-back:hover {
        color: var(--secondary-dark);
    }

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
</style>

<div class="container">

    <div class="header">
        <div class="logo">Mall ERP</div>
        <div class="page-tag">Approval Management</div>
    </div>

    <div class="approval-card">

        <div class="approval-title">
            Create Approval Request
        </div>

        <div class="approval-subtitle">
            Submit a new approval request for review.
        </div>

        <div class="info-box">
            <strong>Information</strong><br>
            Pengajuan yang telah dikirim akan berstatus
            <b>Pending</b> hingga diverifikasi oleh Approver.
        </div>

        <form method="POST">

            <div class="form-group">
                <label>Request Type</label>

                <select
                    name="request_type"
                    class="form-control"
                    required>

                    <option value="">-- Select Type --</option>
                    <option value="contract">Contract</option>
                    <option value="renovation">Renovation</option>
                    <option value="purchase">Purchase</option>
                    <option value="event">Event</option>
                    <option value="maintenance">Maintenance</option>

                </select>
            </div>

            <div class="form-group">
                <label>Title</label>

                <input
                    type="text"
                    name="title"
                    class="form-control"
                    placeholder="Enter approval title"
                    required>
            </div>

            <div class="form-group">
                <label>Description</label>

                <textarea
                    name="description"
                    class="form-control"
                    rows="6"
                    placeholder="Enter approval description"
                    required></textarea>
            </div>

            <button
                type="submit"
                name="submit"
                class="btn-submit">

                Submit Approval

            </button>

        </form>

        <a href="myApproval.php" class="btn-back">
            View My Approval
        </a>

    </div>

</div>

<?php
$content = ob_get_clean();

// Panggil template navbar
require_once dirname(__DIR__, 2) . '/includes/08_nav_template.php';
?>