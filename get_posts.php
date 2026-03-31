<?php
session_start();
require_once "db.php";

// ← JAUNS: sesijas pārbaude
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$sql = "
    SELECT 
        p.post_id,
        p.image_path,
        p.caption,
        p.created_at,
        u.username,
        COUNT(l.like_id) AS likes
    FROM posts p
    JOIN users u ON p.user_id = u.user_id
    LEFT JOIN likes l ON p.post_id = l.post_id
    GROUP BY p.post_id
    ORDER BY p.created_at DESC
";

$result = $conn->query($sql);

$posts = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = [
            'post_id'    => (int)$row['post_id'],
            'image_path' => $row['image_path'],
            'caption'    => $row['caption'],
            'created_at' => $row['created_at'],
            'username'   => $row['username'],
            'likes'      => (int)$row['likes']
        ];
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($posts);

$conn->close();
?>