<?php

require_once '../../auth/checkSession.php';
require '../../config/conn.php';

$page_title = 'Notifikasi Kontrak';
$active_page = 'notifikasi';
$user_name = 'Leasing Manager';
$role = 'leasingManager';

$q = mysqli_query($conn, "
    SELECT 
        c.id_contract, 
        c.contract_number, 
        c.end_date, 
        t.tenant_name 
    FROM 02_contracts c 
    JOIN 02_tenants t 
    ON c.id_tenant = t.id_tenant 
    ORDER BY c.end_date ASC
");

ob_start();

?>

<style>
    .card {
        background: #0B376D;
        padding: 30px;
        border-radius: 15px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 15px;
        border-bottom: 1px solid rgba(255, 255, 255, .1);
        text-align: left;
    }

    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
    }

    .warning {
        background: #F59E0B;
    }

    .safe {
        background: #22C55E;
    }

    .btn {
        padding: 8px 15px;
        background: #00D4D8;
        color: black;
        text-decoration: none;
        border-radius: 8px;
        font-weight: bold;
    }

    .alert {
        padding: 15px;
        background: #22C55E;
        border-radius: 10px;
        margin-bottom: 20px;
    }
</style>

<?php if (isset($_GET['success'])): ?>
    <div class="alert">
        Notifikasi berhasil dikirim
    </div>
<?php endif; ?>

<div class="card">
    <h2>Notifikasi Kontrak</h2>

    <table>
        <tr>
            <th>Tenant</th>
            <th>Kontrak</th>
            <th>Berakhir</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>

        <?php while ($r = mysqli_fetch_assoc($q)): ?>
            <?php
            $batas = strtotime('+30 days');
            $tanggal = strtotime($r['end_date']);

            $status = 'Aman';
            $class = 'safe';

            if ($tanggal <= $batas) {
                $status = 'Akan Berakhir';
                $class = 'warning';
            }
            ?>

            <tr>
                <td><?= $r['tenant_name']; ?></td>
                <td><?= $r['contract_number']; ?></td>
                <td><?= $r['end_date']; ?></td>
                <td>
                    <span class="badge <?= $class; ?>">
                        <?= $status; ?>
                    </span>
                </td>
                <td>
                    <?php if ($status == 'Akan Berakhir'): ?>
                        <a href="process/notifikasi_kontrak.php?id=<?= $r['id_contract']; ?>" class="btn">
                            Kirim
                        </a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php

$content = ob_get_clean();

require '../../includes/navbarM02.php';

?>