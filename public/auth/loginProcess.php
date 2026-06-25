<?php
session_start();

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

?>