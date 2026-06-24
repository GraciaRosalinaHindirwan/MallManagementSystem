<?php
session_start();

$file = '../../config/audit_log.json';

// Ambil data log
$logs = [];

if (file_exists($file)) {
    $logs = json_decode(file_get_contents($file), true);

    if (!is_array($logs)) {
        $logs = [];
    }
}

// Urutkan dari terbaru
usort($logs, function ($a, $b) {
    return strtotime($b['tanggal']) - strtotime($a['tanggal']);
});

// Filter user
$filterUser = $_GET['user'] ?? '';

if (!empty($filterUser)) {
    $logs = array_filter($logs, function ($log) use ($filterUser) {
        return strtolower($log['username']) === strtolower($filterUser);
    });
}

// Total log
$totalLogs = count($logs);

// Hapus semua log
if (isset($_POST['clear_log'])) {
    file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
    header("Location: logs.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Audit Log</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Poppins',sans-serif;
        }

        body{
            background:linear-gradient(135deg,#021b45,#06265d,#04183d);
            min-height:100vh;
            color:#fff;
            padding:25px;
        }

        .back-btn{
            display:inline-block;
            text-decoration:none;
            color:#fff;
            background:rgba(255,255,255,.08);
            padding:14px 22px;
            border-radius:12px;
            margin-bottom:30px;
            border:1px solid rgba(255,255,255,.1);
        }

        .back-btn:hover{
            background:rgba(255,255,255,.15);
        }

        .header{
            background:linear-gradient(90deg,#0c4c8d,#13b7c8);
            border-radius:22px;
            padding:40px;
            margin-bottom:30px;
        }

        .header h1{
            font-size:52px;
            margin-bottom:10px;
        }

        .header p{
            font-size:22px;
            opacity:.8;
        }

        .card{
            background:rgba(8,36,89,.85);
            border:1px solid rgba(255,255,255,.1);
            border-radius:22px;
            padding:30px;
            margin-bottom:30px;
        }

        .card-title{
            color:#18d4f4;
            margin-bottom:25px;
            font-size:30px;
        }

        .filter-form{
            display:flex;
            gap:15px;
        }

        .filter-form input{
            flex:1;
            background:#0a2c63;
            border:1px solid rgba(255,255,255,.1);
            color:#fff;
            padding:18px;
            border-radius:12px;
            font-size:16px;
        }

        .filter-form button{
            border:none;
            background:#18d4e4;
            color:#fff;
            padding:18px 35px;
            border-radius:12px;
            cursor:pointer;
            font-size:16px;
            font-weight:600;
        }

        .table-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
        }

        .total-log{
            background:rgba(255,255,255,.1);
            padding:10px 18px;
            border-radius:10px;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th{
            text-align:left;
            padding:20px;
            border-bottom:1px solid rgba(255,255,255,.1);
        }

        td{
            padding:20px;
            border-bottom:1px solid rgba(255,255,255,.05);
        }

        tr:hover{
            background:rgba(255,255,255,.03);
        }

        .badge{
            background:rgba(255,255,255,.08);
            padding:8px 14px;
            border-radius:20px;
        }

        .empty{
            text-align:center;
            padding:40px;
        }

        .clear-btn{
            margin-top:25px;
            border:none;
            background:#e74c3c;
            color:white;
            padding:14px 20px;
            border-radius:10px;
            cursor:pointer;
        }

        @media(max-width:768px){

            .filter-form{
                flex-direction:column;
            }

            table{
                display:block;
                overflow-x:auto;
            }

            .table-header{
                flex-direction:column;
                gap:15px;
            }
        }
    </style>

</head>
<body>

<a href="../dashboard/dashboard.php" class="back-btn">
    <i class="fa fa-arrow-left"></i>
    Kembali ke Dashboard
</a>

<div class="header">
    <h1>
        <i class="fa-solid fa-clipboard-list"></i>
        Audit Log
    </h1>
    <p>SISFO MALL</p>
</div>

<!-- FILTER -->
<div class="card">

    <h3 class="card-title">
        <i class="fa fa-filter"></i>
        FILTER LOG
    </h3>

    <form method="GET" class="filter-form">

        <input type="text"
               name="user"
               placeholder="Masukkan username..."
               value="<?= htmlspecialchars($filterUser) ?>">

        <button type="submit">
            <i class="fa fa-search"></i>
            Search
        </button>

    </form>

</div>

<!-- TABEL -->
<div class="card">

    <div class="table-header">

        <h3 class="card-title">
            <i class="fa fa-list"></i>
            DAFTAR LOG AKTIVITAS
        </h3>

        <div class="total-log">
            Total <?= $totalLogs ?> Log
        </div>

    </div>

    <table>

        <thead>
            <tr>
                <th>No</th>
                <th>User</th>
                <th>Aktivitas</th>
                <th>Waktu</th>
            </tr>
        </thead>

        <tbody>

        <?php if (empty($logs)): ?>

            <tr>
                <td colspan="4" class="empty">
                    Tidak ada log ditemukan
                </td>
            </tr>

        <?php else: ?>

            <?php $no = 1; ?>

            <?php foreach ($logs as $log): ?>

                <tr>

                    <td><?= $no++ ?></td>

                    <td>
                        <span class="badge">
                            <?= htmlspecialchars($log['username']) ?>
                        </span>
                    </td>

                    <td>
                        <?= htmlspecialchars($log['aktivitas']) ?>
                    </td>

                    <td>
                        <?= date('d M Y H:i:s', strtotime($log['tanggal'])) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

    <form method="POST"
          onsubmit="return confirm('Yakin ingin menghapus semua log?')">

        <button class="clear-btn" name="clear_log">
            Hapus Semua Log
        </button>

    </form>

</div>

</body>
</html>