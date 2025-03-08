<?php
session_start();

// Display session data
echo '<h1>Session Data</h1>';
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
?>
