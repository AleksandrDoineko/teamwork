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
    die("Invalid username or password");
}

$stmt->bind_result($user_id, $hash);
$stmt->fetch();

if (password_verify($password, $hash)) {
    $_SESSION["user_id"] = $user_id;
    $_SESSION["username"] = $username;
    echo "LOGGED_IN";
} else {
    echo "Invalid username or password";
}

$stmt->close();
$conn->close();
?>