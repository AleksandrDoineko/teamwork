<?php
require_once "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($username === "" || $email === "" || $password === "") {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hash);

        if ($stmt->execute()) {
            header("Location: login.php");
            exit;
        } else {
            $error = "Username or email already exists";
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <link rel="stylesheet" href="register.css">
</head>
<body>

<form action="register.php" method="POST">
  <h2>Create Account</h2>
  <?php if ($error): ?>
    <p style="color: red; text-align: center;"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <input type="text" name="username" placeholder="Username" required>
  <input type="email" name="email" placeholder="Email" required>
  <input type="password" name="password" placeholder="Password" required>
  <button type="submit">Register</button>
</form>

</body>
</html>