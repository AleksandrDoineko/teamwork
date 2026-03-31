<?php
session_start();
session_unset();
session_destroy();
header("Location: login.html"); // ← redirect uz login lapu
exit;
?>