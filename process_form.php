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
$type = $_POST['type'];
$username = $_POST['username'];
$secret = $_POST['secret'];
$host = $_POST['host'];
$port = $_POST['port'];
$context = $_POST['context'];
$disallow = $_POST['disallow'];
$allow_ulaw = $_POST['allow_ulaw'];
$allow_alaw = $_POST['allow_alaw'];
$nat = $_POST['nat'];
$qualify = $_POST['qualify'];
$canreinvite = $_POST['canreinvite'];
$transport = $_POST['transport'];

// Generate the configuration string
$config = "
[purevoip]
type=$type
username=$username
secret=$secret
host=$host
port=$port
context=$context
disallow=$disallow
ulaw=$allow_ulaw
alaw=$allow_alaw
nat=$nat
qualify=$qualify
canreinvite=$canreinvite
transport=$transport
";

// Define the URL of the FreePBX server's custom.php script
$url = 'http://' . $ip_address . '/custom.php';

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
    die('Error decoding JSON response');
}

if (!isset($response['status']) || $response['status'] !== 'success') {
    die('Failed to save configuration on FreePBX server');
}

// Redirect to sip_server.php with a success message
header("Location: sip_server.php?status=success");
exit;
?>