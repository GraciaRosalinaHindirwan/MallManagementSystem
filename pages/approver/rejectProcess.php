<?php
session_start();
require_once '../../config/konek.php';

if(!isset($_GET['id'])){
    header("Location: approvalList.php");
    exit;
}

$id = (int)$_GET['id'];

$query = $conn->query("
SELECT *
FROM `08_approval_requests`
WHERE approval_id = $id
");

if($query->num_rows == 0){
    die("Data tidak ditemukan");
}

$data = $query->fetch_assoc();

if(isset($_POST['submit'])){

    $reason = trim($_POST['reject_reason']);

    /*
    nanti bisa diganti session login
    */
    $approver = "Manager";

    $sql = "
    UPDATE `08_approval_requests`
    SET
        status = 'rejected',
        reject_reason = '$reason',
        approved_by = '$approver',
        approved_at = NOW()
    WHERE approval_id = $id
    ";

    if($conn->query($sql)){

        echo "
        <script>
            alert('Request berhasil ditolak');
            window.location='approvalList.php';
        </script>
        ";

        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Reject Request</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>

:root{
    --primary:#0B376D;
    --primary-dark:#082A53;
    --danger:#EF4444;
    --background:#021F42;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:var(--background);
    min-height:100vh;
    padding:40px;
}

.container{
    max-width:800px;
    margin:auto;
}

.card{
    background:white;
    border-radius:24px;
    padding:35px;
    box-shadow:0 15px 40px rgba(0,0,0,.15);
}

.title{
    font-size:30px;
    color:var(--danger);
    font-weight:700;
    margin-bottom:10px;
}

.subtitle{
    color:#64748b;
    margin-bottom:25px;
}

.info-box{
    background:#FEF2F2;
    border-left:5px solid var(--danger);
    padding:15px;
    border-radius:10px;
    margin-bottom:25px;
}

.info-box b{
    color:#991B1B;
}

.form-group{
    margin-bottom:20px;
}

label{
    display:block;
    margin-bottom:8px;
    font-weight:600;
    color:var(--primary);
}

textarea{
    width:100%;
    padding:15px;
    border:2px solid #e5e7eb;
    border-radius:12px;
    resize:none;
    min-height:150px;
}

textarea:focus{
    outline:none;
    border-color:var(--primary);
}

.button-group{
    display:flex;
    gap:12px;
}

.btn{
    text-decoration:none;
    padding:12px 20px;
    border-radius:10px;
    color:white;
    font-weight:600;
    border:none;
    cursor:pointer;
}

.btn-back{
    background:#64748b;
}

.btn-reject{
    background:var(--danger);
}

.btn:hover{
    opacity:.9;
}

</style>
</head>
<body>

    <div class="container">

        <div class="card">

            <div class="title">
                Reject Approval Request
            </div>

            <div class="subtitle">
                Provide a reason for rejecting this request.
            </div>

            <div class="info-box">

                <b>Request Number :</b>
                    <?= $data['request_number']; ?>

                <br><br>

                <b>Title :</b>
                <?= htmlspecialchars($data['title']); ?>

            </div>

            <form method="POST">

                <div class="form-group">

                    <label>Reject Reason</label>

                        <textarea
                        name="reject_reason"
                        required
                        placeholder="Masukkan alasan penolakan..."></textarea>

                </div>

                <div class="button-group">

                    <a
                        href="approvalDetail.php?id=<?= $id; ?>"
                        class="btn btn-back">
                        Back
                    </a>

                    <button
                        type="submit"
                        name="submit"
                        class="btn btn-reject">

                        Submit Reject

                    </button>

                </div>

            </form>

        </div>

    </div>

</body>
</html>