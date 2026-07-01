<?php


require '../../../config/conn.php';



$id_contract=$_POST['id_contract'];

$description=$_POST['description'];



$query=mysqli_query($conn,"


INSERT INTO 02_addendums(

id_contract,
description


)

VALUES(

'$id_contract',
'$description'

)


");



if($query){

header(

"Location: ../addendum_kontrak.php?success=1"

);

exit();

}



echo mysqli_error($conn);



?>