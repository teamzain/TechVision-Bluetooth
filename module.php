<?php
session_start();
$message = '';

// Function to execute the command
function executeCommand($command) {
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

    // Prepend the command with 'asterisk -rx'
    $command = 'asterisk -rx "' . $command . '"';

    // URL of the FreePBX server's execute.php script
    $url = 'http://' . $ip_address . '/execute.php';

    // Prepare the data
    $data = json_encode(['command' => $command]);
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => $data,
        ],
    ];

    // Create the context for the HTTP request
    $context  = stream_context_create($options);

    // Send the request and get the result
    $output = file_get_contents($url, false, $context);

    if ($output === FALSE) {
        die('Error occurred while executing the command');
    }

    // Decode the JSON response
    $response = json_decode($output, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Error decoding JSON response');
    }

    return $response;
}

// Handle POST requests for toggle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $command = $_POST['command'];
    $response = executeCommand($command);
    echo json_encode($response);
    exit; // Stop further script execution
}

// On page load, execute the unload command by default
$initialResponse = executeCommand('module unload chan_mobile.so');
?>
