<?php

require '../../../config/conn.php';

$id_contract = $_POST['id_contract'];
$end_date = $_POST['end_date'];

$query = mysqli_query($conn,"
UPDATE 02_contracts
SET end_date='$end_date'
WHERE id_contract='$id_contract'
");

if($query){

    header("Location: ../renewal_kontrak.php?success=1");
    exit();

}

header("Location: ../renewal_kontrak.php?error=1");
exit();

?>