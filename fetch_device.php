<?php
session_start();

// Generate the unique file for the current user
$session_id = session_id();
$file = __DIR__ . '/ip_addresses/' . $session_id . '_ip_address.txt';

if (!file_exists($file)) {
    die('IP address file not found. Please enter the IP address first.');
}

$ip_address = trim(file_get_contents($file));

if (filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
    die('Invalid IP address in file');
}

// Clear session devices on page load/reload
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    unset($_SESSION['devices']);
}

function getTotalConnectedDevices($ip_address) {
    $file_path = 'config.txt';
    $data = [];
    
    if (file_exists($file_path)) {
        $file_content = file_get_contents($file_path);
        $data = json_decode($file_content, true);
        
        if (!is_array($data)) {
            $data = [];
        }
    }

    foreach ($data as $entry) {
        if (isset($entry['server_ip']) && $entry['server_ip'] == $ip_address) {
            return (int)$entry['connected_devices'];
        }
    }
    return 0;
}

header('Content-Type: application/json');
echo json_encode(['total_connected_devices' => getTotalConnectedDevices($ip_address)]);
?>
