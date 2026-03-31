<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request");
}

$username = trim($_POST["username"]);
$password = $_POST["password"];

$stmt = $conn->prepare("SELECT user_id, password_hash FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    header("Location: login.html?error=Invalid+username+or+password");
    exit;
}

$stmt->bind_result($user_id, $hash);
$stmt->fetch();

if (password_verify($password, $hash)) {
    $_SESSION["user_id"] = $user_id;
    $_SESSION["username"] = $username;
    header("Location: index.php");
    exit;
} else {
    header("Location: login.html?error=Invalid+username+or+password");
    exit;
}

$stmt->close();
$conn->close();
?>