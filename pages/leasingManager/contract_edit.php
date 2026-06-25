<?php

error_reporting(E_ALL);
ini_set('display_errors',1);

require '../../config/conn.php';

$page_title='Edit Kontrak';
$active_page='kontrak';
$user_name='Leasing Manager';
$role='leasingManager';


$id=$_GET['id'];


$data=mysqli_query($conn,"

SELECT *

FROM `02_contracts`

WHERE id_contract='$id'

");


$row=mysqli_fetch_assoc($data);


ob_start();

?>


<style>

.card{

background:#123F7A;
padding:30px;
border-radius:15px;

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

border:none;

border-radius:8px;

font-weight:bold;

cursor:pointer;

}


</style>



<div class="card">


<h2>Edit Kontrak</h2>


<form
action="process/update_contract.php"
method="POST">



<input
type="hidden"
name="id"
value="<?= $row['id_contract'];?>">



<div class="form-group">

<label>Tanggal Mulai</label>

<input

type="date"

name="start_date"

value="<?= $row['start_date'];?>"

required>


</div>





<div class="form-group">

<label>Tanggal Selesai</label>


<input

type="date"

name="end_date"

value="<?= $row['end_date'];?>"

required>



</div>




<div class="form-group">

<label>Status</label>


<select name="status">



<option
<?= $row['contract_status']=='Draft'?'selected':'';?>
>

Draft


</option>




<option
<?= $row['contract_status']=='Active'?'selected':'';?>
>

Active


</option>



</select>



</div>




<button>


Update


</button>




</form>


</div>



<?php

$content=ob_get_clean();

require '../../includes/navbarM02.php';

?>