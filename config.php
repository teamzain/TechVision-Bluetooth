<?php

$conn = new mysqli('localhost', 'root', '', 'techvision'); // Default username 'root', and no password for XAMPP

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// Your code logic goes here

?>
