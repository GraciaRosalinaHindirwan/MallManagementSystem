<?php
session_start();

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: ../index.php');
    exit;
}

//ambil data 
$username = trim($_POST['username']);
$password = trim($_POST['password']);

//cek password supaya tidak di baypass, harusnya register page ini 
if(!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9])(?!.*[+{}]).{8,}$/', $password)){
    $_SESSION['error'] = 'Password tidak memenuhi format yang ditentukan';

    header('Location: ../index.php');
    exit;
}

// buat captcha
$captchaInput = strtoupper(
    trim($_POST['captcha'])
);

if (
    $captchaInput !== $_SESSION['captcha']
) {

    $_SESSION['error'] =
        'Kode captcha salah';

    header('Location: ../index.php');

    exit;
}

if(password_verify($password, $user['password'])){
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['last_activity'] = time();
}

if($user['must_change_password'] == 1){

    $_SESSION['warning'] =
        'Anda wajib mengganti password sebelum menggunakan sistem.';

    header('Location: ../changePassword.php');
    exit;
} else{
    header('Location: ../index.php');
    exit;
}

?>