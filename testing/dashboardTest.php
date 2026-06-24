<?php
require_once __DIR__ . '/../public/auth/checkSession.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div style="text-align: right; padding: 10px;">
        <a href="../public/auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');" style="color: blue; text-decoration: underline;">Logout</a>
    </div>
    <h1>Test Dashboard</h1>
</body>
</html>