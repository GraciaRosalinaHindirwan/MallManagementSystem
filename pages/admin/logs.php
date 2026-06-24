<?php
session_start();

$file = '../../config/audit_log.json';

$logs = [];

if (file_exists($file)) {
    $logs = json_decode(file_get_contents($file), true);
    if (!is_array($logs)) $logs = [];
}

// urut terbaru
usort($logs, function ($a, $b) {
    return strtotime($b['tanggal']) - strtotime($a['tanggal']);
});

// filter user
$filterUser = $_GET['user'] ?? '';
if ($filterUser) {
    $logs = array_filter($logs, function ($log) use ($filterUser) {
        return strtolower($log['username']) === strtolower($filterUser);
    });
}

// stats
$totalLogs = count($logs);
$totalUsers = count(array_unique(array_column($logs, 'username')));

// clear log
if (isset($_POST['clear_log'])) {
    file_put_contents($file, json_encode([]));
    header("Location: logs.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Audit Log</title>

    <style>
        body {
            margin: 0;
            font-family: Arial;
            background: #eef2f7;
        }

        .container {
            padding: 20px;
        }

        /* HEADER */
        .header {
            background: linear-gradient(90deg, #2c3e50, #34495e);
            color: white;
            padding: 18px;
            text-align: center;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }

        .header h2 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 1px;
        }

        /* STATS */
        .stats {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .card {
            flex: 1;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .card h3 {
            margin: 0;
            font-size: 20px;
        }

        .card p {
            margin: 5px 0 0;
            color: gray;
        }

        /* TOP BAR */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        input {
            padding: 8px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        button {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn-red {
            background: #e74c3c;
            color: white;
        }

        .btn-green {
            background: #2ecc71;
            color: white;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        th {
            background: #2c3e50;
            color: white;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f6f9ff;
        }

        .badge {
            background: #dff0ff;
            color: #0077cc;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .activity {
            font-weight: 500;
        }
    </style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <h2>📊 Audit Log Dashboard</h2>
    </div>

    <!-- STATS -->
    <div class="stats">
        <div class="card">
            <h3><?= $totalLogs ?></h3>
            <p>Total Logs</p>
        </div>

        <div class="card">
            <h3><?= $totalUsers ?></h3>
            <p>Active Users</p>
        </div>
    </div>

    <!-- TOP BAR -->
    <div class="topbar">

        <form method="get">
            <input type="text" name="user" placeholder="Filter username..."
                   value="<?= htmlspecialchars($filterUser) ?>">
            <button class="btn-green">Search</button>
        </form>

        <form method="post" onsubmit="return confirm('Yakin hapus semua log?')">
            <button class="btn-red" name="clear_log">Clear All</button>
        </form>

    </div>

    <!-- TABLE -->
    <table>
        <tr>
            <th>No</th>
            <th>User</th>
            <th>Activity</th>
            <th>Time</th>
        </tr>

        <?php if (empty($logs)) : ?>
            <tr>
                <td colspan="4" style="text-align:center;">No logs found</td>
            </tr>
        <?php else : ?>
            <?php $no = 1; foreach ($logs as $log) : ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><span class="badge"><?= htmlspecialchars($log['username']) ?></span></td>
                    <td class="activity"><?= htmlspecialchars($log['aktivitas']) ?></td>
                    <td><?= htmlspecialchars($log['tanggal']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>

    </table>

</div>

</body>
</html>