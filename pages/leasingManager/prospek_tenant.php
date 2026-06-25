<?php

require_once '../../auth/checkSession.php';

require '../../config/conn.php';

$page_title = 'Pendaftaran Prospek';
$active_page = 'prospek';
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
        margin-bottom: 25px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }

    input {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
    }

    button {
        padding: 12px 25px;
        background: #00D4D8;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
    }

    .success {
        padding: 15px;
        background: #22C55E;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid rgba(255, 255, 255, .1);
    }
</style>

<?php if (isset($_GET['success'])): ?>
    <div class="success">
        Prospek berhasil ditambahkan
    </div>
<?php endif; ?>

<div class="card">
    <h2>Pendaftaran Prospek Tenant</h2>

    <form action="process/save_prospek.php" method="POST">
        <div class="form-group">
            <label>Nama Tenant</label>
            <input type="text" name="tenant_name" required>
        </div>

        <div class="form-group">
            <label>Brand</label>
            <input type="text" name="brand_name" required>
        </div>

        <button type="submit">
            Simpan
        </button>
    </form>
</div>

<div class="card">
    <h2>Daftar Tenant</h2>

    <table>
        <tr>
            <th>Tenant</th>
            <th>Brand</th>
            <th>Status</th>
        </tr>

        <?php while ($r = mysqli_fetch_assoc($q)): ?>
            <tr>
                <td><?= $r['tenant_name']; ?></td>
                <td><?= $r['brand_name']; ?></td>
                <td><?= $r['status']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php

$content = ob_get_clean();

require '../../includes/navbarM02.php';

?>