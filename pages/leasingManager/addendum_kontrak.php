<?php

require_once '../../auth/checkSession.php';
require '../../config/conn.php';

$page_title = 'Addendum Kontrak';
$active_page = 'addendum';
$user_name = 'Leasing Manager';
$role = 'leasingManager';

$q = mysqli_query($conn, "
    SELECT 
        id_contract, 
        contract_number 
    FROM 02_contracts 
    ORDER BY contract_number
");

$riwayat = mysqli_query($conn, "
    SELECT 
        a.id_addendum, 
        a.description, 
        a.created_at, 
        c.contract_number 
    FROM 02_addendums a 
    JOIN 02_contracts c 
    ON a.id_contract = c.id_contract 
    ORDER BY a.id_addendum DESC
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

    .card h2 {
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

    select, textarea {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
    }

    textarea {
        height: 120px;
        resize: none;
    }

    button {
        padding: 12px 25px;
        background: #00D4D8;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
    }

    button:hover {
        opacity: .9;
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

    .success {
        padding: 15px;
        background: #22C55E;
        border-radius: 10px;
        margin-bottom: 20px;
    }
</style>

<?php if (isset($_GET['success'])): ?>
    <div class="success">
        Addendum berhasil ditambahkan
    </div>
<?php endif; ?>

<div class="card">
    <h2>Addendum Kontrak</h2>

    <form action="process/save_addendum.php" method="POST">
        <div class="form-group">
            <label>Kontrak</label>
            <select name="id_contract" required>
                <?php while ($r = mysqli_fetch_assoc($q)): ?>
                    <option value="<?= $r['id_contract']; ?>">
                        <?= $r['contract_number']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Isi Addendum</label>
            <textarea name="description" required placeholder="Masukkan isi addendum..."></textarea>
        </div>

        <button type="submit">Simpan</button>
    </form>
</div>

<div class="card">
    <h2>Riwayat Addendum</h2>

    <table>
        <tr>
            <th>Kontrak</th>
            <th>Deskripsi</th>
            <th>Tanggal</th>
        </tr>

        <?php while ($a = mysqli_fetch_assoc($riwayat)): ?>
            <tr>
                <td><?= $a['contract_number']; ?></td>
                <td><?= $a['description']; ?></td>
                <td><?= $a['created_at']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php

$content = ob_get_clean();

require '../../includes/navbarM02.php';

?>