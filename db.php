<?php
session_start();

// Generate a unique file for each user based on the session ID
$session_id = session_id();
$ip_file = __DIR__ . '/ip_addresses/' . $session_id . '_ip_address.txt';
$output_file = __DIR__ . '/ip_addresses/' . $session_id . '_db_output.txt';

// Check if the IP address file exists
if (!file_exists($ip_file)) {
    die('IP address file not found. Please enter the IP address first.');
}

// Read and validate the IP address
$ip_address = trim(file_get_contents($ip_file));
if (filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
    die('Invalid IP address in file');
}

// URL of the FreePBX server's execute.php script
$url = 'http://' . $ip_address . '/execute.php'; // Use the IP address from the file
$command = 'cat /etc/freepbx.conf'; // Command to execute

// Prepare the data
$data = json_encode(['command' => $command]);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
        'timeout' => 30, // Timeout for the HTTP request
    ],
];

// Create the context for the HTTP request
$context  = stream_context_create($options);

// Send the request and get the result
$output = @file_get_contents($url, false, $context);

if ($output === FALSE) {
    $error = error_get_last();
    die('Error occurred while making the request: ' . htmlspecialchars($error['message']));
}

// Decode the JSON response
$response = json_decode($output, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die('Error decoding JSON response: ' . htmlspecialchars(json_last_error_msg()));
}

// Check if 'output' key exists
if (!isset($response['output'])) {
    if (isset($response['error'])) {
        die('Error: ' . htmlspecialchars($response['error']));
    } else {
        die('The response does not contain the expected output key');
    }
}

// Ensure the directory exists
if (!is_dir(__DIR__ . '/ip_addresses')) {
    mkdir(__DIR__ . '/ip_addresses', 0777, true);
}

// Save the output to the user-specific file
if (file_put_contents($output_file, $response['output']) === false) {
    die('Failed to write output to file');
}

echo "<h1>Command Output Saved</h1>";
echo "<p>The output has been saved to <a href='" . htmlspecialchars($output_file) . "'>db_output.txt</a></p>";
?>
