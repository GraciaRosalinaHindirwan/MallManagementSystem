<?php
session_start();

if (!isset($_SESSION['user_id'])) {

<<<<<<< HEAD
    header('Location: ../index.php');
=======
    header('Location: ../public/index.php');
>>>>>>> 55bf5912288eaf5072aa118db5e7a3075d14d273
    exit;
}

$timeout = 15 * 60; // 900 detik
if (
    isset($_SESSION['last_activity'])
    &&
    (time() - $_SESSION['last_activity']) > $timeout
) {

    $_SESSION['error'] =
        'Session berakhir karena tidak ada aktivitas selama 15 menit.';

    unset($_SESSION['user_id']);

<<<<<<< HEAD
    header('Location: ../index.php');
=======
    header('Location: ../public/index.php');
>>>>>>> 55bf5912288eaf5072aa118db5e7a3075d14d273
    exit;
}
$_SESSION['last_activity'] = time();

/*pasang di semua halaman dengan 
<?php
    require_once 'auth/checkSession.php';
?>
*/
?>