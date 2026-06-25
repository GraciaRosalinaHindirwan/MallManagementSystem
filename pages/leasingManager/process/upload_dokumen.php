<?php

require '../../../config/conn.php';



$id=$_POST['id_contract'];



$file=$_FILES['dokumen']['name'];

$tmp=$_FILES['dokumen']['tmp_name'];



$path="../../../documents/".$file;


move_uploaded_file($tmp,$path);



$url="/documents/".$file;



mysqli_query($conn,"

UPDATE 02_contracts

SET legal_document_url='$url'

WHERE id_contract='$id'

");


header("Location: ../upload_dokumen.php?success=1");

exit();


?>