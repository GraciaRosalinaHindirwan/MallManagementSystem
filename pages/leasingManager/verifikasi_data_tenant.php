<?php

require_once '../../auth/checkSession.php';
require '../../config/conn.php';

$page_title = 'Verifikasi Tenant';
$active_page = 'verifikasi';
$user_name = 'Leasing Manager';
$role = 'leasingManager';

$q = mysqli_query($conn, "
    SELECT *
    FROM 02_tenants
    ORDER BY id_tenant DESC
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

    .btn {
        padding: 8px 15px;
        background: #00D4D8;
        color: black;
        text-decoration: none;
        border-radius: 8px;
        font-weight: bold;
        display: inline-block;
    }

    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        color: white;
    }

    .verify {
        background: #22C55E;
    }

    .pending {
        background: #F59E0B;
    }
</style>

<?php if (isset($_GET['success'])): ?>
    <div style="padding: 15px; margin-bottom: 20px; background: #22C55E; border-radius: 10px;">
        Tenant berhasil diverifikasi
    </div>
<?php endif; ?>

<div class="card">
    <h2>Verifikasi Tenant</h2>

    <table>
        <tr>
            <th>Tenant</th>
            <th>Brand</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>

        <?php while ($r = mysqli_fetch_assoc($q)): ?>
            <tr>
                <td><?= $r['tenant_name']; ?></td>
                <td><?= $r['brand_name']; ?></td>
                <td>
                    <?php
                    $class = ($r['status'] == 'Active') ? 'verify' : 'pending';
                    ?>
                    <span class="badge <?= $class; ?>">
                        <?= $r['status']; ?>
                    </span>
                </td>
                <td>
                    <?php if (trim($r['status']) == 'Non-Active'): ?>
                        <a href="process/verifikasi_tenant.php?id=<?= $r['id_tenant']; ?>" class="btn">
                            Verifikasi
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