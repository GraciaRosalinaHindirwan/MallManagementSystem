<?php
session_start();

// Hapus semua data session
$_SESSION = array();

// Hancurkan session yang berjalan
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Tendang balik ke halaman login utama (index.php di folder public)
header("Location: index.php");
exit();
