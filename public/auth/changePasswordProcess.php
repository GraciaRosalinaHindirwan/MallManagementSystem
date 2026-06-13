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
die('lewat session');
    
$newPassword = trim($_POST['new_password']);
$confirmPassword = trim($_POST['confirm_password']);

if (empty($newPassword) || empty($confirmPassword)) {

    $_SESSION['error'] =
        'Semua field wajib diisi';

    header('Location: changePassword.php');
    exit;
} else if($newPassword !== $confirmPassword){
    $_SESSION['error'] = 'Konfirmasi password tidak cocok';
    header('Location: ../auth/changePassword.php');
    exit;
}

echo "MASUK VALIDASI";
die();

//cek aturan password
if(
    !preg_match(
        '/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9])(?!.*[+{}]).{8,}$/',
        $newPassword
    )
){
    $_SESSION['error'] =
        'Password minimal 8 karakter, harus mengandung huruf kapital, angka, dan simbol. Simbol +, {, } tidak diperbolehkan.';

    header('Location: ../auth/changePassword.php');
    exit;
}

$hashedPassword = password_hash(
    $newPassword,
    PASSWORD_DEFAULT
);

//update database


$_SESSION['success'] =
    'Password berhasil diubah';

header('Location: ../index.php');
exit;

?>