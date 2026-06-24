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
        body { font-family: Arial; background:#f4f6f9; margin:0; }

        .container { padding:20px; }

        .card {
            background:white;
            padding:20px;
            border-radius:10px;
        }

        table {
            width:100%;
            border-collapse:collapse;
            margin-top:15px;
        }

        th, td {
            border:1px solid #ddd;
            padding:10px;
        }

        th {
            background:#007bff;
            color:white;
        }

        .topbar {
            display:flex;
            justify-content:space-between;
            margin-bottom:10px;
        }

        input { padding:6px; }

        .btn {
            padding:6px 10px;
            border:none;
            cursor:pointer;
        }

        .danger { background:red; color:white; }
        .green { background:green; color:white; }
    </style>
</head>

<body>

<div class="container">
<div class="card">

    <div class="topbar">
        <h2>📊 Audit Log</h2>

        <form method="get">
            <input type="text" name="user" placeholder="Filter user..."
                   value="<?= htmlspecialchars($filterUser) ?>">
            <button class="btn green">Filter</button>
        </form>
    </div>

    <form method="post" onsubmit="return confirm('Hapus semua log?')">
        <button name="clear_log" class="btn danger">Clear Log</button>
    </form>

    <table>
        <tr>
            <th>No</th>
            <th>Username</th>
            <th>Aktivitas</th>
            <th>Tanggal</th>
        </tr>

        <?php if (empty($logs)) : ?>
            <tr>
                <td colspan="4" style="text-align:center;">Tidak ada log</td>
            </tr>
        <?php else : ?>
            <?php $no = 1; foreach ($logs as $log) : ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($log['username']) ?></td>
                    <td><?= htmlspecialchars($log['aktivitas']) ?></td>
                    <td><?= htmlspecialchars($log['tanggal']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>

    </table>

</div>
</div>

</body>
</html>