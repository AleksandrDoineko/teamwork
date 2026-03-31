<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];

if (!isset($_GET['post_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing post_id']);
    exit;
}

$post_id = (int)$_GET['post_id'];

// Pārbauda vai like jau eksistē
$check = $conn->prepare("SELECT like_id FROM likes WHERE user_id = ? AND post_id = ?");
$check->bind_param("ii", $user_id, $post_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // Jau nolikots → unlike
    $del = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $del->bind_param("ii", $user_id, $post_id);
    $del->execute();
    $del->close();
    $action = "unliked";
} else {
    // Nav nolikots → like
    $ins = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $ins->bind_param("ii", $user_id, $post_id);
    $ins->execute();
    $ins->close();
    $action = "liked";
}

$check->close();

// Atgriež jauno like skaitu
$count = $conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
$count->bind_param("i", $post_id);
$count->execute();
$count->bind_result($total);
$count->fetch();
$count->close();
$conn->close();

echo json_encode(['action' => $action, 'likes' => $total]);
?>