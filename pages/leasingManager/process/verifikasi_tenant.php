<?php

require '../../../config/conn.php';


$id=$_GET['id'];


mysqli_query($conn,"

UPDATE 02_tenants

SET status='Active'

WHERE id_tenant='$id'

");


header(

"Location:../verifikasi_data_tenant.php?success=1"

);

exit;

?>