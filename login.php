<?php
session_start();
require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT user_id, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $error = "Invalid username or password";
    } else {
        $stmt->bind_result($user_id, $hash);
        $stmt->fetch();

        if (password_verify($password, $hash)) {
            $_SESSION["user_id"] = $user_id;
            $_SESSION["username"] = $username;
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password";
        }
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <link rel="stylesheet" href="login.css">
</head>
<body>

<form action="login.php" method="POST">
  <h2>Login</h2>
  <?php if ($error): ?>
    <p style="color: red; text-align: center;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <input type="text" name="username" placeholder="Username" required>
  <input type="password" name="password" placeholder="Password" required>
  <button type="submit">Login</button>
  <p style="text-align: center; font-size: 0.85rem; color: #666;">
    Nav konta? <a href="register.php" style="color: #534AB7;">Reģistrēties</a>
  </p>
</form>

</body>
</html>