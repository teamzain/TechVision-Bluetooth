<?php
session_start();

// Generate a unique file for each user based on the session ID
$session_id = session_id();
$file = __DIR__ . '/ip_addresses/' . $session_id . '_ip_address.txt';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip_address = trim($_POST['ip_address']);
    
    // Validate the IP address
    if (filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
        die('Invalid IP address format');
    }
    
    // Create directory if it doesn't exist
    if (!is_dir(__DIR__ . '/ip_addresses')) {
        mkdir(__DIR__ . '/ip_addresses', 0777, true);
    }

    // Write the IP address to the user-specific file
    file_put_contents($file,
     $ip_address);
    include 'db.php';
    header("Location: dashboard.php");
    exit();
}
?>
