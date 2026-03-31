<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Not logged in";
    exit;
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo "Image upload failed";
    exit;
}

$caption = isset($_POST['caption']) ? trim($_POST['caption']) : "";

$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

// ← JAUNS: atļautie failu tipi
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo "Invalid file type. Allowed: jpg, jpeg, png, gif, webp";
    exit;
}

// ← JAUNS: pārbauda vai tiešām ir attēls
if (!getimagesize($_FILES['image']['tmp_name'])) {
    http_response_code(400);
    echo "File is not a valid image";
    exit;
}

$filename = uniqid("img_", true) . "." . $ext;
$targetPath = $uploadDir . $filename;
$relativePath = "uploads/" . $filename;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo "Failed to move uploaded file";
    exit;
}

$stmt = $conn->prepare("INSERT INTO posts (user_id, image_path, caption) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $relativePath, $caption);

if ($stmt->execute()) {
    echo "OK";
} else {
    http_response_code(500);
    echo "DB error: " . $conn->error;
}

$stmt->close();
$conn->close();
?>