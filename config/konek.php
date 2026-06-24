<?php
$conn = new mysqli(
    "localhost",
    "root",
    "",
    "mall_erp"
);

if ($conn->connect_error) {
    die("Connection failed");
}
?>
