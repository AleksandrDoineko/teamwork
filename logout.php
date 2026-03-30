<?php
session_start();
session_unset();
session_destroy();
echo "LOGGED_OUT";
?>