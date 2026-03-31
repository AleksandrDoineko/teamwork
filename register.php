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

// ← JAUNS: e-pasta validācija
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email address");
}

// ← JAUNS: paroles garuma pārbaude
if (strlen($password) < 6) {
    die("Password must be at least 6 characters");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hash);

if ($stmt->execute()) {
    header("Location: login.html");
    exit;
} else {
    echo "ERROR: " . $conn->error;
}

$stmt->close();
$conn->close();
?>