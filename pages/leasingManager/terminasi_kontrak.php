<?php

require_once '../../auth/checkSession.php';
require '../../config/conn.php';

$page_title = 'Terminasi Kontrak';
$active_page = 'terminasi';
$user_name = 'Leasing Manager';
$role = 'leasingManager';

$q = mysqli_query($conn, "
    SELECT 
        id_contract, 
        contract_number 
    FROM 02_contracts 
    WHERE contract_status = 'Active'
");

ob_start();

?>

<style>
    .card {
        background: #0B376D;
        padding: 30px;
        border-radius: 15px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 10px;
    }

    select {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
    }

    button {
        padding: 12px 25px;
        background: #EF4444;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
    }

    .success-box {
        padding: 15px;
        margin-bottom: 20px;
        background: #22C55E;
        border-radius: 10px;
        color: white;
        font-weight: 600;
    }
</style>

<div class="card">
    <?php if (isset($_GET['success'])): ?>
        <div class="success-box">
            Kontrak berhasil diterminasi.
        </div>
    <?php endif; ?>

    <h2>Terminasi Kontrak</h2>

    <form action="process/terminasi.php" method="POST">
        <div class="form-group">
            <label>Pilih Kontrak</label>
            <select name="id_contract">
                <?php while ($r = mysqli_fetch_assoc($q)): ?>
                    <option value="<?= $r['id_contract'] ?>">
                        <?= $r['contract_number'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button>Terminasi</button>
    </form>
</div>

<?php

$content = ob_get_clean();

require '../../includes/navbarM02.php';

?>