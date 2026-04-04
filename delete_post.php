<?php
session_start();
require_once "db.php";

// Pārbaudām, vai lietotājs ir ielogojies
if (!isset($_SESSION['user_id'])) {
    die("Nav autorizēts lietotājs");
}

$user_id = $_SESSION['user_id'];

// Atļaujam tikai POST pieprasījumus
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Nederīga metode");
}

// Saņemam post_id no formas
$post_id = intval($_POST['post_id'] ?? 0);
if ($post_id <= 0) {
    die("Nederīgs post_id");
}

// Atrodam postu un pārbaudām, vai tas pieder pašreizējam lietotājam
$stmt = $conn->prepare("SELECT image_path FROM posts WHERE post_id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$stmt->bind_result($image_path);

// Ja posts nepieder lietotājam — aizliedzam dzēst
if (!$stmt->fetch()) {
    die("Posts nav atrasts vai nav tiesību to dzēst");
}
$stmt->close();

// Dzēšam attēla failu no servera
$fullPath = __DIR__ . "/" . $image_path;
if (file_exists($fullPath)) {
    unlink($fullPath);
}

// Dzēšam visus šī posta 'like'
$conn->query("DELETE FROM likes WHERE post_id = $post_id");

// 🗑️ Dzēšam pašu postu no datubāzes
$stmt = $conn->prepare("DELETE FROM posts WHERE post_id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$stmt->close();

// Pāradresējam lietotāju atpakaļ uz profila lapu
header("Location: profile.php");
exit;
?>