<?php

require '../../../config/conn.php';

$id = $_POST['id_contract'];

mysqli_query($conn,"
UPDATE 02_contracts
SET contract_status='Terminated'
WHERE id_contract='$id'
");

header("Location: ../terminasi_kontrak.php?success=1");
exit();

?>