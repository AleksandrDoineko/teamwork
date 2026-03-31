<?php
// git commit: "feat: add update_profile.php — handles bio and avatar upload"
// Fails: update_profile.php — apstrādā bio un avatar augšupielādi

session_start();
require_once "db.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : null;
$avatar_url = null;

// Apstrādā avatar augšupielādi
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nederīgs faila tips']);
        exit;
    }

    if (!getimagesize($_FILES['avatar']['tmp_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Nav derīgs attēls']);
        exit;
    }

    $avatarDir = __DIR__ . "/uploads/avatars/";
    if (!is_dir($avatarDir)) {
        mkdir($avatarDir, 0777, true);
    }

    // Dzēš veco avataru, ja eksistē
    $fetchOld = $conn->prepare("SELECT avatar_url FROM users WHERE user_id = ?");
    $fetchOld->bind_param("i", $user_id);
    $fetchOld->execute();
    $fetchOld->bind_result($oldAvatar);
    $fetchOld->fetch();
    $fetchOld->close();

    if ($oldAvatar && file_exists(__DIR__ . "/" . $oldAvatar)) {
        unlink(__DIR__ . "/" . $oldAvatar);
    }

    $filename = "avatar_" . $user_id . "_" . uniqid() . "." . $ext;
    $targetPath = $avatarDir . $filename;
    $avatar_url = "uploads/avatars/" . $filename;

    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Neizdevās saglabāt avataru']);
        exit;
    }
}

// Veido dinamisko update vaicājumu
if ($avatar_url !== null) {
    $stmt = $conn->prepare("UPDATE users SET bio = ?, avatar_url = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $bio, $avatar_url, $user_id);
} else {
    $stmt = $conn->prepare("UPDATE users SET bio = ? WHERE user_id = ?");
    $stmt->bind_param("si", $bio, $user_id);
}

if ($stmt->execute()) {
    // Atgriež atjauninātos datus
    $fetch = $conn->prepare("SELECT username, email, bio, avatar_url, created_at FROM users WHERE user_id = ?");
    $fetch->bind_param("i", $user_id);
    $fetch->execute();
    $fetch->bind_result($username, $email, $newBio, $newAvatar, $createdAt);
    $fetch->fetch();
    $fetch->close();

    $_SESSION['username'] = $username;

    echo json_encode([
        'success'    => true,
        'username'   => $username,
        'email'      => $email,
        'bio'        => $newBio,
        'avatar_url' => $newAvatar,
        'created_at' => $createdAt
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'DB kļūda: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>