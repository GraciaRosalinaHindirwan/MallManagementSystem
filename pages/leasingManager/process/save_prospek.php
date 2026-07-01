<?php

require '../../../config/conn.php';

$tenant = $_POST['tenant_name'];
$brand = $_POST['brand_name'];

mysqli_query($conn,"

INSERT INTO 02_tenants(

tenant_name,
brand_name,
status

)

VALUES(

'$tenant',
'$brand',
'Non-Active'

)

");


header(

"Location: ../prospek_tenant.php?success=1"

);

exit();

?>