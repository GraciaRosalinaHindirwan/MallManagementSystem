<?php

require '../../config/conn.php';

$page_title = 'Renewal Kontrak';
$active_page = 'renewal';
$user_name = 'Leasing Manager';
$role = 'leasingManager';


$q = mysqli_query($conn,"
SELECT
    id_contract,
    contract_number,
    end_date
FROM 02_contracts
WHERE contract_status='Active'
ORDER BY contract_number
");

ob_start();

?>


<style>

.card{
    background:#0B376D;
    padding:30px;
    border-radius:15px;
}

.card h2{
    margin-bottom:25px;
}


.form-group{
    margin-bottom:20px;
}


label{
    display:block;
    margin-bottom:8px;
    font-weight:500;
}


select,
input{

    width:100%;
    padding:12px;

    border:none;
    border-radius:8px;

}


button{

    padding:12px 25px;

    background:#00D4D8;
    color:#082A53;

    border:none;
    border-radius:8px;

    font-weight:bold;
    cursor:pointer;

}


button:hover{
    opacity:.9;
}

.success-box{

padding:15px;

margin-bottom:20px;

background:#22C55E;

color:white;

border-radius:10px;

font-weight:600;

}



.error-box{

padding:15px;

margin-bottom:20px;

background:#EF4444;

color:white;

border-radius:10px;

font-weight:600;

}


</style>



<div class="card">
<?php if(isset($_GET['success'])): ?>

<div class="success-box">

    Kontrak berhasil diperpanjang.

</div>

<?php endif; ?>


<?php if(isset($_GET['error'])): ?>

<div class="error-box">

    Gagal memperpanjang kontrak.

</div>

<?php endif; ?>

<h2>Renewal Kontrak</h2>


<form
action="process/renewal.php"
method="POST"
>



<div class="card">

<h2>Renewal Kontrak</h2>


<form action="process/renewal.php" method="POST">


<div class="form-group">

<label>Pilih Kontrak</label>

<select name="id_contract" required>

<?php while($r=mysqli_fetch_assoc($q)): ?>

<option value="<?= $r['id_contract'] ?>">

<?= $r['contract_number'] ?>

(<?= $r['end_date'] ?>)

</option>

<?php endwhile; ?>


</select>

</div>



<div class="form-group">

<label>Tanggal Baru</label>

<input
type="date"
name="end_date"
required
>

</div>



<button type="submit">

Perpanjang Kontrak

</button>


</form>

</div>



<?php

$content = ob_get_clean();

require '../../includes/navbarM02.php';

?>