<?php
$conn = new mysqli(
    "localhost",
    "root",
    "",
    "fm"
);

if ($conn->connect_error) {
    die("Connection failed");
}
?>