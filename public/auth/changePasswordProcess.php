<?php
session_start();

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: ../auth/changePassword.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {

    header('Location: ../index.php');
    exit;
}

$newPassword = trim($_POST['new_password']);
$confirmPassword = trim($_POST['confirm_password']);

if (empty($newPassword) || empty($confirmPassword)) {

    $_SESSION['error'] =
        'Semua field wajib diisi';

    header('Location: changePassword.php');
    exit;
}

$hashedPassword = password_hash(
    $newPassword,
    PASSWORD_DEFAULT
);

//update database


$_SESSION['success'] =
    'Password berhasil diubah';

header('Location: ../dashboard.php');
exit;

?>