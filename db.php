<?php
$host = "localhost";      // your database host
$user = "root";           // your MySQL username
$pass = "";               // your MySQL password
$dbname = "project";     // your database name

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>