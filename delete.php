<?php
session_start();

$session_id = session_id();
$file = __DIR__ . '/ip_addresses/' . $session_id . '_ip_address.txt';

if (!file_exists($file)) {
    die('IP address file not found. Please enter the IP address first.');
}

$ip_address = trim(file_get_contents($file));

if (filter_var($ip_address, FILTER_VALIDATE_IP) === false) {
    die('Invalid IP address in file');
}

if (!isset($_GET['username'])) {
    die('Username not provided.');
}

$username_to_delete = $_GET['username'];

// URL of the FreePBX server's execute.php script
$url = 'http://' . $ip_address . '/execute.php';
$command = 'cat /etc/asterisk/sip_custom.conf';

$data = json_encode(['command' => $command]);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];

$context  = stream_context_create($options);
$output = file_get_contents($url, false, $context);

if ($output === FALSE) {
    die('Error occurred while fetching the file contents');
}

$response = json_decode($output, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die('Error decoding JSON response');
}

$lines = explode("\n", trim($response['output']));

// Parse the file content into an associative array
$config_blocks = [];
$current_block = [];
$is_deleted = false;

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    if ($line[0] === '[') {
        if (!empty($current_block)) {
            // If the block's username matches the one to delete, skip adding it
            if (isset($current_block['username']) && $current_block['username'] !== $username_to_delete) {
                $config_blocks[] = $current_block;
            } else {
                $is_deleted = true;
            }
        }
        $current_block = ['name' => trim($line, '[]')];
    } else {
        list($key, $value) = explode('=', $line, 2);
        $current_block[trim($key)] = trim($value);
    }
}

// If the last block in the file should not be deleted, add it
if (!empty($current_block) && isset($current_block['username']) && $current_block['username'] !== $username_to_delete) {
    $config_blocks[] = $current_block;
}

// Reconstruct the file content
$new_content = '';

foreach ($config_blocks as $block) {
    $new_content .= '[' . $block['name'] . ']' . "\n";
    foreach ($block as $key => $value) {
        if ($key !== 'name') {
            $new_content .= $key . '=' . $value . "\n";
        }
    }
    $new_content .= "\n";
}

// Save the updated content back to the server
$command = 'echo ' . escapeshellarg($new_content) . ' > /etc/asterisk/sip_custom.conf';

$data = json_encode(['command' => $command]);
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data,
    ],
];

$context  = stream_context_create($options);
$output = file_get_contents($url, false, $context);

if ($output === FALSE) {
    die('Error occurred while saving the file contents');
}
if (isset($_GET['deleted'])) {
    $deleted = $_GET['deleted'];
    
    if ($deleted == '1') {
        echo "<script>alert('Deleted successfully');</script>";
    } elseif ($deleted == '0') {
        echo "<script>alert('Deletion failed');</script>";
    }
}
exit;
?>
