<?php

require '../../config/conn.php';

$resultTenant = mysqli_query($conn,"
SELECT id_tenant,tenant_name
FROM 02_tenants
WHERE status='Active'
");

$resultUnit = mysqli_query($conn,"
SELECT id_units,unit_code
FROM 01_units
WHERE status='available'
");

?>

<h2>Buat Kontrak</h2>


<form action="process/save_contract.php" method="POST">


<label>Tenant</label>

<select name="id_tenant">

<?php while($t=mysqli_fetch_assoc($resultTenant)):?>

<option value="<?= $t['id_tenant']?>">

<?= $t['tenant_name']?>

</option>

<?php endwhile;?>

</select>



<br><br>


<label>Unit</label>

<select name="id_unit">

<?php while($u=mysqli_fetch_assoc($resultUnit)):?>

<option value="<?= $u['id_units']?>">

<?= $u['unit_code']?>

</option>

<?php endwhile;?>


</select>
<br><br>
<label>Mulai</label>

<input type="date" name="start_date">
<br><br>

<label>Selesai</label>

<input type="date" name="end_date">
<br><br>
<button>
Simpan
</button>
</form>
