<?php

require_once '../../config/conn.php';

$page_title  = 'Dashboard Leasing';
$active_page = 'kontrak';
$user_name   = 'Leasing Manager';
$role        = 'leasingManager';


/* ==========================
   Statistik Dashboard
========================== */

$q1 = mysqli_query($conn,"
SELECT COUNT(*) total
FROM 02_contracts
WHERE contract_status='Active'
");

$aktif = mysqli_fetch_assoc($q1)['total'];


$q2 = mysqli_query($conn,"
SELECT COUNT(*) total
FROM 02_contracts
WHERE contract_status='Draft'
");

$draft = mysqli_fetch_assoc($q2)['total'];


$q3 = mysqli_query($conn,"
SELECT COUNT(*) total
FROM 02_contracts
WHERE contract_status='Active'
AND DATEDIFF(end_date,CURDATE()) <= 30
");

$expired = mysqli_fetch_assoc($q3)['total'];


/* ==========================
   Kontrak Terbaru
========================== */

$q4 = mysqli_query($conn,"
SELECT
    c.id_contract,
    c.contract_number,
    c.contract_status,
    c.start_date,
    c.end_date,
    t.tenant_name

FROM 02_contracts c

LEFT JOIN 02_tenants t
ON c.id_tenant=t.id_tenant

ORDER BY c.id_contract DESC
LIMIT 5
");


ob_start();

?>


<style>

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:20px;
    margin-bottom:30px;
}

.card{
    background:#0B376D;
    padding:25px;
    border-radius:15px;
    border-left:4px solid #00D4D8;
}

.card h2{
    font-size:42px;
    margin-bottom:10px;
}

.card p{
    color:#B8C7D9;
}


.table-box{
    background:#0B376D;
    padding:25px;
    border-radius:15px;
    overflow-x:auto;
}

.table-box h3{
    margin-bottom:20px;
}

.table-box table{
    width:100%;
    border-collapse:collapse;
}

.table-box th{
    padding:15px;
    text-align:left;
    color:#B8C7D9;
    border-bottom:1px solid rgba(255,255,255,.1);
}

.table-box td{
    padding:15px;
    border-bottom:1px solid rgba(255,255,255,.05);
}


.badge{
    padding:6px 14px;
    border-radius:30px;
    font-size:12px;
    font-weight:600;
    display:inline-block;
}

.active{
    background:#22C55E;
}

.draft{
    background:#F59E0B;
}


.btn-edit{
    display:inline-block;
    padding:8px 15px;
    background:#00D4D8;
    color:#082A53;
    font-size:13px;
    font-weight:700;
    border-radius:8px;
    text-decoration:none;
    transition:.3s;
}

.btn-edit:hover{
    background:#00bfc2;
}

</style>




<div class="cards">

    <div class="card">
        <h2><?= $aktif ?></h2>
        <p>Kontrak Aktif</p>
    </div>

    <div class="card">
        <h2><?= $draft ?></h2>
        <p>Draft Kontrak</p>
    </div>

    <div class="card">
        <h2><?= $expired ?></h2>
        <p>Akan Berakhir</p>
    </div>

</div>





<div class="table-box">

    <h3>Kontrak Terbaru</h3>


    <table>

        <thead>
            <tr>
                <th>No Kontrak</th>
                <th>Tenant</th>
                <th>Status</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th>Aksi</th>
            </tr>
        </thead>


        <tbody>

        <?php while($r = mysqli_fetch_assoc($q4)): ?>

            <?php
                $class = ($r['contract_status']=='Active')
                    ? 'active'
                    : 'draft';
            ?>

            <tr>

                <td><?= $r['contract_number'] ?></td>

                <td><?= $r['tenant_name'] ?></td>

                <td>
                    <span class="badge <?= $class ?>">
                        <?= $r['contract_status'] ?>
                    </span>
                </td>

                <td><?= $r['start_date'] ?></td>

                <td><?= $r['end_date'] ?></td>

                <td>

                    <a
                        href="contract_edit.php?id=<?= $r['id_contract'] ?>"
                        class="btn-edit"
                    >
                        Edit
                    </a>

                </td>

            </tr>

        <?php endwhile; ?>

        </tbody>

    </table>

</div>



<?php

$content = ob_get_clean();

require_once '../../includes/navbarM02.php';

?>