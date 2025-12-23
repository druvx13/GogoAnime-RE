<?php
session_start();
require_once('../config/info.php');

session_destroy();
setcookie('remember_me', '', time() - 3600, '/');
header("Location: $base_url/login.html");
exit();
?>