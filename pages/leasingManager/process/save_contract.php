<?php

require '../../../config/conn.php';


$idTenant=$_POST['id_tenant'];
$idUnit=$_POST['id_unit'];

$start=$_POST['start_date'];
$end=$_POST['end_date'];



$number="CONT-".date("Y")."-".rand(100,999);



$sql="INSERT INTO 02_contracts(

contract_number,
id_tenant,
id_unit,
start_date,
end_date,
contract_status


)

VALUES(

'$number',
'$idTenant',
'$idUnit',
'$start',
'$end',
'Draft'

)

";


mysqli_query($conn,$sql);



header("location:../dashboard.php");

