<?php


require '../../../config/conn.php';



$id=$_POST['id'];

$start=$_POST['start_date'];

$end=$_POST['end_date'];

$status=$_POST['status'];



mysqli_query($conn,"


UPDATE `02_contracts`

SET


start_date='$start',

end_date='$end',

contract_status='$status'


WHERE id_contract='$id'


");



header(

"Location:../dashboard.php"

);

exit();
