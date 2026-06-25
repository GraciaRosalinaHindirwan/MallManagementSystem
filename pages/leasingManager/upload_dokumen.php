<?php

require_once '../../auth/checkSession.php';
require '../../config/conn.php';

$page_title = 'Upload Dokumen';
$active_page = 'dokumen';
$user_name = 'Leasing Manager';
$role = 'leasingManager';

$q = mysqli_query($conn, "
    SELECT 
        id_contract, 
        contract_number 
    FROM 02_contracts 
    ORDER BY contract_number
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

    select, input {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
    }

    button {
        padding: 12px 25px;
        background: #00D4D8;
        color: #082A53;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
    }

    .success {
        background: #22C55E;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 10px;
    }
</style>

<div class="card">
    <?php if (isset($_GET['success'])): ?>
        <div class="success">
            Dokumen berhasil diupload
        </div>
    <?php endif; ?>

    <h2>Upload Dokumen Legal</h2>

    <form action="process/upload_dokumen.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Kontrak</label>
            <select name="id_contract">
                <?php while ($r = mysqli_fetch_assoc($q)): ?>
                    <option value="<?= $r['id_contract']; ?>">
                        <?= $r['contract_number']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>File PDF</label>
            <input type="file" name="dokumen" accept=".pdf" required>
        </div>

        <button type="submit">Upload</button>
    </form>
</div>

<?php

$content = ob_get_clean();

require '../../includes/navbarM02.php';

?>