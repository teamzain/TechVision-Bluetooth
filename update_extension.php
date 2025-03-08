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

// Retrieve form data
$extensionConfig = $_POST['extensionConfig'] ?? '';

if (empty($extensionConfig)) {
    die('Extension configuration data is missing.');
}

// Generate the configuration string
$config = "
[from-internal]
$extensionConfig
";

// Define the URL of the FreePBX server's extension.php script
$url = 'http://' . $ip_address . '/extension.php';

// Prepare the data to send to the FreePBX server
$data = json_encode(['config' => $config]);

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];

$context  = stream_context_create($options);

$output = @file_get_contents($url, false, $context);

if ($output === FALSE) {
    $error = error_get_last();
    die('Error occurred while sending request: ' . $error['message']);
}

$response = json_decode($output, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die('Error decoding JSON response: ' . json_last_error_msg());
}

if (!isset($response['status']) || $response['status'] !== 'success') {
    die('Failed to save configuration on FreePBX server. Response: ' . print_r($response, true));
}

// Redirect to a confirmation page with a success message
header("Location: outbound.php?status=success");
exit;
?>
