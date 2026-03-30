<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request");
}

$username = trim($_POST["username"]);
$email = trim($_POST["email"]);
$password = $_POST["password"];

if ($username === "" || $email === "" || $password === "") {
    die("All fields are required");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hash);

if ($stmt->execute()) {
    echo "REGISTERED";
} else {
    echo "ERROR: " . $conn->error;
}

$stmt->close();
$conn->close();
?>