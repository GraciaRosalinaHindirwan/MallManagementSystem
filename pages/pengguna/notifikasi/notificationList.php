<?php
session_start();
require_once __DIR__ . "/../../../config/konek.php";

$notif = $conn->query("
SELECT *
FROM `08_approval_requests`
WHERE status='pending'
ORDER BY submitted_at DESC
");

$totalNotif = $notif->num_rows;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notification List</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0B376D;
            --primary-dark: #082A53;
            --accent: #00D4D8;
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
            min-height: 100vh;
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
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 10px;
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

</head>

<body>

    <div class="container">

        <div class="card">

            <div class="page-title">
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

</body>

</html>
