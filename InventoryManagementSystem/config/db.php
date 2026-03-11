<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "cafe_inventory";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database connection failed");
}

session_start();