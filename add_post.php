<?php
require_once "db.php";

// TEMP: hardcoded user until login system exists
$user_id = 1;

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

// Create uploads directory if not exists
$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
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