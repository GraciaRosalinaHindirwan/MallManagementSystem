<?php
session_start();
require_once '../../config/konek.php';
require_once __DIR__ . '/../../public/auth/checkSession.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/index.php");
    exit();
}

$role = $_SESSION['role'] ?? '';
if (!isset($conn) || !$conn) {
    die("Koneksi database gagal!");
}

$staff = [
    'Finance Staff',
    'Purchasing Staff',
    'Facility Staff',
    'Tenant Staff'
];

if (in_array($_SESSION['role'], $staff)) {
    header("Location: myApproval.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: approvalList.php");
    exit;
}

$id = (int)$_GET['id'];

$query = $conn->query("
SELECT *
FROM `08_approval_requests`
WHERE approval_id = $id
");

if ($query->num_rows == 0) {
    die("Data tidak ditemukan");
}

$data = $query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Approval Detail</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

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

        * {
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
            max-width: 1100px;
            margin: auto;
        }

        .card {
            background: white;
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, .15);
        }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: #64748b;
            margin-bottom: 30px;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table tr {
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-table th {
            width: 250px;
            text-align: left;
            padding: 18px;
            background: #f8fafc;
            color: var(--primary);
            font-weight: 600;
        }

        .detail-table td {
            padding: 18px;
            color: #374151;
        }

        .description-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 12px;
            line-height: 1.8;
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

        .button-group {
            margin-top: 30px;
            display: flex;
            gap: 12px;
        }

        .btn {
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
        }

        .btn-back {
            background: var(--primary);
        }

        .btn-approve {
            background: var(--success);
        }

        .btn-reject {
            background: var(--danger);
        }

        .btn:hover {
            opacity: .9;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="card">

            <div class="page-title">
                Approval Detail
            </div>

            <div class="page-subtitle">
                Review approval request information
            </div>

            <table class="detail-table">

                <tr>
                    <th>Request Number</th>
                    <td><?= $data['request_number']; ?></td>
                </tr>

                <tr>
                    <th>Request Type</th>
                    <td><?= ucfirst($data['request_type']); ?></td>
                </tr>

                <tr>
                    <th>Title</th>
                    <td><?= htmlspecialchars($data['title']); ?></td>
                </tr>

                <tr>
                    <th>Description</th>
                    <td>

                        <div class="description-box">
                            <?= nl2br(htmlspecialchars($data['description'])); ?>
                        </div>

                    </td>
                </tr>

                <tr>
                    <th>Submitted By</th>
                    <td><?= htmlspecialchars($data['submitted_by']); ?></td>
                </tr>

                <tr>
                    <th>Submitted At</th>
                    <td><?= $data['submitted_at']; ?></td>
                </tr>

                <tr>
                    <th>Status</th>

                    <td>

                        <?php

                        if ($data['status'] == "pending") {
                            echo "<span class='badge pending'>Pending</span>";
                        } elseif ($data['status'] == "approved") {
                            echo "<span class='badge approved'>Approved</span>";
                        } else {
                            echo "<span class='badge rejected'>Rejected</span>";
                        }

                        ?>

                    </td>

                </tr>

            </table>

            <div class="button-group">

                <a
                    href="approvalList.php"
                    class="btn btn-back">
                    Back
                </a>

                <?php if ($data['status'] == "pending"): ?>

                    <a
                        href="approveProcess.php?id=<?= $data['approval_id']; ?>"
                        class="btn btn-approve"
                        onclick="return confirm('Approve this request?')">
                        Approve
                    </a>

                    <a
                        href="rejectProcess.php?id=<?= $data['approval_id']; ?>"
                        class="btn btn-reject">
                        Reject
                    </a>

                <?php endif; ?>

            </div>

        </div>

    </div>

</body>

</html>