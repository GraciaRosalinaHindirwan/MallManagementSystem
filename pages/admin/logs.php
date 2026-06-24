<!DOCTYPE html>
<html>
<head>
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
            background: linear-gradient(135deg,#021b45,#06265d,#04183d);
            min-height:100vh;
            color:white;
            padding:30px;
        }

        .container{
            width:100%;
        }

        /* BACK BUTTON */
        .back-btn{
            display:inline-block;
            text-decoration:none;
            color:#fff;
            background:rgba(255,255,255,.08);
            padding:14px 22px;
            border-radius:12px;
            margin-bottom:25px;
            border:1px solid rgba(255,255,255,.1);
        }

        .back-btn:hover{
            background:rgba(255,255,255,.15);
        }

        /* HEADER */
        .header{
            background:linear-gradient(90deg,#063a7a,#0aa6b8);
            border-radius:20px;
            padding:35px;
            margin-bottom:30px;
        }

        .header h1{
            font-size:42px;
            margin-bottom:8px;
        }

        .header p{
            opacity:.8;
            font-size:18px;
        }

        /* CARD */
        .card{
            background:rgba(8,36,89,.85);
            border:1px solid rgba(255,255,255,.1);
            border-radius:20px;
            padding:30px;
            margin-bottom:30px;
        }

        .card-title{
            color:#19d3f3;
            margin-bottom:25px;
            font-size:24px;
            font-weight:600;
        }

        /* FILTER */
        .filter-form{
            display:flex;
            gap:15px;
        }

        .filter-form input{
            flex:1;
            background:#0b2c64;
            border:1px solid rgba(255,255,255,.1);
            color:white;
            padding:18px;
            border-radius:12px;
            font-size:16px;
        }

        .filter-form button{
            border:none;
            background:#18d4e4;
            color:white;
            padding:18px 35px;
            border-radius:12px;
            cursor:pointer;
            font-size:16px;
            font-weight:600;
        }

        .filter-form button:hover{
            opacity:.9;
        }

        /* TABLE */
        .table-header{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
        }

        .total-log{
            background:rgba(255,255,255,.1);
            padding:8px 15px;
            border-radius:10px;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        th{
            text-align:left;
            padding:20px;
            color:#fff;
            border-bottom:1px solid rgba(255,255,255,.1);
        }

        td{
            padding:22px 20px;
            border-bottom:1px solid rgba(255,255,255,.06);
        }

        tr:hover{
            background:rgba(255,255,255,.03);
        }

        .badge{
            background:rgba(255,255,255,.08);
            padding:8px 14px;
            border-radius:30px;
        }

        .empty{
            text-align:center;
            padding:40px;
        }

        .clear-btn{
            background:#e74c3c;
            color:white;
            border:none;
            padding:12px 20px;
            border-radius:10px;
            cursor:pointer;
            margin-top:20px;
        }
    </style>
</head>

<body>

<div class="container">

    <a href="../dashboard/dashboard.php" class="back-btn">
        <i class="fa fa-arrow-left"></i> Kembali ke Dashboard
    </a>

    <!-- HEADER -->
    <div class="header">
        <h1><i class="fa fa-clipboard-list"></i> Audit Log</h1>
        <p>SISFO MALL</p>
    </div>

    <!-- FILTER -->
    <div class="card">
        <h3 class="card-title">
            <i class="fa fa-filter"></i> FILTER LOG
        </h3>

        <form method="GET" class="filter-form">
            <input type="text"
                   name="user"
                   placeholder="Masukkan username..."
                   value="<?= htmlspecialchars($filterUser) ?>">

            <button type="submit">
                <i class="fa fa-search"></i> Search
            </button>
        </form>
    </div>

    <!-- TABLE -->
    <div class="card">

        <div class="table-header">
            <h3 class="card-title">
                <i class="fa fa-list"></i> DAFTAR LOG AKTIVITAS
            </h3>

            <div class="total-log">
                Total <?= $totalLogs ?> log
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

            <?php if(empty($logs)): ?>
                <tr>
                    <td colspan="4" class="empty">
                        Tidak ada log ditemukan
                    </td>
                </tr>

            <?php else: ?>
                <?php $no=1; foreach($logs as $log): ?>
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
                        <?= date('d M Y H:i:s',
                        strtotime($log['tanggal'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            </tbody>
        </table>

        <form method="POST"
              onsubmit="return confirm('Yakin hapus semua log?')">

            <button class="clear-btn"
                    name="clear_log">
                Hapus Semua Log
            </button>

        </form>

    </div>

</div>

</body>
</html>