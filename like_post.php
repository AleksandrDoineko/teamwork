<?php
require_once "db.php";

// TEMP: hardcoded user until login system exists
$user_id = 1;

if (!isset($_GET['post_id'])) {
    http_response_code(400);
    echo "Missing post_id";
    exit;
}

$post_id = (int)$_GET['post_id'];

$stmt = $conn->prepare("INSERT IGNORE INTO likes (user_id, post_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $post_id);

if ($stmt->execute()) {
    echo "OK";
} else {
    http_response_code(500);
    echo "DB error: " . $conn->error;
}

$stmt->close();
$conn->close();
?>