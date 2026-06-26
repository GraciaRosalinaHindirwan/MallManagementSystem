<?php
session_start();
require_once '../../config/konek.php';

if(!isset($_GET['id'])){
    header("Location: approvalList.php");
    exit;
}

$id = (int)$_GET['id'];

/*
kalau sudah ada login
$approver = $_SESSION['nama'];
*/

$approver = "Manager";

$sql = "
UPDATE `08_approval_requests`
SET
    status = 'approved',
    approved_by = '$approver',
    approved_at = NOW()
WHERE approval_id = $id
";

if($conn->query($sql)){

    echo "
    <script>
        alert('Approval berhasil disetujui');
        window.location='approvalList.php';
    </script>
    ";

}else{

    echo "
    <script>
        alert('Gagal approve data');
        window.location='approvalList.php';
    </script>
    ";

}
?>