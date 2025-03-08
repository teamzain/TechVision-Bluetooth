<?php
session_start(); // Start the session

// Unset only the session variable related to the user
if (isset($_SESSION['user_name'])) {
    unset($_SESSION['user_name']);
}

// Unset only the session variable related to the admin
if (isset($_SESSION['admin_name'])) {
    unset($_SESSION['admin_name']);
}

// Redirect to the login page (index.php)
header("Location: index.php");
exit();
?>
