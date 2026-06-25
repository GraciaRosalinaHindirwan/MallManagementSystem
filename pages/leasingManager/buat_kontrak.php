<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../../config/conn.php';

$page_title = 'Buat Kontrak';
$active_page = 'kontrak';
$user_name = 'Leasing Manager';
$role = 'leasingManager';


$queryTenant = mysqli_query($conn,"
SELECT *
FROM `02_tenants`
WHERE status='Active'
");


if(!$queryTenant){
    die(mysqli_error($conn));
}



$queryUnit = mysqli_query($conn,"
SELECT *
FROM `01_units`
WHERE status='available'
");


if(!$queryUnit){
    die(mysqli_error($conn));
}


ob_start();

?>


<style>

.card{

    background:#123F7A;
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

}


input,
select{


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


</style>



<div class="card">

<h2>Buat Kontrak Baru</h2>



<form action="process/save_contract.php"
method="POST">



<div class="form-group">


<label>Tenant</label>


<select name="id_tenant" required>


<?php while($tenant = mysqli_fetch_assoc($queryTenant)): ?>


<option value="<?= $tenant['id_tenant']; ?>">


<?= $tenant['tenant_name']; ?>


</option>


<?php endwhile; ?>


</select>



</div>





<div class="form-group">


<label>Unit</label>



<select name="id_unit" required>



<?php while($unit = mysqli_fetch_assoc($queryUnit)): ?>


<option value="<?= $unit['id_units']; ?>">


<?= $unit['unit_code']; ?>


</option>



<?php endwhile; ?>



</select>



</div>




<div class="form-group">


<label>Tanggal Mulai</label>


<input
type="date"
name="start_date"
required>



</div>




<div class="form-group">


<label>Tanggal Selesai</label>



<input
type="date"
name="end_date"
required>



</div>




<button type="submit">

Simpan Kontrak

</button>



</form>


</div>




<?php


$content = ob_get_clean();


require '../../includes/navbarM02.php';


?>