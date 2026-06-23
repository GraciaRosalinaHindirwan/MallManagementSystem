<?php
$conn = new mysqli(
    "localhost",
    "root",
    "",
    "mall_management"
);

if ($conn->connect_error) {
    die("Connection failed");
}
