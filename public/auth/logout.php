<?php
session_start();

require_once '../../config/log_helper.php';

$username = $_SESSION['username'] ?? 'Unknown';
simpanLog($username, 'Logout');

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_unset();
session_destroy();

header('Location: ../index.php');
exit;